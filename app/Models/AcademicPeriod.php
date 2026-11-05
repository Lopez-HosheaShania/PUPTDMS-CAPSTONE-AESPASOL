<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class AcademicPeriod extends Model
{
    protected $fillable = [
        'academic_year_id',
        'academic_term_id',
        'start_date',
        'end_date',
        'description',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function getStatusAttribute(): string
    {
        $today = Carbon::today();

        if ($this->is_active) {
            return 'Active';
        }

        if ($this->start_date && $today->lt($this->start_date)) {
            return 'Upcoming';
        }

        if ($this->end_date && $today->gt($this->end_date)) {
            return 'Ended';
        }

        return 'Inactive';
    }

    public function getDaysRemainingAttribute(): int
    {
        if (!$this->end_date) {
            return 0;
        }

        $today = Carbon::today();

        if ($today->gt($this->end_date)) {
            return 0;
        }

        return $today->diffInDays($this->end_date);
    }

    public function getProgressPercentAttribute(): int
    {
        if (!$this->start_date || !$this->end_date) {
            return 0;
        }

        $start = Carbon::parse($this->start_date);
        $end = Carbon::parse($this->end_date);
        $today = Carbon::today();

        if ($today->lte($start)) {
            return 0;
        }

        if ($today->gte($end)) {
            return 100;
        }

        $totalDays = $start->diffInDays($end);

        if ($totalDays <= 0) {
            return 0;
        }

        $elapsedDays = $start->diffInDays($today);

        return (int) round(($elapsedDays / $totalDays) * 100);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function academicTerm()
    {
        return $this->belongsTo(AcademicTerm::class);
    }
}
