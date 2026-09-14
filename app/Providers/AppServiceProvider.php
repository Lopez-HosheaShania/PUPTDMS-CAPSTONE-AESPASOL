<?php

namespace App\Providers;

use App\Helpers\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    private const AUDIT_EXCLUDED_MODELS = [
        \App\Models\AiServiceLog::class,
        \App\Models\AuditLog::class,
        \App\Models\AuditLogActor::class,
        \App\Models\AuditLogRequest::class,
        \App\Models\AuditLogDescription::class,
        \App\Models\Backup::class,
        \App\Models\Inventory::class,
    ];

    private const AUDIT_SENSITIVE_FIELDS = [
        'password',
        'remember_token',
        'access_token',
        'refresh_token',
    ];

    private const AUDIT_MODULE_LABELS = [
        \App\Models\AcademicPeriod::class => 'Academic Periods',
        \App\Models\Appointment::class => 'Appointments',
        \App\Models\AppointmentReservedBooking::class => 'Appointments',
        \App\Models\AppointmentFollowUp::class => 'Appointments',
        \App\Models\AppointmentTransfer::class => 'Appointments',
        \App\Models\AppointmentReminder::class => 'Appointments',
        \App\Models\AppointmentDraft::class => 'Appointments',
        \App\Models\AppointmentProcedure::class => 'Appointments',
        \App\Models\AppointmentProcedureTiming::class => 'Appointments',
        \App\Models\AppointmentOdontogram::class => 'Appointments',
        \App\Models\BlockedDate::class => 'Clinic Schedule',
        \App\Models\ClinicSchedule::class => 'Clinic Schedule',
        \App\Models\DentalHistory::class => 'Dental Records',
        \App\Models\DentalHistoryAnswer::class => 'Dental Records',
        \App\Models\DentalHistoryConcern::class => 'Dental Records',
        \App\Models\DentalHistoryCondition::class => 'Dental Records',
        \App\Models\DentalHistoryConditionDate::class => 'Dental Records',
        \App\Models\DentistTransition::class => 'Dentist Continuity',
        \App\Models\DentistTransitionDetail::class => 'Dentist Continuity',
        \App\Models\DentistTransitionCancellation::class => 'Dentist Continuity',
        \App\Models\DentistTransitionItemResolution::class => 'Dentist Continuity',
        \App\Models\DentistTransitionItemAssignment::class => 'Dentist Continuity',
        \App\Models\DentistTransitionItemState::class => 'Dentist Continuity',
        \App\Models\DentistTransitionChecklistItem::class => 'Dentist Continuity',
        \App\Models\DentistTransitionItem::class => 'Dentist Continuity',
        \App\Models\DocumentRequest::class => 'Document Requests',
        \App\Models\DocumentRequestReview::class => 'Document Requests',
        \App\Models\DocumentRequestState::class => 'Document Requests',
        \App\Models\DocumentTemplate::class => 'Document Templates',
        \App\Models\DocumentTemplateField::class => 'Document Templates',
        \App\Models\ExternalAdminAccess::class => 'Assign CMS Access',
        \App\Models\ExternalAdminProfile::class => 'Assign CMS Access',
        \App\Models\Faculty::class => 'Faculty Integration',
        \App\Models\FacultyProfile::class => 'Faculty Integration',
        \App\Models\Inventory::class => 'Inventory',
        \App\Models\MedicalHistory::class => 'Patients',
        \App\Models\MedicalHistoryAnswer::class => 'Patients',
        \App\Models\MedicalHistoryDiseaseAnswer::class => 'Patients',
        \App\Models\MedicalHistoryQuestion::class => 'Patients',
        \App\Models\Patient::class => 'Patients',
        \App\Models\PatientOdontogram::class => 'Dental Records',
        \App\Models\Permission::class => 'Roles & Permissions',
        \App\Models\Role::class => 'Roles & Permissions',
        \App\Models\ServiceType::class => 'System Settings',
        \App\Models\SystemSetting::class => 'System Settings',
        \App\Models\Tooth::class => 'Dental Records',
        \App\Models\ToothLegend::class => 'Dental Records',
        \App\Models\ToothSurface::class => 'Dental Records',
        \App\Models\User::class => 'User Management',
        \App\Models\UserDeactivationEmployment::class => 'User Management',
        \App\Models\UserDeactivationAccess::class => 'User Management',
    ];

    public function register(): void
    {
        //
    }

        public function boot(): void
    {
        try {
            $timezone = setting('timezone', 'Asia/Manila (UTC+8)');
            $clinicName = setting('clinic_name', 'PUP Taguig Dental Clinic');
            $language = setting('language', 'English (US)');

            $timezoneMap = [
                'Asia/Manila (UTC+8)' => 'Asia/Manila',
                'UTC' => 'UTC',
            ];

            $localeMap = [
                'English (US)' => 'en',
                'Filipino' => 'fil',
            ];

            $actualTimezone = $timezoneMap[$timezone] ?? 'Asia/Manila';
            $actualLocale = $localeMap[$language] ?? 'en';

            config([
                'app.timezone' => $actualTimezone,
                'app.name' => $clinicName,
                'app.locale' => $actualLocale,
            ]);

            app()->setLocale($actualLocale);
            date_default_timezone_set($actualTimezone);

            View::share('globalClinicName', $clinicName);
        } catch (\Throwable $e) {
            //
        }

        $this->registerAuditableModelObservers();
    }

    private function registerAuditableModelObservers(): void
    {
        foreach (glob(app_path('Models/*.php')) ?: [] as $path) {
            $class = 'App\\Models\\' . pathinfo($path, PATHINFO_FILENAME);

            if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
                continue;
            }

            $this->registerModelAuditHooks($class);
        }
    }

    private function registerModelAuditHooks(string $class): void
    {
        $class::created(function (Model $model): void {
            if (! $this->shouldAuditModel($model)) {
                return;
            }

            AuditLogger::log(
                'create',
                $this->resolveAuditModule($model),
                $this->buildAuditDescription($model, 'created')
            );
        });

        $class::updated(function (Model $model): void {
            if (! $this->shouldAuditModel($model)) {
                return;
            }

            $changedFields = $this->resolveChangedAuditFields($model);

            if ($changedFields === []) {
                return;
            }

            AuditLogger::log(
                'update',
                $this->resolveAuditModule($model),
                $this->buildAuditDescription($model, 'updated', $changedFields)
            );
        });

        $class::deleted(function (Model $model): void {
            if (! $this->shouldAuditModel($model)) {
                return;
            }

            AuditLogger::log(
                'delete',
                $this->resolveAuditModule($model),
                $this->buildAuditDescription($model, 'deleted')
            );
        });
    }

    private function shouldAuditModel(Model $model): bool
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return false;
        }

        if (in_array($model::class, self::AUDIT_EXCLUDED_MODELS, true)) {
            return false;
        }

        if (! app()->bound('request')) {
            return false;
        }

        if (! Auth::check() && ! session()->has('role') && ! session()->has('admin_logged_in')) {
            return false;
        }

        return true;
    }

    private function resolveAuditModule(Model $model): string
    {
        return self::AUDIT_MODULE_LABELS[$model::class]
            ?? Str::headline(Str::pluralStudly(class_basename($model)));
    }

    private function buildAuditDescription(Model $model, string $verb, array $changedFields = []): string
    {
        $entity = Str::headline(class_basename($model));
        $identifier = $model->getKey();
        $label = $this->resolveAuditDisplayLabel($model);

        $description = "{$entity} #{$identifier}";

        if ($label !== null) {
            $description .= " ({$label})";
        }

        $description .= " {$verb}";

        if ($changedFields !== []) {
            $description .= ' fields: ' . implode(', ', $changedFields);
        }

        return $description . '.';
    }

    private function resolveChangedAuditFields(Model $model): array
    {
        return collect(array_keys($model->getChanges()))
            ->reject(fn(string $field) => in_array($field, ['updated_at'], true))
            ->reject(fn(string $field) => in_array($field, self::AUDIT_SENSITIVE_FIELDS, true))
            ->values()
            ->all();
    }

    private function resolveAuditDisplayLabel(Model $model): ?string
    {
        foreach (['name', 'title', 'email', 'slug', 'code'] as $field) {
            $value = $model->getAttribute($field);

            if (filled($value)) {
                return Str::limit((string) $value, 80);
            }
        }

        return null;
    }
}
