<?php

namespace App\Http\Controllers\Dentist;

use App\Http\Controllers\Controller;
use App\Models\DentalServiceRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DentalServicesRecordController extends Controller
{
    public function index(Request $request)
    {
        $selectedMonth = $this->resolveSelectedMonth($request->input('month'));

        $records = DentalServiceRecord::query()
            ->latest('time_in')
            ->get()
            ->map(fn(DentalServiceRecord $record) => $this->toFrontendRow($record))
            ->values();

        $notifications = [];

        return view('dentist.dental-services', compact(
            'records',
            'selectedMonth',
            'notifications'
        ));
    }

    public function data(Request $request)
    {
        $selectedMonth = $this->resolveSelectedMonth($request->input('month'));

        $records = $this->recordsForMonth($selectedMonth)
            ->map(fn(DentalServiceRecord $record) => $this->toFrontendRow($record))
            ->values();

        return response()->json([
            'records' => $records,
            'selectedMonth' => $selectedMonth,
        ]);
    }

    private function resolveSelectedMonth(?string $month): string
    {
        if ($month && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            return $month;
        }

        return now()->format('Y-m');
    }

    private function recordsForMonth(string $selectedMonth)
    {
        $start = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return DentalServiceRecord::query()
            ->whereBetween('time_in', [$start, $end])
            ->latest('time_in')
            ->get();
    }

    private function toFrontendRow(DentalServiceRecord $record): array
    {
        $middleInitial = $record->patient_middle_name
            ? strtoupper(substr($record->patient_middle_name, 0, 1)) . '.'
            : '';

        $name = trim("{$record->patient_last_name}, {$record->patient_first_name} {$middleInitial}");

        $programDisplay = $record->department === 'Student'
            ? trim(
                ($record->program_code ?? '') .
                    ($record->year_level ? ' ' . $record->year_level : '') .
                    ($record->section ? "-{$record->section}" : '')
            )
            : ($record->department ?? '');

        $priority = array_values(array_filter([
            $record->is_pwd ? 'PWD' : null,
            $record->is_senior ? 'Senior' : null,
        ]));

        $duration = '';

        if ($record->time_in && $record->time_out) {
            $duration = $record->time_in->diffInMinutes($record->time_out) . ' mins';
        }

        return [
            'id' => $record->id,
            'date' => optional($record->time_in)->format('m/d/y') ?? '',
            'dateKey' => optional($record->time_in)->format('Y-m-d') ?? '',
            'monthKey' => optional($record->time_in)->format('Y-m') ?? '',
            'timeIn' => optional($record->time_in)->format('h:i A') ?? '',
            'name' => $name,
            'program' => $programDisplay,
            'age' => $record->age,
            'gad' => [
                'gender' => $record->gender,
                'priority' => $priority,
            ],
            'email' => $record->email,
            'contact' => $record->contact,
            'timeOut' => $record->time_out ? $record->time_out->format('h:i A') : '',
            'duration' => $duration,
            'type' => $record->visit_type,
            'department' => $record->department,
            'has_signature' => (bool) $record->has_signature,
        ];
    }
}
