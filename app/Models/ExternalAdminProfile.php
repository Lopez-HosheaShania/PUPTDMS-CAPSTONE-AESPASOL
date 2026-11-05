<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalAdminProfile extends Model
{
    public const FIELDS = ['external_admin_id', 'fname', 'lname', 'email', 'office', 'address', 'age', 'gender', 'contact_number', 'senior_pwd'];

    protected $guarded = ['id'];

    public function access()
    {
        return $this->hasOne(ExternalAdminAccess::class);
    }
}
