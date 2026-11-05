<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientInformation extends Model
{
    use HasFactory;

    protected $table = 'patient_information';

    protected $fillable = [
        'patient_id',
        'phone',
        'birthdate',
        'gender',
        'place_of_birth',
        'height_m',
        'weight_kg',
        'faculty_code',
        'student_no',
        'course_code',
        'course_name',
        'year_level',
        'section',
        'is_pwd',
        'is_senior',
        'address',
    ];

    protected $casts = [
        'birthdate' => 'date',
        'height_m' => 'float',
        'weight_kg' => 'float',
        'year_level' => 'integer',
        'is_pwd' => 'boolean',
        'is_senior' => 'boolean',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
