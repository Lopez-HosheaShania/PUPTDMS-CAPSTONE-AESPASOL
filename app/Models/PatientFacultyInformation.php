<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientFacultyInformation extends Model
{
    protected $table = 'patient_faculty_information';

    protected $fillable = [
        'patient_id',
        'faculty_code',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}