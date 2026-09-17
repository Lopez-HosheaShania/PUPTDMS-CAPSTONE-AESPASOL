<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Support\AppointmentOdontogramStorage;

class AppointmentOdontogram extends Model
{
    protected $fillable = ['appointment_procedure_id', 'odontogram_data'];

    protected $casts = ['has_data' => 'boolean'];

    protected $with = ['teeth.markings'];

    protected $hidden = ['teeth', 'has_data'];

    protected $appends = ['odontogram_data'];

    private bool $hasPendingData = false;

    private ?array $pendingData = null;

    public function teeth()
    {
        return $this->hasMany(AppointmentOdontogramTooth::class)->orderBy('position');
    }

    public function getOdontogramDataAttribute(): ?array
    {
        if ($this->hasPendingData) {
            return $this->pendingData;
        }

        return $this->has_data ? AppointmentOdontogramStorage::decode($this->teeth) : null;
    }

    public function setOdontogramDataAttribute(?array $data): void
    {
        AppointmentOdontogramStorage::validate($data);
        $this->pendingData = $data;
        $this->hasPendingData = true;
        $this->attributes['has_data'] = $data !== null;
    }

    public function save(array $options = [])
    {
        if (! $this->hasPendingData) {
            return parent::save($options);
        }

        return $this->getConnection()->transaction(function () use ($options) {
            if (! parent::save($options)) {
                return false;
            }
            // Serialize concurrent replacements even when the header has no dirty fields.
            $this->newQuery()->withoutEagerLoads()->whereKey($this->id)->lockForUpdate()->first();
            $this->getConnection()->table($this->getTable())->where('id', $this->id)
                ->update(['has_data' => $this->pendingData !== null]);
            AppointmentOdontogramStorage::replace($this->getConnection(), $this->id, $this->pendingData);
            $this->unsetRelation('teeth');
            $this->hasPendingData = false;
            $this->pendingData = null;

            return true;
        });
    }

    public function procedure()
    {
        return $this->belongsTo(AppointmentProcedure::class, 'appointment_procedure_id');
    }
}
