<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DentistTransitionItemState extends Model
{
    protected $table = 'dentist_transition_item_states';

    public $timestamps = false;

    protected $fillable = ['transfer_status', 'is_critical'];

    protected $casts = ['is_critical' => 'boolean'];

    public function dentistTransitionItem()
    {
        return $this->belongsTo(DentistTransitionItem::class);
    }
}
