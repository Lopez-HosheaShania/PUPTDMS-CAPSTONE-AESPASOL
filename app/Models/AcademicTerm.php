<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicTerm extends Model
{
    protected $fillable = [
        'code',
        'name',
        'sort_order',
    ];

    public function academicPeriods()
    {
        return $this->hasMany(AcademicPeriod::class);
    }
}