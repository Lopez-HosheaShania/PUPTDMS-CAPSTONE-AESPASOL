<?php

namespace App\Console\Commands;

use App\Models\ExternalAdminProfile;
use App\Models\Patient;
use App\Services\CmsApiService;
use App\Services\FacultyApiService;
use App\Services\StudentApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class SyncPatientClassifications extends Command
{
    protected $signature = 'patients:reconcile-classifications
        {--patient=* : Limit the review to these patient IDs}
        {--source=local : Evidence to use: local, faculty, student, cms, or all}
        {--apply : Save the proposed repairs; otherwise only preview}';

    protected $description = 'Preview or repair patient categories and missing institutional information without changing account roles';

    public function handle(FacultyApiService $facultyApi, StudentApiService $studentApi, CmsApiService $cmsApi): int
    {
        $source = $this->option('source');
        $ids = $this->option('patient');

        if (! in_array($source, ['local', 'faculty', 'student', 'cms', 'all'], true)) {
            $this->error('Source must be local, faculty, student, cms, or all.');

            return self::INVALID;
        }

        foreach ($ids as $id) {
            if (! ctype_digit((string) $id) || (int) $id < 1) {
                $this->error('Patient IDs must be positive integers.');

                return self::INVALID;
            }
        }

        $query = Patient::query()->when($ids !== [], fn ($query) => $query->whereIn('id', $ids));
        if (! $query->exists()) {
            $this->warn('No matching patients.');

            return self::SUCCESS;
        }

        try {
            $faculties = in_array($source, ['faculty', 'all'], true) ? $facultyApi->getFaculties() : [];
            $students = in_array($source, ['student', 'all'], true) ? $studentApi->getAllStudents() : [];
            $admins = in_array($source, ['cms', 'all'], true) ? $cmsApi->getAdmins() : [];
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Directory retrieval failed. No patient records were changed. Check the application logs.');

            return self::FAILURE;
        }

        // The faculty service also returns [] on connection/configuration failures.
        if ((in_array($source, ['faculty', 'all'], true) && $faculties === [])
            || (in_array($source, ['student', 'all'], true) && $students === [])
            || (in_array($source, ['cms', 'all'], true) && $admins === [])) {
            $this->error('A requested directory was empty or unavailable. No patient records were changed.');

            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $this->info($apply ? 'Applying patient repairs.' : 'PREVIEW ONLY: patient records will not be changed.');
        $changed = 0;
        $failed = false;

        foreach ($query->with(['user', 'studentInformation', 'facultyInformation'])->lazyById(100) as $patient) {
            try {
                if ($apply) {
                    // Re-read and re-evaluate inside the transaction, including related information.
                    [$before, $updates, $reason] = DB::transaction(function () use ($patient, $faculties, $students, $admins) {
                        $current = Patient::query()->lockForUpdate()->findOrFail($patient->id);
                        $current->setRelation('studentInformation', $current->studentInformation()->lockForUpdate()->first());
                        $current->setRelation('facultyInformation', $current->facultyInformation()->lockForUpdate()->first());
                        [$updates, $reason] = $this->plan($current, $faculties, $students, $admins);
                        $before = $current->classification;

                        if ($updates !== []) {
                            $patientUpdates = $updates;
                            unset($patientUpdates['external_admin_id']);
                            if ($patientUpdates !== []) {
                                $current->fill($patientUpdates)->save();
                            }
                            if (isset($updates['external_admin_id'])) {
                                $profile = ExternalAdminProfile::whereRaw('LOWER(TRIM(email)) = ?', [strtolower(trim($current->email))])
                                    ->lockForUpdate()->sole();
                                $profile->update(['external_admin_id' => $updates['external_admin_id']]);
                            }
                        }

                        return [$before, $updates, $reason];
                    });
                } else {
                    [$updates, $reason] = $this->plan($patient, $faculties, $students, $admins);
                    $before = $patient->classification;
                }

                $changed += $updates !== [] ? 1 : 0;
                $this->line(sprintf('Patient %d | %s -> %s | %s | %s',
                    $patient->id, $before ?? 'NULL', $updates['classification'] ?? $before ?? 'NULL',
                    $updates === [] ? 'no changes' : implode(', ', array_keys($updates)), $reason));
            } catch (Throwable $exception) {
                report($exception);
                $failed = true;
                $this->error("Patient {$patient->id}: repair failed and its transaction was rolled back. Check the application logs.");
            }
        }

        $this->info($apply ? "Repaired: {$changed}" : "Proposed repairs: {$changed}");

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    /** @return array{array<string, mixed>, string} */
    private function plan(Patient $patient, array $faculties, array $students, array $admins): array
    {
        $category = strtolower(trim((string) $patient->classification));
        if (! in_array($category, ['', 'patient', 'student', 'faculty', 'administrative', 'dependent', 'dependent_alumni'], true)) {
            return [[], 'Skipped: explicit category requires manual review'];
        }

        $email = strtolower(trim((string) $patient->email));
        $ssoId = trim((string) $patient->user?->sso_user_id);
        $facultyCode = trim((string) $patient->faculty_code);
        $studentNo = trim((string) $patient->student_no);

        $facultyMatches = array_values(array_filter($faculties, fn ($row) => is_array($row) && (
            ($email !== '' && strtolower($this->value($row, ['email']) ?? '') === $email)
            || ($ssoId !== '' && $this->value($row, ['idp_user_id']) === $ssoId)
        )));
        $studentMatches = array_values(array_filter($students, fn ($row) => is_array($row) && (
            ($email !== '' && strtolower($this->value($row, ['email', 'emailAddress', 'email_address', 'institutional_email', 'institutionalEmail']) ?? '') === $email)
            || ($studentNo !== '' && $this->value($row, ['studentNumber', 'student_number', 'studentNo', 'student_no']) === $studentNo)
        )));

        $cmsMatches = array_values(array_filter($admins, fn ($row) => is_array($row)
            && $email !== '' && strtolower($this->value($row, ['email']) ?? '') === $email));

        if (count($facultyMatches) > 1 || count($studentMatches) > 1 || count($cmsMatches) > 1) {
            return [[], 'Skipped: multiple directory matches'];
        }

        $isFaculty = $category === 'faculty' || $facultyCode !== '' || $facultyMatches !== [];
        $isStudent = $category === 'student' || $studentNo !== '' || $studentMatches !== [];
        $isCms = $category === 'administrative' || $cmsMatches !== [];
        if ($isFaculty && $isStudent) {
            return [[], 'Skipped: conflicting student and faculty evidence'];
        }
        if ($isCms && ($isFaculty || $isStudent)) {
            return [[], 'Skipped: conflicting CMS and student/faculty evidence'];
        }
        if (! $isFaculty && ! $isStudent && ! $isCms) {
            return [[], 'Skipped: no student, faculty, or CMS evidence'];
        }

        if (($facultyMatches !== [] || $studentMatches !== [] || $cmsMatches !== []) && $email !== ''
            && Patient::whereKeyNot($patient->id)->whereRaw('LOWER(TRIM(email)) = ?', [$email])->exists()) {
            return [[], 'Skipped: email belongs to multiple patient records'];
        }

        $target = $isFaculty ? 'faculty' : ($isStudent ? 'student' : 'administrative');
        if ($target === 'administrative') {
            $updates = $patient->classification !== $target ? ['classification' => $target] : [];
            $cms = $cmsMatches[0] ?? null;
            if ($cms !== null) {
                $profiles = ExternalAdminProfile::where(function ($query) use ($email, $cms) {
                    $query->whereRaw('LOWER(TRIM(email)) = ?', [$email])->orWhere('external_admin_id', $cms['admin_id']);
                })->get();
                if ($profiles->count() > 1) {
                    return [[], 'Skipped: multiple local CMS profiles match this identity'];
                }
                if ($profiles->count() === 1 && $profiles[0]->external_admin_id !== $cms['admin_id']) {
                    $updates['external_admin_id'] = $cms['admin_id'];
                }
            }

            return [$updates, $updates === [] ? 'Already consistent' : 'Matched CMS evidence'];
        }
        $profile = $isFaculty ? ($facultyMatches[0] ?? []) : ($studentMatches[0] ?? []);
        $identifier = $isFaculty ? 'faculty_code' : 'student_no';
        $incomingIdentifier = $this->value($profile, $isFaculty
            ? ['faculty_code'] : ['studentNumber', 'student_number', 'studentNo', 'student_no']);
        $savedIdentifier = $isFaculty ? $facultyCode : $studentNo;

        if ($incomingIdentifier !== null && $savedIdentifier !== '' && $incomingIdentifier !== $savedIdentifier) {
            return [[], 'Skipped: directory identifier differs from saved identifier'];
        }

        $resolvedIdentifier = $incomingIdentifier ?? ($savedIdentifier !== '' ? $savedIdentifier : null);
        $relation = $isFaculty ? 'facultyInformation' : 'studentInformation';
        if ($resolvedIdentifier !== null && Patient::whereKeyNot($patient->id)
            ->whereHas($relation, fn ($query) => $query->where($identifier, $resolvedIdentifier))->exists()) {
            return [[], 'Skipped: institutional identifier belongs to another patient'];
        }

        $updates = [];
        if ($patient->classification !== $target) {
            $updates['classification'] = $target;
        }
        if ($savedIdentifier === '' && $incomingIdentifier !== null) {
            $updates[$identifier] = $incomingIdentifier;
        }

        if ($target === 'student') {
            $fields = [
                'course_code' => ['program.code', 'programCode', 'program_code', 'course.code', 'courseCode', 'course_code'],
                'course_name' => ['program.name', 'program', 'course.name', 'course'],
                'year_level' => ['yearLevel', 'year_level'],
                'section' => ['section'],
            ];
            foreach ($fields as $field => $paths) {
                $value = $this->value($profile, $paths);
                if ($value !== null && blank($patient->{$field})) {
                    if ($field === 'year_level') {
                        if (! ctype_digit($value) || (int) $value < 1) {
                            continue;
                        }
                        $value = (int) $value;
                    }
                    $updates[$field] = $value;
                }
            }
        }

        return [$updates, $updates === [] ? 'Already consistent' : 'Matched '.$target.' evidence'];
    }

    private function value(array $row, array $paths): ?string
    {
        foreach ($paths as $path) {
            $value = data_get($row, $path);
            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return null;
    }
}
