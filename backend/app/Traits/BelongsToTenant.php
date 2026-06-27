<?php

namespace App\Traits;

use App\Scopes\BelongsToTenantScope;
use Illuminate\Support\Facades\Context;

/**
 * Apply this trait to any Eloquent model that is scoped to an Organization.
 * It auto-registers a global scope that injects WHERE organization_id = <current tenant>
 * on every query, so Org A can never read Org B's data.
 *
 * The tenant id is resolved from the authenticated user's organization_id at query time.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new BelongsToTenantScope);

        static::creating(function ($model) {
            if ($model->organization_id === null) {
                $model->organization_id = Context::get('tenant_id');
            }
        });
    }
}
