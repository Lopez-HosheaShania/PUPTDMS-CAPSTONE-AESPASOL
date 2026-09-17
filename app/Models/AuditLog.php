<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public const ACTOR_FIELDS = ['actor_id', 'actor_role', 'actor_identifier', 'actor_name'];

    public const REQUEST_FIELDS = ['ip_address', 'user_agent', 'browser_name', 'device_type', 'device_name', 'os_name'];

    protected $with = ['actorSnapshot', 'requestDetails', 'descriptionDetails'];

    protected $hidden = ['actorSnapshot', 'requestDetails', 'descriptionDetails'];

    private array $pendingDetails = [];

    public function descriptionDetails()
    {
        return $this->hasOne(AuditLogDescription::class);
    }

    public function getFullDescriptionAttribute(): ?string
    {
        if (array_key_exists('descriptionDetails', $this->pendingDetails)) {
            return $this->pendingDetails['descriptionDetails']['full_description'];
        }

        return $this->descriptionDetails?->full_description ?? $this->description;
    }

    public function scopeWithDescription($query, string $text)
    {
        return $query->where(fn ($q) => $q->where('description', $text)
            ->orWhereHas('descriptionDetails', fn ($detail) => $detail->where('full_description', $text)));
    }

    public function actorSnapshot()
    {
        return $this->hasOne(AuditLogActor::class);
    }

    public function requestDetails()
    {
        return $this->hasOne(AuditLogRequest::class);
    }

    private function detailRelation($key): ?string
    {
        return in_array($key, self::ACTOR_FIELDS, true) ? 'actorSnapshot'
            : (in_array($key, self::REQUEST_FIELDS, true) ? 'requestDetails' : null);
    }

    public function getAttribute($key)
    {
        if ($relation = $this->detailRelation($key)) {
            return array_key_exists($key, $this->pendingDetails[$relation] ?? [])
                ? $this->pendingDetails[$relation][$key]
                : $this->getRelationValue($relation)?->getAttribute($key);
        }

        return parent::getAttribute($key);
    }

    public function setAttribute($key, $value)
    {
        if ($key === 'description') {
            $summary = \App\Support\AuditDescription::summarize($value, $this->action, $this->module);
            if ($summary !== $value || $this->exists) {
                $this->pendingDetails['descriptionDetails'] = ['full_description' => $value];
            }

            return parent::setAttribute($key, $summary);
        }
        if ($relation = $this->detailRelation($key)) {
            $this->pendingDetails[$relation][$key] = $value;

            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    public function attributesToArray()
    {
        $details = [];
        foreach (array_merge(self::ACTOR_FIELDS, self::REQUEST_FIELDS) as $key) {
            $details[$key] = $this->getAttribute($key);
        }

        return array_merge(parent::attributesToArray(), $this->getArrayableItems($details));
    }

    public function save(array $options = [])
    {
        if ($this->pendingDetails === []) {
            return parent::save($options);
        }

        return $this->getConnection()->transaction(function () use ($options) {
            if (! parent::save($options)) {
                return false;
            }
            foreach ($this->pendingDetails as $relation => $values) {
                $this->setRelation($relation, $this->{$relation}()->updateOrCreate([], $values));
            }
            $this->pendingDetails = [];

            return true;
        });
    }

    public function scopeForActorRole($query, string $role)
    {
        return $query->whereHas('actorSnapshot', fn ($actor) => $actor->where('actor_role', $role));
    }

    protected $fillable = [
        'actor_id',
        'actor_name',
        'actor_role',
        'actor_identifier',
        'action',
        'module',
        'description',
        'is_archived',
        'archived_at',
        'archived_by',
        'ip_address',
        'user_agent',
        'browser_name',
        'device_type',
        'device_name',
        'os_name',
    ];

    protected $casts = [
        'is_archived' => 'boolean',
        'archived_at' => 'datetime',
    ];

    public function user()
    {
        return $this->hasOneThrough(User::class, AuditLogActor::class, 'audit_log_id', 'id', 'id', 'actor_id');
    }

    public function archivedBy()
    {
        return $this->belongsTo(User::class, 'archived_by');
    }
}
