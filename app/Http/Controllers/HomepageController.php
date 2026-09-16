<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Appointment;
use App\Models\MedicalHistory;
use App\Models\Patient;
use Carbon\Carbon;
use App\Helpers\PhilippineHolidays;
use App\Helpers\AuditLogger;
use App\Services\StudentApiService;
use App\Services\ReservedBookingInvitationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Services\AppointmentOdontogramSnapshotService;

class HomepageController extends Controller
{
    const MAX_APPOINTMENTS_PER_DAY = 5;

    public function __construct(
        private readonly StudentApiService $studentApiService,
        private readonly ReservedBookingInvitationService $reservedBookingInvitationService,
        private readonly AppointmentOdontogramSnapshotService $appointmentOdontogramSnapshotService
    ) {}

    public function index()
    {
        $patientId = session('impersonated_patient_id') ?: session('patient_id');

        if (!$patientId) {
            return redirect()->route('login')->with('error', 'Please login first!');
        }

        $patient = Patient::findOrFail($patientId);

        $this->backfillStudentProfileIfNeeded($patient);
        $patient = $patient->fresh();
        $reservedBookingReminders = $this->reservedBookingInvitationService->syncPatient($patient);
        $patient->load('medicalHistory');

        if (!session('impersonated_patient_id')) {
            $user = auth()->user();

            if ($user?->email && $patient->user_id === $user->id && $patient->email !== $user->email) {
                $patient->forceFill(['email' => $user->email])->save();
            }
        }

        AuditLogger::log(
            'view',
            'patient_dashboard',
            "Patient viewed homepage"
        );
        $appointments = Appointment::where('patient_id', $patient->id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->orderBy('appointment_date', 'asc')
            ->orderBy('appointment_time', 'asc')
            ->get();

        $today = now()->toDateString();

        $upcomingAppointment = Appointment::where('patient_id', $patient->id)
            ->whereIn('status', ['upcoming', 'rescheduled'])
            ->where('appointment_date', '>=', $today)
            ->orderBy('appointment_date', 'asc')
            ->orderBy('appointment_time', 'asc')
            ->first();

        $appointmentCountsPerDay = Appointment::whereIn('status', ['upcoming', 'rescheduled'])
            ->selectRaw('appointment_date, COUNT(*) as count')
            ->groupBy('appointment_date')
            ->pluck('count', 'appointment_date')
            ->toArray();

        $unavailableDates = [];

        $philippineHolidays = PhilippineHolidays::recordsRange(0, 4);

        $records = Appointment::with(['procedure', 'dentist'])
            ->where('patient_id', $patient->id)
            ->whereIn('status', ['completed', 'cancelled'])
            ->orderBy('appointment_date', 'desc')
            ->orderBy('appointment_time', 'desc')
            ->get();

        $previousOdontogramByAppointment =
            $this->appointmentOdontogramSnapshotService
            ->previousSnapshotsByAppointment($records);

        $notifications = [];

        return view('patient.index', compact(
            'patient',
            'appointments',
            'upcomingAppointment',
            'appointmentCountsPerDay',
            'unavailableDates',
            'philippineHolidays',
            'records',
            'notifications',
            'previousOdontogramByAppointment',
            'reservedBookingReminders'
        ));
    }

    private function backfillStudentProfileIfNeeded(Patient $patient): void
    {
        $isStudent = ! empty($patient->student_no) || (! empty($patient->email) && ! empty($patient->course_code));

        if (! $isStudent) {
            return;
        }

        $needsBackfill = blank($patient->birthdate)
            || blank($patient->gender)
            || blank($patient->address)
            || ! optional($patient->medicalHistory)->emergency_person
            || ! optional($patient->medicalHistory)->emergency_number
            || ! optional($patient->medicalHistory)->emergency_relation;

        if (! $needsBackfill) {
            return;
        }

        try {
            $studentNumber = $patient->student_no;
            $studentProfile = [];
            if (empty($studentNumber) && ! empty($patient->email)) {
                $studentProfileResponse = $this->studentApiService->getStudentByEmail($patient->email);
                $studentProfile = is_array($studentProfileResponse['data'] ?? null)
                    ? $studentProfileResponse['data']
                    : [];
            }

            $studentNumber = $studentNumber
                ?: data_get($studentProfile, 'studentNumber')
                ?: data_get($studentProfile, 'student_number');

            $personalInfo = [];
            $addresses = [];

            if (! empty($studentNumber)) {
                $personalInfoResponse = $this->studentApiService->getPersonalInfoByStudentNumber($studentNumber);
                $personalInfo = is_array($personalInfoResponse['data'] ?? null)
                    ? $personalInfoResponse['data']
                    : [];

                $addressResponse = $this->studentApiService->getAddressesByStudentNumber($studentNumber);
                $addresses = is_array($addressResponse['data'] ?? null)
                    ? $addressResponse['data']
                    : [];
            }

            $birthdate = $this->normalizeDate(
                $personalInfo['dateOfBirth']
                    ?? $personalInfo['birthdate']
                    ?? null
            );
            $gender = $this->normalizeGenderLabel(
                $personalInfo['gender']['name']
                    ?? $personalInfo['gender']
                    ?? data_get($studentProfile, 'gender.name')
                    ?? data_get($studentProfile, 'gender')
                    ?? null
            );
            $address = $this->formatStudentAddress($addresses);
            $placeOfBirth = $this->cleanStringValue($personalInfo['placeOfBirth'] ?? null);
            $heightM = $this->normalizeNullableFloat($personalInfo['heightM'] ?? null);
            $weightKg = $this->normalizeNullableFloat($personalInfo['weightKg'] ?? null);

            $patient->birthdate = $patient->birthdate ?: $birthdate;
            $patient->gender = $patient->gender ?: $gender;
            $patient->address = $patient->address ?: $address;

            if (Schema::hasColumns('patients', ['place_of_birth', 'height_m', 'weight_kg'])) {
                $patient->place_of_birth = $patient->place_of_birth ?: $placeOfBirth;
                $patient->height_m = $patient->height_m ?? $heightM;
                $patient->weight_kg = $patient->weight_kg ?? $weightKg;
            }

            if ($patient->isDirty()) {
                $patient->save();
            }

            $this->syncStudentMedicalHistory($patient, $personalInfo);

            $user = auth()->user();

            if ($user && $patient->user_id === $user->id) {
                $user->birthdate = $user->birthdate ?: $patient->birthdate;
                $user->gender = $user->gender ?: $patient->gender;

                if ($user->isDirty()) {
                    $user->save();
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Homepage student profile backfill failed', [
                'patient_id' => $patient->id,
                'student_no' => $patient->student_no,
                'email' => $patient->email,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function syncStudentMedicalHistory(Patient $patient, array $personalInfo): void
    {
        $emergencyPerson = $this->cleanStringValue(
            $personalInfo['emergencyContactName']
                ?? $personalInfo['emergency_contact_name']
                ?? data_get($personalInfo, 'emergencyContact.name')
                ?? data_get($personalInfo, 'emergency_contact.name')
                ?? data_get($personalInfo, 'emergency_contact.contact_name')
                ?? data_get($personalInfo, 'emergencyContact.contactName')
                ?? null
        );
        $emergencyNumber = $this->normalizePhilippineMobile(
            $this->cleanStringValue(
                $personalInfo['emergencyContactNumber']
                    ?? $personalInfo['emergency_contact_number']
                    ?? data_get($personalInfo, 'emergencyContact.number')
                    ?? data_get($personalInfo, 'emergencyContact.contactNumber')
                    ?? data_get($personalInfo, 'emergency_contact.number')
                    ?? data_get($personalInfo, 'emergency_contact.contact_number')
                    ?? null
            )
        );
        $emergencyRelation = $this->normalizeEmergencyRelation(
            $this->cleanStringValue(
                $personalInfo['emergencyContactRelationship']
                    ?? $personalInfo['emergency_contact_relationship']
                    ?? $personalInfo['emergencyContactRelation']
                    ?? $personalInfo['emergency_contact_relation']
                    ?? data_get($personalInfo, 'emergencyContact.relationship')
                    ?? data_get($personalInfo, 'emergencyContact.relation')
                    ?? data_get($personalInfo, 'emergency_contact.relationship')
                    ?? data_get($personalInfo, 'emergency_contact.relation')
                    ?? data_get($personalInfo, 'emergency_contact.relationship_name')
                    ?? data_get($personalInfo, 'emergencyContact.relationshipName')
                    ?? data_get($personalInfo, 'emergencyContactRelationship.name')
                    ?? data_get($personalInfo, 'emergency_contact_relationship.name')
                    ?? null
            )
        );

        if (! $emergencyPerson && ! $emergencyNumber && ! $emergencyRelation) {
            return;
        }

        $medicalHistory = MedicalHistory::firstOrNew(['patient_id' => $patient->id]);
        $currentRelation = strtolower(trim((string) ($medicalHistory->emergency_relation ?? '')));
        $hasPlaceholderRelation = in_array($currentRelation, ['', 'not specified', '(not specified)', 'n/a', 'na'], true);

        if ($emergencyPerson && empty($medicalHistory->emergency_person)) {
            $medicalHistory->emergency_person = $emergencyPerson;
        }

        if ($emergencyNumber && empty($medicalHistory->emergency_number)) {
            $medicalHistory->emergency_number = $emergencyNumber;
        }

        if ($emergencyRelation && $hasPlaceholderRelation) {
            $medicalHistory->emergency_relation = $emergencyRelation;
        }

        if (! $medicalHistory->exists && empty($medicalHistory->emergency_relation)) {
            $medicalHistory->emergency_relation = 'Not specified';
        }

        if ($medicalHistory->isDirty()) {
            $medicalHistory->save();
        }
    }

    private function normalizeDate(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeGenderLabel(?string $value): ?string
    {
        $gender = strtolower(trim((string) $value));

        if ($gender === '') {
            return null;
        }

        if (str_starts_with($gender, 'm')) {
            return 'Male';
        }

        if (str_starts_with($gender, 'f')) {
            return 'Female';
        }

        return $value;
    }

    private function normalizeNullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function cleanStringValue(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $cleaned = trim((string) $value);

        return $cleaned !== '' ? $cleaned : null;
    }

    private function normalizePhilippineMobile(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '63') && strlen($digits) === 12) {
            $digits = '0' . substr($digits, 2);
        } elseif (str_starts_with($digits, '9') && strlen($digits) === 10) {
            $digits = '0' . $digits;
        }

        if (! preg_match('/^09\d{9}$/', $digits)) {
            return null;
        }

        return $digits;
    }

    private function normalizeEmergencyRelation(?string $value): ?string
    {
        $normalized = strtolower(trim((string) $value));

        return match ($normalized) {
            'father', 'dad', 'papa', 'tatay' => 'Father',
            'mother', 'mom', 'mama', 'nanay' => 'Mother',
            'uncle', 'tiyo', 'tito' => 'Uncle',
            'aunt', 'auntie', 'tiya', 'tita' => 'Auntie',
            'brother', 'kuya' => 'Brother',
            'sister', 'ate' => 'Sister',
            'grandmother', 'lola' => 'Grandmother',
            'grandfather', 'lolo' => 'Grandfather',
            'cousin', 'pinsan' => 'Cousin',
            'guardian', 'legal guardian' => 'Legal Guardian',
            'friend', 'kaibigan' => 'Friend',
            'other relative', 'relative', 'kamag-anak', 'kamaganak' => 'Other Relative',
            default => null,
        };
    }

    private function formatStudentAddress(array $addresses): ?string
    {
        if ($addresses === []) {
            return null;
        }

        $preferredAddress = collect($addresses)->first(function ($address) {
            $type = strtolower(trim((string) data_get($address, 'addressType')));

            return in_array($type, ['current', 'present', 'home', 'permanent'], true);
        }) ?? $addresses[0];

        $parts = array_filter([
            $this->cleanStringValue(data_get($preferredAddress, 'streetDetail.string'))
                ?: $this->cleanStringValue(data_get($preferredAddress, 'streetDetail')),
            $this->cleanStringValue(data_get($preferredAddress, 'barangay.name')),
            $this->cleanStringValue(data_get($preferredAddress, 'city.name')),
            $this->cleanStringValue(data_get($preferredAddress, 'province.name.string'))
                ?: $this->cleanStringValue(data_get($preferredAddress, 'province.name')),
            $this->cleanStringValue(data_get($preferredAddress, 'region.name')),
        ]);

        return $parts === [] ? null : implode(', ', array_values($parts));
    }
}
