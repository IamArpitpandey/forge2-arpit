<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;

/**
 * Global scope that restricts all queries on a tenant-scoped model to the
 * current tenant's organization_id.
 *
 * The tenant id is resolved from, in order of preference:
 *   1. Context::get('tenant_id')  — set explicitly by middleware/tests
 *   2. Auth::user()->organization_id — resolved from the Sanctum token
 *
 * To bypass the scope (e.g. in seeders), use Model::withoutTenantScope().
 */
class BelongsToTenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = Context::get('tenant_id')
            ?? (Auth::check() ? Auth::user()->organization_id : null);

        if ($tenantId) {
            $builder->where($model->getTable() . '.organization_id', $tenantId);
        }
    }

    /**
     * Extension: allow calling Model::withoutTenantScope() to bypass.
     */
    public function extend(Builder $builder): void
    {
        $builder->macro('withoutTenantScope', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
    }
}
