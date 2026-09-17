<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DentistTransitionItemAssignment extends Model
{
    protected $table = 'dentist_transition_item_assignments';

    public $timestamps = false;

    protected $fillable = ['original_dentist_id', 'successor_dentist_id'];

    protected $casts = ['original_dentist_id' => 'integer', 'successor_dentist_id' => 'integer'];

    public function dentistTransitionItem()
    {
        return $this->belongsTo(DentistTransitionItem::class);
    }
}
