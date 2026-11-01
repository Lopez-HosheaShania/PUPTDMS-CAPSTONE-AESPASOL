<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name', 'slug'];

    public static function displayNameFor(?string $slug, ?self $role = null): string
    {
        $normalizedSlug = strtolower(trim((string) $slug));

        if (
            $role &&
            $normalizedSlug !== '' &&
            strtolower((string) $role->slug) === $normalizedSlug &&
            filled($role->name)
        ) {
            return ucwords($role->name);
        }

        return match ($normalizedSlug) {
            'super_admin', 'admin' => 'Administrator',
            'dentist', 'dentist_role' => 'Dentist',
            'patient', 'patient_role' => 'Patient',
            default => ucwords($role?->name ?: str_replace(['-', '_'], ' ', $normalizedSlug ?: 'user')),
        };
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return self::displayNameFor($this->slug, $this);
    }
}
