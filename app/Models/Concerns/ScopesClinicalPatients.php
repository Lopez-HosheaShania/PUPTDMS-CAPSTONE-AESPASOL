<?php

namespace App\Models\Concerns;

use App\Services\ClinicalAccessService;
use Illuminate\Database\Eloquent\Builder;

trait ScopesClinicalPatients
{
    public static function bootScopesClinicalPatients(): void
    {
        static::addGlobalScope('clinical_patients', function (Builder $query) {
            app(ClinicalAccessService::class)->scope($query);
        });
    }
}
