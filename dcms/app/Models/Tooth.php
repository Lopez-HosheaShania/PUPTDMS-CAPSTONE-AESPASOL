<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tooth extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'tooth_number',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function surfaces()
    {
        return $this->hasMany(ToothSurface::class);
    }

    public function legends()
    {
        return $this->belongsToMany(ToothLegend::class, 'tooth_legends', 'tooth_id', 'legend_id')
            ->withTimestamps();
    }
}