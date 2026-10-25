<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OdontogramRevisionReasonType extends Model
{
    protected $fillable = [
        'code',
        'label',
        'requires_detail',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'requires_detail' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(
            OdontogramRevision::class,
            'reason_type_id'
        );
    }

    public function scopeActive(
        Builder $query
    ): Builder {
        return $query->where(
            'is_active',
            true
        );
    }
}