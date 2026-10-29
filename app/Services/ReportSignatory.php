<?php

namespace App\Services;

use App\Support\DentistName;
use setasign\Fpdi\Fpdi;

class ReportSignatory
{
    public static function dentistNameForReport(string $name, bool $addPrefix = true, bool $addSuffix = true): string
    {
        $name = trim($name);
        $name = preg_replace('/^(?:DR\.?\s+)+/iu', '', $name);
        $name = preg_replace('/(?:,\s*|\s+)(?:D\.?M\.?D\.?|M\.?D\.?)$/iu', '', $name);
        $name = trim($name, " \t\n\r\0\x0B,");

        return $name === '' ? '' : ($addPrefix ? 'Dr. ' : '').$name.($addSuffix ? ', DMD' : '');
    }

    public static function isDentalAide(?\App\Models\User $user): bool
    {
        return in_array(str_replace('-', '_', $user?->role?->slug ?? ''), ['dental_aide', 'dental_aide_role'], true);
    }

    public static function canManage(?\App\Models\User $user): bool
    {
        return $user?->status === 'active'
            && (AssignedDentistService::isDentist($user) || self::isPreparer($user));
    }

    public static function isPreparer(?\App\Models\User $user): bool
    {
        $role = str_replace('-', '_', $user?->role?->slug ?? '');

        return self::isDentalAide($user)
            || in_array($role, ['dental_intern', 'dental_intern_role'], true)
            || ($role === 'admin' && $user->hasPermission('create_report_files'));
    }

    public static function preparerLabel(?\App\Models\User $user): string
    {
        if ($user?->role?->slug === 'admin') {
            return 'Dental Aide';
        }

        return self::isDentalAide($user) ? 'Dental Aide' : 'Dental Intern';
    }

    public function ownProfile(): ?\App\Models\ReportSignatory
    {
        return self::canManage(auth()->user())
            ? \App\Models\ReportSignatory::where('user_id', auth()->id())->first() : null;
    }

    public function currentPreparerProfile(): ?\App\Models\ReportSignatory
    {
        if (self::isPreparer(auth()->user())) {
            return $this->ownProfile();
        }

        $profiles = \App\Models\ReportSignatory::whereHas('user', function ($query) {
            $query->where('status', 'active')
                ->whereHas('role', function ($role) {
                    $role->where(function ($roles) {
                        $roles->whereIn('slug', ['dental_aide', 'dental-aide', 'dental_aide_role', 'dental-aide-role'])
                            ->orWhere(function ($admin) {
                                $admin->where('slug', 'admin')
                                    ->whereHas('permissions', fn ($permission) => $permission->where('slug', 'create_report_files'));
                            });
                    });
                })
                ->whereDoesntHave('employmentStatus', fn ($employment) => $employment->whereNotNull('access_ends_at')->where('access_ends_at', '<=', now()));
        })->limit(2)->get();

        return $profiles->count() === 1 ? $profiles->first() : null;
    }

    public function currentProfile(): ?\App\Models\ReportSignatory
    {
        $user = auth()->user();

        if (AssignedDentistService::isDentist($user)) {
            return $user->status === 'active'
                ? \App\Models\ReportSignatory::where('user_id', $user->id)->first() : null;
        }

        // Staff may generate reports for the clinic's sole configured dentist.
        // Multiple active signatories require the dentist to generate their own report.
        $profiles = \App\Models\ReportSignatory::whereHas('user', function ($query) {
            $query->where('status', 'active')
                ->whereHas('role', fn ($role) => $role->whereIn('slug', ['dentist', 'dentist_role', 'head_dentist']))
                ->whereDoesntHave('employmentStatus', fn ($employment) => $employment->whereNotNull('access_ends_at')->where('access_ends_at', '<=', now()));
        })->limit(2)->get();

        return $profiles->count() === 1 ? $profiles->first() : null;
    }

