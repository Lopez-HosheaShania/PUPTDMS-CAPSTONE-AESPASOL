<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicDentistAssignment extends Model
{
    public const SLOT = 'primary';

    protected $primaryKey = 'slot';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['dentist_id', 'dentist_transition_id', 'assigned_by'];

    protected $casts = ['dentist_id' => 'integer', 'dentist_transition_id' => 'integer', 'assigned_by' => 'integer'];

    public function dentist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dentist_id');
    }

    public function transition(): BelongsTo
    {
        return $this->belongsTo(DentistTransition::class, 'dentist_transition_id');
    }
}
