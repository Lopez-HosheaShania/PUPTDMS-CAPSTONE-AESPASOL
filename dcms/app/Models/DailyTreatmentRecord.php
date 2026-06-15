<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyTreatmentRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'treatment_date',
        'time_in',
        'time_out',
        'patient_name',
        'patient_email',
        'patient_phone',
        'office_type',
        'program_code',
        'age',
        'gender',
        'is_senior',
        'is_pwd',
        'case_type',
        'treatment_done',
        'minutes_processed',
        'has_signature',
        'signature_path',
    ];

    protected $casts = [
        'treatment_date' => 'date',
        'time_in' => 'datetime:H:i:s',
        'time_out' => 'datetime:H:i:s',
        'age' => 'integer',
        'is_senior' => 'boolean',
        'is_pwd' => 'boolean',
        'has_signature' => 'boolean',
    ];
}