    public function profileForDentist(?\App\Models\User $dentist): ?\App\Models\ReportSignatory
    {
        return AssignedDentistService::isDentist($dentist)
            ? \App\Models\ReportSignatory::where('user_id', $dentist->id)->first() : null;
    }

    public function documentProfile(\App\Models\DocumentRequest $request): ?\App\Models\ReportSignatory
    {
        foreach ([$request->approvedBy, $request->assignedDentist] as $dentist) {
            if (AssignedDentistService::isDentist($dentist)) {
                return $this->profileForDentist($dentist);
            }
        }

        return null;
    }
    private const LAYOUTS = [
        'gad_report' => [
            'name_y' => 518.569, 'role_y' => 532.819,
            'font_size' => 10.725, 'role_font_size' => 10.725,
            'signature_y' => 475, 'signature_height' => 32,
            'preparer' => ['x' => 128.4, 'width' => 233, 'mask' => [120, 468, 245, 80]],
            'dentist' => ['x' => 466.65, 'width' => 240, 'mask' => [464, 468, 245, 80]],
        ],
        'daily_treatment_record' => [
            'name_y' => 425.967, 'role_y' => 436.014,
            'font_size' => 5.739, 'role_font_size' => 5.261,
            'signature_y' => 400, 'signature_height' => 19,
            'preparer' => [
                'x' => 61.226, 'width' => 210, 'mask' => [58, 390, 220, 57],
                'label' => ['Prepared by:', 61.226, 397.306, 4.782, 'B'],
            ],
            'dentist' => [
                'x' => 430.949, 'width' => 210, 'name_y' => 426.287, 'mask' => [429, 390, 220, 57],
                'label' => ['Noted by:', 430.949, 397.306, 4.782, 'B'],
            ],
        ],
        'dental_cases' => [
            'name_y' => 666.7, 'role_y' => 680.14,
            'font_size' => 11.04, 'role_font_size' => 9.96,
            'signature_y' => 614, 'signature_height' => 40,
            'preparer' => [
                'x' => 72.024, 'width' => 220, 'mask' => [54, 560, 252, 134],
                'label' => ['Prepared by:', 72.024, 608.11, 11.04, ''],
            ],
            'dentist' => [
                'x' => 324.07, 'width' => 230, 'mask' => [318, 560, 240, 134],
                'label' => ['Submitted by:', 324.07, 608.11, 11.04, ''],
            ],
        ],
        'monthly_report' => [
            'name_y' => 355.56, 'role_y' => 366.36,
            'font_size' => 8.416, 'role_font_size' => 8.416,
            'font_family' => 'Times', 'role_style' => 'B',
            'signature_y' => 337, 'signature_height' => 10,
            'dentist' => [
                'x' => 30.48, 'width' => 210, 'mask' => [28, 316, 215, 63],
                'label' => ['Submitted by:', 30.48, 333.96, 8.416, 'B'],
            ],
        ],
        'dpt_emergency' => [
            'name_y' => 578.4, 'role_y' => 590.88,
            'font_size' => 8.88, 'role_font_size' => 8.88,
            'signature_y' => 531, 'signature_height' => 38,
            'dentist' => [
                'x' => 386.684, 'role_x' => 402.48, 'width' => 170, 'mask' => [330, 510, 245, 98],
                'label' => ['Prepared by:', 340.56, 578.4, 8.88, '', 44.124],
            ],
        ],
        'dpt_non_emergency' => [
            'name_y' => 578.4, 'role_y' => 590.88,
            'font_size' => 8.88, 'role_font_size' => 8.88,
            'signature_y' => 531, 'signature_height' => 38,
            'dentist' => [
                'x' => 386.684, 'role_x' => 402.48, 'width' => 170, 'mask' => [330, 510, 245, 98],
                'label' => ['Prepared by:', 340.56, 578.4, 8.88, '', 44.124],
            ],
        ],
    ];

