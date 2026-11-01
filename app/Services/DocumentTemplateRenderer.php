<?php

namespace App\Services;

use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateField;
use App\Models\Inventory;
use App\Models\Appointment;
use App\Models\DocumentRequest;
use App\Models\User;
use App\Support\DentistName;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DocumentTemplateRenderer
{
    public function render(DocumentTemplate $template, array $context = []): string
    {
        $content = (string) $template->content;

        if ($content === '') {
            return '';
        }

        $context = $this->buildTemplateContext($template, $context);

        $fields = DocumentTemplateField::query()
            ->where('document_type', $template->document_type)
            ->get()
            ->keyBy('field_key');

        $replacementCount = 0;
        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', function (array $matches) use ($fields, $context, $content) {
            $fieldKey = $matches[1][0];
            $field = $fields->get($fieldKey);

            $value = $this->resolveValue($fieldKey, $field, $context);

            if (in_array($fieldKey, ['dentist_name', 'submitted_by', 'noted_by'], true)) {
                $offset = $matches[0][1];
                $before = html_entity_decode(strip_tags(substr($content, max(0, $offset - 512), min(512, $offset))), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $after = html_entity_decode(strip_tags(substr($content, $offset + strlen($matches[0][0]), 512)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $hasPrefix = preg_match('/(?:^|[^\p{L}])Dr\.?\s*$/iu', $before) === 1;
                $hasSuffix = preg_match('/^\s*,?\s*(?:D\.?\s*M\.?\s*D\.?|M\.?\s*D\.?)(?!\p{L})/iu', $after) === 1;
                $value = implode('; ', array_map(
                    fn (string $name) => ReportSignatory::dentistNameForReport($name, ! $hasPrefix, ! $hasSuffix),
                    explode('; ', (string) $value),
                ));
            }

            return e((string) $value);
        }, $content, -1, $replacementCount, PREG_OFFSET_CAPTURE) ?? $content;
    }

    public function renderForPreview(DocumentTemplate $template): string
    {
        return $this->render($template, []);
    }

    private function buildTemplateContext(DocumentTemplate $template, array $context): array
    {
        $actor = auth()->user();
        $signatories = app(ReportSignatory::class);
        $preparer = $signatories->currentPreparerProfile();
        $profile = match (true) {
            ($context['document_request'] ?? null) instanceof DocumentRequest => $signatories->documentProfile($context['document_request']),
            ($context['appointment'] ?? null) instanceof Appointment => $signatories->profileForDentist(DentistName::clinicianForAppointment($context['appointment'])),
            ($context['dentist'] ?? null) instanceof User => $signatories->profileForDentist($context['dentist']),
            default => $signatories->currentProfile(),
        };
        $dentistName = match (true) {
            ($context['document_request'] ?? null) instanceof DocumentRequest => DentistName::forDocumentRequest($context['document_request']),
            ($context['appointment'] ?? null) instanceof Appointment => DentistName::forAppointment($context['appointment']),
            ($context['dentist'] ?? null) instanceof User => DentistName::forUser($context['dentist']),
            default => $this->reportDentists($template, $context),
        };
        $dentistName = $profile?->name ?? $dentistName;
        $dentistName = implode('; ', array_map([ReportSignatory::class, 'dentistNameForReport'], explode('; ', $dentistName)));
        $signature = $profile?->signatureDataUri() ?? '';
        $context = array_merge([
            'dentist_name' => $dentistName,
            'submitted_by' => $dentistName,
            'noted_by' => $dentistName,
            'prepared_by' => $preparer?->name ?? DentistName::forUser($actor),
            'prepared_by_role' => $preparer ? ReportSignatory::preparerLabel($preparer->user) : ($actor?->display_role_name ?? ''),
            'dentist_signature' => $signature,
            'submitted_by_signature' => $signature,
            'noted_by_signature' => $signature,
            'prepared_by_signature' => $preparer?->signatureDataUri() ?? '',
            'lic_no' => $profile?->license_number ?? '',
            'dentist_license_no' => $profile?->license_number ?? '',
        ], $context);

        if ($template->document_type === 'dental_cases') {
            return array_merge($this->buildDentalCasesContext(), $context);
        }

        if ($template->document_type === 'gad_report') {
            return array_merge($this->buildGadReportContext($context), $context);
        }

        if ($template->document_type === 'dental_supplies_inventory') {
            return array_merge($this->buildDentalSuppliesInventoryContext(), $context);
        }

        if ($template->document_type === 'medicine_inventory') {
            return array_merge($this->buildMedicineInventoryContext(), $context);
        }

        if ($template->document_type === 'daily_treatment_record') {
            return array_merge($this->buildDailyTreatmentRecordContext(), $context);
        }

        if ($template->document_type === 'dental_services') {
            return array_merge($this->buildDentalServicesContext(), $context);
        }

        return $context;
    }

    private function reportDentists(DocumentTemplate $template, array $context): string
    {
        if (isset($context['records']) && is_iterable($context['records'])) {
            return implode('; ', DentistName::forRecords($context['records']));
        }

        if (! in_array($template->document_type, ['dental_cases', 'gad_report', 'daily_treatment_record', 'dental_services'], true)) {
            return '';
        }

        [$from, $to] = $template->document_type === 'gad_report'
            ? $this->resolveGadReportRange($context)
            : [now()->startOfMonth(), now()->endOfMonth()];
        $records = app(AppointmentReportRecords::class)->between($from, $to);
        $records = match ($template->document_type) {
            'daily_treatment_record' => $records->take(12),
            'dental_services' => $records->take(15),
            default => $records,
        };

        return implode('; ', DentistName::forRecords($records));
    }

    private function buildDentalCasesContext(): array
    {
        $currentMonth = now();
        $clinicName = setting('clinic_name', 'Taguig Dental Clinic');

        $records = app(AppointmentReportRecords::class)->between($currentMonth->copy()->startOfMonth(), $currentMonth->copy()->endOfMonth())
            ->filter(fn ($row) => filled($row->diagnosis))
            ->groupBy(fn ($row) => json_encode([$row->office_type, $row->diagnosis]))
            ->map(fn ($rows) => (object) ['office_type' => $rows->first()->office_type,
                'diagnosis' => $rows->first()->diagnosis, 'total' => $rows->sum('dental_case_teeth_count')])
            ->sortByDesc('total')->values();

        $groups = [
            'students' => [
                'filter' => fn ($row) => $row->office_type === null || $row->office_type === '' || strtolower((string) $row->office_type) === 'student',
                'diagnosis' => [],
                'cases' => [],
            ],
            'faculty' => [
                'filter' => fn ($row) => strtolower((string) $row->office_type) === 'faculty',
                'diagnosis' => [],
                'cases' => [],
            ],
            'admin' => [
                'filter' => fn ($row) => strtolower((string) $row->office_type) === 'administrative',
                'diagnosis' => [],
                'cases' => [],
            ],
            'dependents' => [
                'filter' => fn ($row) => strtolower((string) $row->office_type) === 'dependent',
                'diagnosis' => [],
                'cases' => [],
            ],
        ];

        foreach ($groups as $groupKey => &$group) {
            $groupRows = $records->filter($group['filter'])->take(3)->values();

            for ($i = 0; $i < 3; $i++) {
                $row = $groupRows->get($i);
                $slot = $i + 1;

                $group['diagnosis'][$slot] = $row?->diagnosis ?? '—';
                $group['cases'][$slot] = $row ? (string) $row->total : '—';
            }
        }
        unset($group);

        return [
            'report_month' => strtoupper($currentMonth->format('F Y')),
            'clinic_name' => strtoupper($clinicName),
            'students_diagnosis_1' => $groups['students']['diagnosis'][1],
            'students_diagnosis_2' => $groups['students']['diagnosis'][2],
            'students_diagnosis_3' => $groups['students']['diagnosis'][3],
            'students_cases_1' => $groups['students']['cases'][1],
            'students_cases_2' => $groups['students']['cases'][2],
            'students_cases_3' => $groups['students']['cases'][3],
            'faculty_diagnosis_1' => $groups['faculty']['diagnosis'][1],
            'faculty_diagnosis_2' => $groups['faculty']['diagnosis'][2],
            'faculty_diagnosis_3' => $groups['faculty']['diagnosis'][3],
            'faculty_cases_1' => $groups['faculty']['cases'][1],
            'faculty_cases_2' => $groups['faculty']['cases'][2],
            'faculty_cases_3' => $groups['faculty']['cases'][3],
            'admin_diagnosis_1' => $groups['admin']['diagnosis'][1],
            'admin_diagnosis_2' => $groups['admin']['diagnosis'][2],
            'admin_diagnosis_3' => $groups['admin']['diagnosis'][3],
            'admin_cases_1' => $groups['admin']['cases'][1],
            'admin_cases_2' => $groups['admin']['cases'][2],
            'admin_cases_3' => $groups['admin']['cases'][3],
            'dependents_diagnosis_1' => $groups['dependents']['diagnosis'][1],
            'dependents_diagnosis_2' => $groups['dependents']['diagnosis'][2],
            'dependents_diagnosis_3' => $groups['dependents']['diagnosis'][3],
            'dependents_cases_1' => $groups['dependents']['cases'][1],
            'dependents_cases_2' => $groups['dependents']['cases'][2],
            'dependents_cases_3' => $groups['dependents']['cases'][3],
        ];
    }

    private function buildGadReportContext(array $context = []): array
    {
        [$from, $to] = $this->resolveGadReportRange($context);
        $monthYear = $this->formatGadReportPeriod($from, $to);
        $campusName = strtoupper(setting('clinic_name', 'Taguig Dental Clinic'));

        $records = app(AppointmentReportRecords::class)->between($from, $to);

        $departmentLabels = [
            'students' => ['student'],
            'faculty' => ['faculty'],
            'administrative' => ['administrative'],
            'dependent' => ['dependent'],
        ];

        $countByDepartmentAndGender = function (string $gender, callable $filter) use ($records, $departmentLabels): array {
            $counts = [];

            foreach ($departmentLabels as $key => $labels) {
                $counts[$key] = (int) $records->filter(function ($record) use ($gender, $filter, $labels) {
                    $department = strtolower(trim((string) $record->department));

                    return in_array($department, $labels, true)
                        && strtolower((string) $record->gender) === $gender
                        && $filter($record);
                })->count();
            }

            return $counts;
        };

        $groupFilters = [
            fn ($record) => !((bool) $record->is_senior) && !((bool) $record->is_pwd),
            fn ($record) => (bool) $record->is_senior,
            fn ($record) => (bool) $record->is_pwd,
        ];

        $maleRows = [];
        $femaleRows = [];
        foreach ($groupFilters as $index => $filter) {
            $maleRows[$index] = $countByDepartmentAndGender('male', $filter);
            $femaleRows[$index] = $countByDepartmentAndGender('female', $filter);
        }

        $headers = [];
        foreach ($departmentLabels as $key => $labels) {
            $headers[$key] = (int) $records->filter(function ($record) use ($labels) {
                return in_array(strtolower(trim((string) $record->department)), $labels, true);
            })->count();
        }

        $headers['total'] = (int) $records->count();

        $rowTotals = function (array $rows, int $index): ?string {
            $total = array_sum([
                $rows[$index]['students'] ?? 0,
                $rows[$index]['faculty'] ?? 0,
                $rows[$index]['administrative'] ?? 0,
                $rows[$index]['dependent'] ?? 0,
            ]);

            return $this->formatGadCount($total);
        };

        $grandTotal = (int) $records->count();

        return [
            'pup_logo' => asset('images/PUP.png'),
            'bagong_pilipinas_logo' => asset('images/bagong-pilipinas.png'),
            'iso_logo' => asset('images/iso9001.png'),
            'iab_logo' => asset('images/iso9001.png'),
            'report_month_year' => $monthYear,
            'campus_name' => $campusName,
            'header_students' => $this->formatGadCount($headers['students'] ?? 0),
            'header_faculty' => $this->formatGadCount($headers['faculty'] ?? 0),
            'header_administrative' => $this->formatGadCount($headers['administrative'] ?? 0),
            'header_dependent' => $this->formatGadCount($headers['dependent'] ?? 0),
            'header_total' => $this->formatGadCount($grandTotal),
            'gad_category_1' => '—',
            'gad_category_2' => 'Senior Citizen',
            'gad_category_3' => 'PWD',
            'cat1_male_students' => $this->formatGadCount($maleRows[0]['students'] ?? 0),
            'cat1_male_faculty' => $this->formatGadCount($maleRows[0]['faculty'] ?? 0),
            'cat1_male_administrative' => $this->formatGadCount($maleRows[0]['administrative'] ?? 0),
            'cat1_male_dependent' => $this->formatGadCount($maleRows[0]['dependent'] ?? 0),
            'cat1_male_total' => $rowTotals($maleRows, 0),
            'cat1_female_students' => $this->formatGadCount($femaleRows[0]['students'] ?? 0),
            'cat1_female_faculty' => $this->formatGadCount($femaleRows[0]['faculty'] ?? 0),
            'cat1_female_administrative' => $this->formatGadCount($femaleRows[0]['administrative'] ?? 0),
            'cat1_female_dependent' => $this->formatGadCount($femaleRows[0]['dependent'] ?? 0),
            'cat1_female_total' => $rowTotals($femaleRows, 0),
            'cat2_male_students' => $this->formatGadCount($maleRows[1]['students'] ?? 0),
            'cat2_male_faculty' => $this->formatGadCount($maleRows[1]['faculty'] ?? 0),
            'cat2_male_administrative' => $this->formatGadCount($maleRows[1]['administrative'] ?? 0),
            'cat2_male_dependent' => $this->formatGadCount($maleRows[1]['dependent'] ?? 0),
            'cat2_male_total' => $rowTotals($maleRows, 1),
            'cat2_female_students' => $this->formatGadCount($femaleRows[1]['students'] ?? 0),
            'cat2_female_faculty' => $this->formatGadCount($femaleRows[1]['faculty'] ?? 0),
            'cat2_female_administrative' => $this->formatGadCount($femaleRows[1]['administrative'] ?? 0),
            'cat2_female_dependent' => $this->formatGadCount($femaleRows[1]['dependent'] ?? 0),
            'cat2_female_total' => $rowTotals($femaleRows, 1),
            'cat3_male_students' => $this->formatGadCount($maleRows[2]['students'] ?? 0),
            'cat3_male_faculty' => $this->formatGadCount($maleRows[2]['faculty'] ?? 0),
            'cat3_male_administrative' => $this->formatGadCount($maleRows[2]['administrative'] ?? 0),
            'cat3_male_dependent' => $this->formatGadCount($maleRows[2]['dependent'] ?? 0),
            'cat3_male_total' => $rowTotals($maleRows, 2),
            'cat3_female_students' => $this->formatGadCount($femaleRows[2]['students'] ?? 0),
            'cat3_female_faculty' => $this->formatGadCount($femaleRows[2]['faculty'] ?? 0),
            'cat3_female_administrative' => $this->formatGadCount($femaleRows[2]['administrative'] ?? 0),
            'cat3_female_dependent' => $this->formatGadCount($femaleRows[2]['dependent'] ?? 0),
            'cat3_female_total' => $rowTotals($femaleRows, 2),
            'total_students' => $this->formatGadCount($headers['students'] ?? 0),
            'total_faculty' => $this->formatGadCount($headers['faculty'] ?? 0),
            'total_administrative' => $this->formatGadCount($headers['administrative'] ?? 0),
            'total_dependent' => $this->formatGadCount($headers['dependent'] ?? 0),
            'grand_total' => $this->formatGadCount($grandTotal),
            'prepared_by_signature' => '',
            'prepared_by' => '',
            'prepared_by_role' => 'Dental Aide',
            'submitted_by_signature' => '',
            'submitted_by' => '',
            'submitted_by_role' => 'Dentist',
        ];
    }

    private function resolveGadReportRange(array $context): array
    {
        $fromInput = $context['date_from'] ?? request()->input('date_from');
        $toInput = $context['date_to'] ?? request()->input('date_to');

        $from = $fromInput
            ? Carbon::parse((string) $fromInput)->startOfDay()
            : now()->startOfMonth();

        $to = $toInput
            ? Carbon::parse((string) $toInput)->endOfDay()
            : ($fromInput ? Carbon::parse((string) $fromInput)->endOfDay() : now()->endOfMonth());

        if ($to->lt($from)) {
            $to = $from->copy()->endOfDay();
        }

        return [$from, $to];
    }

    private function formatGadReportPeriod(Carbon $from, Carbon $to): string
    {
        if ($from->isSameDay($to)) {
            return strtoupper($from->format('F Y'));
        }

        if ($from->year === $to->year) {
            if ($from->month === $to->month) {
                return strtoupper($from->format('F Y'));
            }

            return strtoupper($from->format('F') . ' TO ' . $to->format('F Y'));
        }

        return strtoupper($from->format('F Y') . ' TO ' . $to->format('F Y'));
    }

    private function formatGadCount(int $value): string
    {
        return $value > 0 ? (string) $value : '';
    }

    private function buildDentalSuppliesInventoryContext(): array
    {
        $currentMonth = now();
        $clinicName = setting('clinic_name', 'Taguig Dental Clinic');

        $records = Inventory::query()
            ->whereYear('date_received', $currentMonth->year)
            ->whereMonth('date_received', $currentMonth->month)
            ->orderByDesc('date_received')
            ->orderByDesc('id')
            ->take(35)
            ->get()
            ->values();

        $context = [
            'pup_logo' => asset('images/PUP.png'),
            'bagong_pilipinas_logo' => asset('images/bagong-pilipinas.png'),
            'report_month_year' => strtoupper($currentMonth->format('F Y')),
            'clinic_name' => strtoupper($clinicName),
        ];

        for ($i = 1; $i <= 35; $i++) {
            $record = $records->get($i - 1);

            $context['date_received_' . $i] = $record?->formatted_date ?? '—';
            $context['stock_number_' . $i] = $record?->stock_no ?? '—';
            $context['supply_name_' . $i] = $record?->name ?? '—';
            $context['unit_' . $i] = $record?->unit ?? '—';
            $context['quantity_' . $i] = $record ? (string) $record->qty : '—';
            $context['consumed_' . $i] = $record ? (string) $record->used : '—';
            $context['balance_' . $i] = $record ? (string) ($record->qty - $record->used) : '—';
        }

        return $context;
    }

    private function buildMedicineInventoryContext(): array
    {
        $currentMonth = now();
        $clinicName = setting('clinic_name', 'Taguig Dental Clinic');

        $records = Inventory::query()
            ->where('category', 'Medicine')
            ->whereYear('date_received', $currentMonth->year)
            ->whereMonth('date_received', $currentMonth->month)
            ->orderByDesc('date_received')
            ->orderByDesc('id')
            ->take(35)
            ->get()
            ->values();

        $context = [
            'pup_logo' => asset('images/PUP.png'),
            'bagong_pilipinas_logo' => asset('images/bagong-pilipinas.png'),
            'report_month_year' => strtoupper($currentMonth->format('F Y')),
            'clinic_name' => strtoupper($clinicName),
        ];

        for ($i = 1; $i <= 35; $i++) {
            $record = $records->get($i - 1);

            $context['date_received_' . $i] = $record?->formatted_date ?? '—';
            $context['stock_number_' . $i] = $record?->stock_no ?? '—';
            $context['supply_name_' . $i] = $record?->name ?? '—';
            $context['unit_' . $i] = $record?->unit ?? '—';
            $context['quantity_' . $i] = $record ? (string) $record->qty : '—';
            $context['consumed_' . $i] = $record ? (string) $record->used : '—';
            $context['balance_' . $i] = $record ? (string) ($record->qty - $record->used) : '—';
        }

        return $context;
    }

    private function buildDailyTreatmentRecordContext(): array
    {
        $currentMonth = now();
        $clinicName = setting('clinic_name', 'Taguig Dental Clinic');

        $records = app(AppointmentReportRecords::class)->between($currentMonth->copy()->startOfMonth(), $currentMonth->copy()->endOfMonth())->take(12)->values();

        $context = [
            'pup_logo' => asset('images/PUP.png'),
            'bagong_pilipinas_logo' => asset('images/bagong-pilipinas.png'),
            'report_month_year' => strtoupper($currentMonth->format('F Y')),
            'clinic_name' => strtoupper($clinicName),
            'prepared_by_signature' => '',
            'prepared_by' => '',
            'prepared_by_role' => 'Dental Aide',
            'noted_by_signature' => '',
            'noted_by' => '',
            'noted_by_role' => 'Dentist',
        ];

        for ($i = 1; $i <= 12; $i++) {
            $record = $records->get($i - 1);

            $contact = trim(implode(' / ', array_filter([
                $record?->patient_email,
                $record?->patient_phone,
            ], fn ($value) => is_string($value) && trim($value) !== '')));

            $officeOrProgram = $record?->office_type ?: ($record?->program_code ?: '—');

            $context['row_' . $i . '_date'] = $record?->treatment_date?->format('m/d/y') ?? '—';
            $context['row_' . $i . '_patient_name'] = $record?->patient_name ?? '—';
            $context['row_' . $i . '_contact'] = $contact !== '' ? $contact : '—';
            $context['row_' . $i . '_office'] = $officeOrProgram;
            $context['row_' . $i . '_gender'] = $record?->gender ?? '—';
            $context['row_' . $i . '_treatment'] = $record?->treatment_done ?? '—';
            $context['row_' . $i . '_processed'] = $record?->updated_at?->format('m/d/y g:i A') ?? '—';
            $context['row_' . $i . '_minutes'] = $record ? (string) $record->minutes_processed : '—';
            $context['row_' . $i . '_signature'] = $record?->has_signature ? '✓' : '—';
        }

        return $context;
    }

    private function buildDentalServicesContext(): array
    {
        $currentMonth = now();

        $records = app(AppointmentReportRecords::class)->between($currentMonth->copy()->startOfMonth(), $currentMonth->copy()->endOfMonth())->take(15)->values();

        $context = [
            'pup_logo' => asset('images/PUP.png'),
            'bagong_pilipinas_logo' => asset('images/bagong-pilipinas.png'),
            'form_code' => 'PUP-TDR-F-MEDS-002',
            'revision_no' => '0',
            'revision_date' => 'April 11, 2025',
        ];

        for ($i = 1; $i <= 15; $i++) {
            $record = $records->get($i - 1);

            $name = $record?->dental_services_patient_name ?? '—';

            $courseSectionDepartment = '—';
            if ($record) {
                if (($record->department ?? '') === 'Student') {
                    $courseSectionDepartment = trim(implode(' ', array_filter([
                        $record->program_code,
                        $record->year_level,
                        $record->section ? '-' . $record->section : null,
                    ], fn ($value) => $value !== null && $value !== '')));
                } else {
                    $courseSectionDepartment = (string) ($record->department ?? '—');
                }

                if ($courseSectionDepartment === '') {
                    $courseSectionDepartment = '—';
                }
            }

            $processingTime = '—';
            if ($record?->dental_services_minutes_processed !== null) {
                $processingTime = (string) $record->dental_services_minutes_processed;
            }

            $context['date_' . $i] = $record?->time_in?->format('m/d/y') ?? '—';
            $context['time_in_' . $i] = $record?->dental_services_time_in?->format('h:i A') ?? '—';
            $context['patient_name_' . $i] = $name;
            $context['course_section_department_' . $i] = $courseSectionDepartment;
            $context['age_' . $i] = $record?->age !== null ? (string) $record->age : '—';
            $context['male_' . $i] = ($record?->gender === 'Male') ? '✓' : '—';
            $context['female_' . $i] = ($record?->gender === 'Female') ? '✓' : '—';
            $context['senior_citizen_' . $i] = $record?->is_senior ? '✓' : '—';
            $context['pwd_' . $i] = $record?->is_pwd ? '✓' : '—';
            $context['email_address_' . $i] = $record?->email ?? '—';
            $context['contact_number_' . $i] = $record?->contact ?? '—';
            $context['time_processed_' . $i] = $record?->dental_services_time_processed?->format('h:i A') ?? '—';
            $context['processing_time_' . $i] = $processingTime;
            $isEmergency = (bool) ($record?->is_emergency ?? false);

            $context['emergency_case_' . $i] = $isEmergency ? '✓' : '—';
            $context['non_emergency_case_' . $i] = $isEmergency ? '—' : '✓';
            $context['signature_' . $i] = $record?->has_signature ? '✓' : '—';
        }

        return $context;
    }

    private function resolveValue(string $fieldKey, ?DocumentTemplateField $field, array $context): mixed
    {
        if (array_key_exists($fieldKey, $context) && in_array($fieldKey, [
            'dentist_name', 'submitted_by', 'noted_by', 'prepared_by', 'prepared_by_role',
            'dentist_signature', 'submitted_by_signature', 'noted_by_signature', 'prepared_by_signature',
            'lic_no', 'dentist_license_no',
        ], true)) {
            return $context[$fieldKey];
        }

        if (array_key_exists($fieldKey, $context) && $context[$fieldKey] !== null && $context[$fieldKey] !== '') {
            return $context[$fieldKey];
        }

        return match ($fieldKey) {
            'date' => now()->format('F d, Y'),
            'student_name' => $this->extractName($context, ['student', 'patient', 'user'])
                ?? $field?->sample_value,
            'examination_date' => $this->extractDate($context, ['appointment', 'exam_date'])
                ?? $field?->sample_value,
            'lic_no' => $context['lic_no']
                ?? setting('dentist_license_no', $field?->sample_value ?? 'PRC No. __________'),
            default => $field?->sample_value ?? '—',
        };
    }

    private function extractName(array $context, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = data_get($context, $key . '.name');

            if (is_string($value) && trim($value) !== '') {
                return $value;
            }

            $value = data_get($context, $key);

            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return null;
    }

    private function extractDate(array $context, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = data_get($context, $key . '.appointment_date')
                ?? data_get($context, $key . '.date')
                ?? data_get($context, $key);

            if ($value === null || $value === '') {
                continue;
            }

            if ($value instanceof Carbon) {
                return $value->format('F d, Y');
            }

            try {
                return Carbon::parse((string) $value)->format('F d, Y');
            } catch (\Throwable) {
                if (is_string($value) && trim($value) !== '') {
                    return $value;
                }
            }
        }

        return null;
    }
}
