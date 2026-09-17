<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientStudentInformation extends Model
{
    protected $table = 'patient_student_information';

    protected $fillable = [
        'patient_id',
        'student_no',
        'course_code',
        'course_name',
        'year_level',
        'section',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}