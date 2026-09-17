<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalAdminAccess extends Model
{
    protected $table = 'external_admin_accesses';

    protected $with = ['profile'];

    protected $hidden = ['profile'];

    public function profile()
    {
        return $this->belongsTo(ExternalAdminProfile::class, 'external_admin_profile_id');
    }

    public function scopeForIdentity($query, $email, string $externalId)
    {
        return $query->whereHas('profile', fn ($profile) => $profile->where(function ($identity) use ($email, $externalId) {
            $identity->where('email', $email)->orWhere('external_admin_id', $externalId);
        }));
    }

    public function getAttribute($key)
    {
        if (in_array($key, ExternalAdminProfile::FIELDS, true)) {
            return $this->getRelationValue('profile')?->getAttribute($key);
        }

        return parent::getAttribute($key);
    }

    public function attributesToArray()
    {
        $profile = [];
        foreach (ExternalAdminProfile::FIELDS as $field) {
            $profile[$field] = $this->getAttribute($field);
        }

        return array_merge(parent::attributesToArray(), $this->getArrayableItems($profile));
    }

    protected $fillable = [
        'external_admin_profile_id',
        'has_cms_access',
        'cms_role',
        'cms_status',
    ];
}
