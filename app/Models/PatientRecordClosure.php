<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientRecordClosure extends Model
{
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'patient_id';
    protected $guarded = [];
    protected $casts = ['closed_at' => 'datetime'];
}
