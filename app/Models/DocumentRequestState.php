<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentRequestState extends Model
{
    public $timestamps = false;

    protected $primaryKey = 'document_request_id';

    public $incrementing = false;

    protected $guarded = [];

    protected $casts = ['request_date' => 'date'];
}
