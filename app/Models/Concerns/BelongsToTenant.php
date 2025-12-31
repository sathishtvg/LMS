<?php

namespace App\Models\Concerns;

use App\Support\Tenancy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::creating(function ($model) {
            // Set tenant_id automatically if the table has the column
            if (self::modelHasTenantColumn($model) && empty($model->tenant_id)) {
                $model->tenant_id = Tenancy::id();
            }
        });

        static::addGlobalScope('tenant', function (Builder $builder) {
            if (app()->bound('tenant_bypass') && app('tenant_bypass') === true) return;

            $builder->where(
                $builder->getModel()->getTable() . '.tenant_id',
                Tenancy::id()
            );
        });
    }

    protected static function modelHasTenantColumn($model): bool
    {
        static $cache = [];

        $class = get_class($model);
        if (array_key_exists($class, $cache)) return $cache[$class];

        try {
            $table = $model->getTable();
            $conn = $model->getConnectionName();
            $cache[$class] = Schema::connection($conn)->hasColumn($table, 'tenant_id');
        } catch (\Throwable $e) {
            $cache[$class] = false;
        }

        return $cache[$class];
    }
}
