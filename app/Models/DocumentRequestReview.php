<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentRequestReview extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = ['approved_at' => 'datetime', 'approved_by' => 'integer'];
}
