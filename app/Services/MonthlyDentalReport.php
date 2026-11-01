<?php

namespace App\Services;

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use setasign\Fpdi\Fpdi;

class MonthlyDentalReport
{
    public const ROWS = ['student' => 'Student', 'faculty' => 'Faculty', 'administrative' => 'Administrative', 'dependent' => 'Dependent'];

    public const COLUMNS = [
        'actual_patient' => 'Actual patients (unique)',
        'rde' => 'RDE',
        'charting' => 'Charting',
        'inquiry' => 'Consultation and inquiry',
        'rx' => 'Consultation with Rx',
        'med_rx' => 'Consultation with Med & Rx',
        'extraction' => 'Extraction',
        'prophylaxis' => 'Oral Prophylaxis',
        'temporary' => 'Temporary Restoration',
        'permanent' => 'Permanent Restoration',
        'panoramic' => 'Panoramic X-ray request',
        'periapical' => 'Periapical X-ray request',
        'consent' => 'Consent issued',
        'clearance' => 'Clearance issued',
        'certification' => 'Certification issued',
        'referral_dentist' => 'Referral to Other Dentist',
        'referral_medical' => 'Referral to Medical',
        'suture_removal' => 'Suture Removal',
        'reinstall_jacket' => 'Reinstall Jacket',
    ];

    public function appointments(Carbon $from, Carbon $to): Collection
    {
        return Appointment::with(['patient', 'procedure', 'serviceType'])
            ->where('status', 'completed')->whereHas('patient')
            ->whereDate('appointment_date', '>=', $from->toDateString())
            ->whereDate('appointment_date', '<=', $to->toDateString())
            ->orderBy('appointment_date')->orderBy('id')->get();
    }

    public function preview(Collection $appointments): array
    {
        $counts = [];
        $patients = [];
        $unclassified = 0;
        foreach (self::ROWS as $row => $label) {
            $counts[$row] = array_fill_keys(array_keys(self::COLUMNS), null);
            foreach (['actual_patient', 'inquiry', 'rx', 'prophylaxis'] as $column) {
                $counts[$row][$column] = 0;
            }
            $patients[$row] = [];
        }

        foreach ($appointments as $appointment) {
            $classification = strtolower(trim((string) $appointment->patient->classification));
            $row = match ($classification) {
                'student', 'faculty', 'administrative', 'dependent' => $classification,
                'administrative personnel' => 'administrative',
                'dependent_alumni' => 'dependent',
                default => null,
            };
            if ($row === null) {
                $unclassified++;

                continue;
            }
            $patients[$row][$appointment->patient_id] = true;
            $service = strtolower(trim((string) $appointment->service_type_name));
            $column = match (true) {
                (bool) preg_match('/\b(prophylaxis|prophy|cleaning)\b/', $service) => 'prophylaxis',
                (bool) preg_match('/\b(rde)\b/', $service) => 'rde',
                (bool) preg_match('/\bcharting\b/', $service) => 'charting',
                (bool) preg_match('/\b(consultation|consult|inquiry)\b|oral[ -]+check[ -]?up/', $service) => filled($appointment->procedure?->prescriptions) ? 'rx' : 'inquiry',
                default => null,
            };
            if ($column !== null) {
                $counts[$row][$column] = ($counts[$row][$column] ?? 0) + 1;
            }

            // A chart snapshot can include pre-existing treatment. These are reviewable suggestions only.
            $teeth = ['extraction' => [], 'temporary' => [], 'permanent' => []];
            foreach ($appointment->procedure?->odontogram_data ?? [] as $entry) {
                $tooth = (int) ($entry['tooth'] ?? 0);
                if ($tooth <= 0) {
                    continue;
                }
                $marks = [$entry['status'] ?? null, $entry['threeD'] ?? null, ...array_values($entry['surfaces'] ?? [])];
                foreach ($marks as $mark) {
                    $code = strtoupper(trim((string) ($mark['code'] ?? '')));
                    $category = match ($code) {
                        'X', 'XO' => 'extraction',
                        'TP' => 'temporary',
                        'A', 'LC', 'IN', 'F' => 'permanent',
                        default => null,
                    };
                    if ($category !== null) {
                        $teeth[$category][$tooth] = true;
                    }
                }
            }
            foreach ($teeth as $category => $numbers) {
                if ($numbers !== []) {
                    $counts[$row][$category] = ($counts[$row][$category] ?? 0) + count($numbers);
                }
            }
        }
        foreach ($patients as $row => $ids) {
            $counts[$row]['actual_patient'] = count($ids);
        }

        return ['counts' => $counts, 'completed_visits' => $appointments->count(), 'unclassified_visits' => $unclassified];
    }

