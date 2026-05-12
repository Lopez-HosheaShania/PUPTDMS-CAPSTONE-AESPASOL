<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Tooth;
use App\Models\ToothSurface;

class ToothLegend extends Model
{
    use HasFactory;

    protected $table = 'legends';

    protected $fillable = [
        'code',
        'description',
        'category',
    ];

    public function toothSurfaces()
    {
        return $this->belongsToMany(ToothSurface::class, 'tooth_surface_legends', 'legend_id', 'tooth_surface_id')
            ->withTimestamps();
    }

    public function teeth()
    {
        return $this->belongsToMany(Tooth::class, 'tooth_legends', 'legend_id', 'tooth_id')
            ->withTimestamps();
    }
}