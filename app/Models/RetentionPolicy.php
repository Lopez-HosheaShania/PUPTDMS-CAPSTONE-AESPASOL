<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetentionPolicy extends Model
{
    use \App\Models\Concerns\StoresOptionalDetails;

    protected function detailFields(): array
    {
        return ['approval' => ['approved_by', 'approved_at', 'approval_reference', 'activated_by', 'activated_at', 'effective_from', 'policy_version']];
    }

    public function approval()
    {
        return $this->hasOne(RetentionPolicyApproval::class);
    }

    protected $fillable = [
        'category', 'duration', 'unit', 'status', 'updated_by',
        'approved_by', 'approved_at', 'approval_reference', 'activated_by',
        'activated_at', 'version', 'counting_rule', 'effective_from', 'policy_version',
    ];

    protected $casts = [
        'duration' => 'integer', 'version' => 'integer',
    ];

    public function scopeCurrent($query)
    {
        return $query->whereIn('category', array_keys(config('retention.categories', [])));
    }

    public function isCurrent(): bool
    {
        return array_key_exists($this->category, config('retention.categories', []));
    }

    public function countingRules(): array
    {
        return config('retention.categories.'.$this->category.'.counting_rules', []);
    }

    public function hasConfiguredRule(): bool
    {
        return array_key_exists((string) $this->counting_rule, $this->countingRules());
    }

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
