<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetentionPolicy extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'duration' => 'integer', 'version' => 'integer',
        'archive_enabled' => 'boolean', 'disposal_enabled' => 'boolean',
        'approved_at' => 'datetime', 'activated_at' => 'datetime',
    ];

    public function getPeriodLabelAttribute(): string
    {
        $unit = $this->duration === 1 ? rtrim($this->unit, 's') : $this->unit;

        return $this->duration.' '.ucfirst($unit).($this->status === 'provisional' ? ' (Temporary)' : '');
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status === 'provisional' ? 'Pending Review' : ucfirst($this->status);
    }

    public function administrator()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
