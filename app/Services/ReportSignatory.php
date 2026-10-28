<?php

namespace App\Services;

use App\Support\DentistName;
use setasign\Fpdi\Fpdi;

class ReportSignatory
{
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
        return self::isDentalAide($user)
            || in_array(str_replace('-', '_', $user?->role?->slug ?? ''), ['dental_intern', 'dental_intern_role'], true);
    }

    public static function preparerLabel(?\App\Models\User $user): string
    {
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
                ->whereHas('role', fn ($role) => $role->whereIn('slug', ['dental_aide', 'dental-aide', 'dental_aide_role', 'dental-aide-role']))
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
        'gad_report' => [464, 468, 245, 70, 508, 524, 10.5],
        'daily_treatment_record' => [429, 402, 220, 39, 418, 429, 6],
        'dental_cases' => [322, 570, 235, 116, 656, 671, 10.5],
        'monthly_report' => [28, 305, 215, 67, 346, 358, 8.4],
        'dpt_emergency' => [375, 510, 200, 86, 569, 582, 8.8],
        'dpt_non_emergency' => [375, 510, 200, 86, 569, 582, 8.8],
    ];

    public function draw(Fpdi $pdf, string $type, iterable $records = []): void
    {
        if (! isset(self::LAYOUTS[$type])) {
            return;
        }

        $this->drawPreparedBy($pdf, $type);

        [$x, $y, $width, $height, $nameY, $roleY, $fontSize] = self::LAYOUTS[$type];
        $pdf->SetFillColor(255, 255, 255);
        $maskX = str_starts_with($type, 'dpt_') ? 330 : $x;
        $pdf->Rect($maskX, $y, $width + $x - $maskX, $height, 'F');
        $pdf->SetTextColor(0, 0, 0);
        if (in_array($type, ['dental_cases', 'monthly_report'], true)) {
            $pdf->SetFont('Helvetica', '', $fontSize);
            $pdf->SetXY($x + 2, $type === 'dental_cases' ? 598 : 315);
            $pdf->Cell($width - 4, 12, 'Submitted by:', 0, 0, 'L');
        }
        if (str_starts_with($type, 'dpt_')) {
            $pdf->SetFont('Helvetica', '', $fontSize);
            $pdf->SetXY($x + 2, 524);
            $pdf->Cell($width - 4, 12, 'Prepared by:', 0, 0, 'L');
        }
        if ($profile = $this->currentProfile()) {
            $signatureY = max($y + 2, $nameY - 25, $type === 'monthly_report' ? 328 : 0);
            $this->drawProfile($pdf, $profile, $x, $signatureY, $width, $fontSize, max(6, $nameY - $signatureY - 1));

            return;
        }
        $names = DentistName::forRecords($records);
        if ($names === []) {
            return;
        }

        $name = implode('; ', $names);
        $text = mb_convert_encoding($name, 'Windows-1252', 'UTF-8');
        $pdf->SetFont('Helvetica', 'B', $fontSize);
        $availableHeight = $roleY - $nameY;
        while ($fontSize > 5 && $this->lineCount($pdf, $text, $width - 4) * $fontSize > $availableHeight) {
            $pdf->SetFont('Helvetica', 'B', $fontSize -= 0.25);
        }
        $pdf->SetXY($x + 2, $nameY);
        $pdf->MultiCell($width - 4, $fontSize, $text, 0, 'L');
        $pdf->SetFont('Helvetica', '', max(5, $fontSize - 1));
        $pdf->SetXY($x + 2, $roleY);
        $pdf->Cell($width - 4, 10, count($names) === 1 ? 'Dentist' : 'Dentists', 0, 0, 'L');
    }

    private function drawPreparedBy(Fpdi $pdf, string $type): void
    {
        $layout = match ($type) {
            'gad_report' => [120, 468, 245, 70, 508, 10.5],
            'daily_treatment_record' => [58, 402, 220, 39, 418, 6],
            'dental_cases' => [70, 570, 235, 116, 656, 10.5],
            default => null,
        };
        if (! $layout) {
            return;
        }
        [$x, $y, $width, $height, $nameY, $fontSize] = $layout;
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect($x, $y, $width, $height, 'F');
        if ($type === 'dental_cases') {
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('Helvetica', '', $fontSize);
            $pdf->SetXY($x + 2, 598);
            $pdf->Cell($width - 4, 12, 'Prepared by:');
        }
        if ($profile = $this->currentPreparerProfile()) {
            $signatureY = max($y + 2, $nameY - 25);
            $this->drawProfile($pdf, $profile, $x, $signatureY, $width, $fontSize, max(6, $nameY - $signatureY - 1), self::preparerLabel($profile->user));
        }
    }

    public function drawProfile(Fpdi $pdf, \App\Models\ReportSignatory $profile, float $x, float $y, float $width, float $fontSize = 9, float $signatureHeight = 24, string $designation = 'Dentist'): void
    {
        if ($path = $profile->signaturePath()) {
            [$imageWidth, $imageHeight] = getimagesize($path);
            $scale = min(($width - 4) / $imageWidth, $signatureHeight / $imageHeight);
            $pdf->Image($path, $x + 2, $y, $imageWidth * $scale, $imageHeight * $scale);
        }
        $pdf->SetTextColor(0, 0, 0);
        $name = mb_convert_encoding($profile->name, 'Windows-1252', 'UTF-8');
        $pdf->SetFont('Helvetica', 'B', $fontSize);
        while ($fontSize > 5 && $pdf->GetStringWidth($name) > $width - 4) {
            $pdf->SetFont('Helvetica', 'B', $fontSize -= 0.25);
        }
        $pdf->SetXY($x + 2, $y + $signatureHeight + 1);
        $pdf->Cell($width - 4, $fontSize + 1, $name);
        $nameHeight = $fontSize + 2;
        $pdf->SetFont('Helvetica', '', max(5, $fontSize - 1));
        $license = mb_convert_encoding($designation === 'Dentist' ? 'Dentist | Lic. No. '.$profile->license_number : $designation, 'Windows-1252', 'UTF-8');
        while ($pdf->GetStringWidth($license) > $width - 4 && $fontSize > 5) {
            $pdf->SetFont('Helvetica', '', $fontSize -= 0.25);
        }
        $pdf->SetXY($x + 2, $y + $signatureHeight + 1 + $nameHeight);
        $pdf->Cell($width - 4, $fontSize + 1, $license);
    }

    private function lineCount(Fpdi $pdf, string $text, float $width): int
    {
        $lines = 1;
        $line = '';
        foreach (explode(' ', $text) as $word) {
            $candidate = $line === '' ? $word : $line.' '.$word;
            if ($line !== '' && $pdf->GetStringWidth($candidate) > $width - 6) {
                $lines++;
                $line = $word;
            } else {
                $line = $candidate;
            }
        }

        return $lines;
    }
}
