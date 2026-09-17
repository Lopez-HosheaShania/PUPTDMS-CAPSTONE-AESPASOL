<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDeactivation extends Model
{
    use \App\Models\Concerns\StoresOptionalDetails;
    use HasFactory;

    protected function detailFields(): array
    {
        return [
            'employmentSnapshot' => ['employment_status', 'last_working_date'],
            'accessSnapshot' => ['account_status', 'access_ends_at'],
        ];
    }

    public function employmentSnapshot()
    {
        return $this->hasOne(UserDeactivationEmployment::class);
    }

    public function accessSnapshot()
    {
        return $this->hasOne(UserDeactivationAccess::class);
    }

    public function refresh()
    {
        parent::refresh();
        if ($this->exists) {
            $this->pendingOptionalDetails = [];
        }

        return $this;
    }

    protected $fillable = [
        'user_id',
        'deactivated_by',
        'employment_status',
        'account_status',
        'last_working_date',
        'access_ends_at',
        'deactivated_at',
        'reason',
    ];

    protected $casts = [
        'last_working_date' => 'date',
        'access_ends_at' => 'datetime',
        'deactivated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function deactivatedBy()
    {
        return $this->belongsTo(User::class, 'deactivated_by');
    }
}