    public function totals(array $counts): array
    {
        $columns = array_fill_keys(array_keys(self::COLUMNS), 0);
        $rows = array_fill_keys(array_keys(self::ROWS), 0);
        foreach (self::ROWS as $row => $label) {
            foreach (self::COLUMNS as $column => $name) {
                $value = (int) ($counts[$row][$column] ?? 0);
                $columns[$column] += $value;
                if ($column !== 'actual_patient') {
                    $rows[$row] += $value;
                }
            }
        }

        return ['columns' => $columns, 'rows' => $rows, 'services' => array_sum($rows), 'patients' => $columns['actual_patient']];
    }

    public function draw(Fpdi $pdf, array $counts, Carbon $from, Carbon $to): void
    {
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Rect(338, 54, 188, 14, 'F');
        $period = $from->isSameMonth($to) ? strtoupper($from->format('F Y'))
            : strtoupper($from->format('M j, Y').' - '.$to->format('M j, Y'));
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetXY(338, 57);
        $pdf->Cell(188, 11, 'As of '.$period, 0, 0, 'C');

        // Coordinates follow the supplied A4 landscape PDF (its drawing transform is 0.75).
        $boundaries = [149.6, 209.12, 262.56, 308.64, 354.72, 408.16, 455.68, 509.76, 561.92, 614.72, 668.8, 722.24, 765.6, 815.04, 880.64, 918.08, 958.08, 1018.88, 1075.04];
        $services = array_values(array_diff(array_keys(self::COLUMNS), ['actual_patient']));
        $totals = $this->totals($counts);
        foreach ([...array_keys(self::ROWS), 'total'] as $index => $row) {
            $y = $row === 'total' ? 320.88 : (248.56 + $index * 42.72) * 0.75;
            $values = $row === 'total' ? $totals['columns'] : $counts[$row];
            foreach ($services as $columnIndex => $column) {
                $left = $boundaries[$columnIndex] * 0.75;
                $width = ($boundaries[$columnIndex + 1] - $boundaries[$columnIndex]) * 0.75;
                $pdf->Rect($left + 1, $y - 8, $width - 2, 16, 'F');
                $this->cell($pdf, $left + 1, $y - 5, $width - 2, (string) $values[$column]);
            }
            if ($row === 'total') {
                // Excel A18 is the sum of the 18 service totals, not a patient count.
                $pdf->Rect(30, 311, 81, 19, 'F');
                $this->cell($pdf, 31, $y - 5, 79, (string) $totals['services']);
            } else {
                $this->cell($pdf, 92, $y - 5, 18, (string) $values['actual_patient']);
            }
        }
    }

    private function cell(Fpdi $pdf, float $x, float $y, float $width, string $value): void
    {
        $fontSize = 7.0;
        $pdf->SetFont('Helvetica', 'B', $fontSize);
        while ($fontSize > 4 && $pdf->GetStringWidth($value) > $width - 2) {
            $pdf->SetFont('Helvetica', 'B', $fontSize -= 0.25);
        }
        $pdf->SetXY($x, $y);
        $pdf->Cell($width, 10, $value, 0, 0, 'C');
    }
}
