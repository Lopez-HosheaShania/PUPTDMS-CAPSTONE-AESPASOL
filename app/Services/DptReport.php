<?php

namespace App\Services;

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use setasign\Fpdi\Fpdi;

class DptReport
{
    public const TEMPLATES = [
        'dpt_emergency' => ['code' => 'DPT-EMERGENCY', 'name' => 'DPT Emergency', 'file' => 'dpt-emergency-template.pdf'],
        'dpt_non_emergency' => ['code' => 'DPT-NON-EMERGENCY', 'name' => 'DPT Non-Emergency', 'file' => 'dpt-non-emergency-template.pdf'],
    ];

    public function records(Carbon $from, Carbon $to, bool $emergency): Collection
    {
        return Appointment::with(['patient.user', 'patient.information', 'dentist', 'procedure'])
            ->where('status', 'completed')->whereHas('patient')
            ->whereDate('appointment_date', '>=', $from->toDateString())
            ->whereDate('appointment_date', '<=', $to->toDateString())
            ->where(function ($query) use ($emergency) {
                $query->where('is_walk_in', $emergency);
                if (! $emergency) {
                    $query->orWhereNull('is_walk_in');
                }
            })
            ->orderBy('appointment_date')->orderBy('appointment_time')->orderBy('id')
            ->get()->map(function (Appointment $appointment) {
                $patient = $appointment->patient;
                $classification = strtolower(trim((string) $patient->classification));
                $classification = match ($classification) {
                    'student', 'faculty' => $classification,
                    'administrative', 'administrative personnel' => 'administrative',
                    default => 'dependent',
                };

                return (object) [
                    'id' => $appointment->id,
                    'date' => Carbon::parse($appointment->appointment_date)->format('m/d/y'),
                    'classification' => $classification,
                    // Preserve compound surnames; use the stored display name for legacy profiles.
                    'surname' => $patient->user?->last_name ?: $patient->name,
                    'minutes' => $appointment->procedure?->procedure_duration_seconds !== null
                        ? max(0, $appointment->procedure->procedure_duration_seconds) / 60 : null,
                    'dentist' => $appointment->dentist?->name ?? '',
                ];
            });
    }

    public function pdf(string $type, Collection $records, Carbon $from, Carbon $to, int $copies): string
    {
        $pdf = new Fpdi('P', 'pt');
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->setSourceFile(storage_path('app/report-templates/'.self::TEMPLATES[$type]['file']));
        $template = $pdf->importPage(1);
        $size = $pdf->getTemplateSize($template);
        [$upper, $lower] = $records->partition(fn ($row) => in_array($row->classification, ['student', 'administrative'], true));
        $upperPages = $upper->values()->chunk(7)->values();
        $lowerPages = $lower->values()->chunk(7)->values();
        $pages = max(1, $upperPages->count(), $lowerPages->count());

        for ($copy = 0; $copy < $copies; $copy++) {
            for ($page = 0; $page < $pages; $page++) {
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($template);
                $pdf->SetFillColor(255, 255, 255);
                $pdf->Rect(225, 136, 100, 13, 'F');
                $date = $from->format('m/d/Y');
                if (! $from->isSameDay($to)) {
                    $date .= ' - '.$to->format('m/d/Y');
                }
                $this->cell($pdf, 210, 137, 125, $date);
                $this->drawRows($pdf, $upperPages->get($page, collect())->values(), 201, 288, 'student');
                $this->drawRows($pdf, $lowerPages->get($page, collect())->values(), 396, 483, 'faculty');
            }
        }

        return $pdf->Output('S');
    }

    private function drawRows(Fpdi $pdf, Collection $rows, float $startY, float $totalY, string $firstClassification): void
    {
        foreach ($rows as $index => $row) {
            $y = $startY + $index * 12.4;
            $this->cell($pdf, 59, $y, 39, $row->date);
            $this->cell($pdf, 100, $y, 59, $row->classification === $firstClassification ? '1' : '');
            $this->cell($pdf, 162, $y, 44, $row->classification !== $firstClassification ? '1' : '');
            $this->cell($pdf, 209, $y, 128, $row->surname);
            $this->cell($pdf, 340, $y, 59, $row->minutes === null ? '' : $this->number($row->minutes));
            $this->cell($pdf, 402, $y, 45, $row->dentist);
            // No assistant is recorded by the existing appointment workflow.
        }
        $this->cell($pdf, 80, $totalY, 17, (string) $rows->count());
        $this->cell($pdf, 100, $totalY, 59, (string) $rows->where('classification', $firstClassification)->count());
        $this->cell($pdf, 162, $totalY, 44, (string) $rows->where('classification', '!=', $firstClassification)->count());
        $timed = $rows->filter(fn ($row) => $row->minutes !== null);
        $this->cell($pdf, 340, $totalY, 59, $timed->isEmpty() ? '' : $this->number($timed->avg('minutes')));
    }

    private function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    private function cell(Fpdi $pdf, float $x, float $y, float $width, string $text): void
    {
        $text = mb_convert_encoding(preg_replace('/\s+/u', ' ', $text), 'Windows-1252', 'UTF-8');
        $fontSize = 7.0;
        $pdf->SetFont('Helvetica', '', $fontSize);
        while ($fontSize > 4 && $pdf->GetStringWidth($text) > $width - 2) {
            $pdf->SetFont('Helvetica', '', $fontSize -= 0.25);
        }
        while ($pdf->GetStringWidth($text) > $width - 2 && strlen($text) > 3) {
            $text = substr($text, 0, -4).'...';
        }
        $pdf->SetXY($x, $y);
        $pdf->Cell($width, 12, $text, 0, 0, 'C');
    }
}