    public function draw(Fpdi $pdf, string $type, iterable $records = []): void
    {
        $layout = self::LAYOUTS[$type] ?? null;
        if (! $layout) {
            return;
        }

        foreach (['preparer', 'dentist'] as $slot) {
            if (! isset($layout[$slot])) {
                continue;
            }
            $position = array_merge($layout, $layout[$slot]);
            $pdf->SetFillColor(255, 255, 255);
            $pdf->Rect(...[...$position['mask'], 'F']);
            $pdf->SetTextColor(0, 0, 0);
            if (isset($position['label'])) {
                [$label, $x, $y, $fontSize, $style] = $position['label'];
                $this->drawTemplateText($pdf, $label, $x, $y, $position['label'][5] ?? $position['width'], $fontSize, $style, $position['font_family'] ?? 'Helvetica');
            }

            $profile = $slot === 'preparer' ? $this->currentPreparerProfile() : $this->currentProfile();
            if ($profile) {
                $this->drawProfile($pdf, $profile, $position, $slot === 'preparer' ? self::preparerLabel($profile->user) : 'Dentist');
            } elseif ($slot === 'dentist') {
                $names = array_map([self::class, 'dentistNameForReport'], DentistName::forRecords($records));
                if ($names !== []) {
                    $this->drawTemplateText($pdf, implode('; ', $names), $position['x'], $position['name_y'], $position['width'], $position['font_size'], 'B', $position['font_family'] ?? 'Helvetica');
                    $this->drawTemplateText($pdf, count($names) === 1 ? 'Dentist' : 'Dentists', $position['role_x'] ?? $position['x'], $position['role_y'], $position['width'], $position['role_font_size'], $position['role_style'] ?? '', $position['font_family'] ?? 'Helvetica');
                }
            }
        }
    }

    public function drawProfile(Fpdi $pdf, \App\Models\ReportSignatory $profile, array $position, string $designation = 'Dentist'): void
    {
        $displayName = $designation === 'Dentist' ? self::dentistNameForReport($profile->name) : $profile->name;
        $name = mb_convert_encoding($displayName, 'Windows-1252', 'UTF-8');
        $family = $position['font_family'] ?? 'Helvetica';
        $pdf->SetFont($family, 'B', $position['font_size']);
        $nameWidth = $pdf->GetStringWidth($name);
        if ($nameWidth > $position['width']) {
            $pdf->SetFont($family, 'B', $position['font_size'] * $position['width'] / $nameWidth);
            $nameWidth = $pdf->GetStringWidth($name);
        }
        if ($path = $profile->signaturePath()) {
            [$imageWidth, $imageHeight] = getimagesize($path);
            $scale = min(max(1, $nameWidth) / $imageWidth, $position['signature_height'] / $imageHeight);
            $renderedWidth = $imageWidth * $scale;
            $renderedHeight = $imageHeight * $scale;
            $pdf->Image(
                $path,
                $position['x'] + ($nameWidth - $renderedWidth) / 2,
                $position['signature_y'] + $position['signature_height'] - $renderedHeight,
                $renderedWidth,
                $renderedHeight,
            );
        }
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Text($position['x'], $position['name_y'], $name);
        $role = $designation === 'Dentist' ? 'Dentist | Lic. No. '.$profile->license_number : $designation;
        $this->drawTemplateText($pdf, $role, $position['role_x'] ?? $position['x'], $position['role_y'], $position['width'], $position['role_font_size'], $position['role_style'] ?? '', $family);
    }

    private function drawTemplateText(Fpdi $pdf, string $text, float $x, float $y, float $width, float $fontSize, string $style = '', string $family = 'Helvetica'): void
    {
        $text = mb_convert_encoding($text, 'Windows-1252', 'UTF-8');
        $pdf->SetFont($family, $style, $fontSize);
        $textWidth = $pdf->GetStringWidth($text);
        if ($textWidth > $width) {
            $pdf->SetFont($family, $style, $fontSize * $width / $textWidth);
        }
        $pdf->Text($x, $y, $text);
    }
}
