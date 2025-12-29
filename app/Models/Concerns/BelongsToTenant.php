<?php

namespace App\Models\Concerns;

use App\Support\Tenancy;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
  protected static function bootBelongsToTenant(): void
  {
    static::creating(function ($model) {
      if (property_exists($model, 'tenant_id') || isset($model->tenant_id)) {
        if (!$model->tenant_id) {
          $model->tenant_id = Tenancy::id();
        }
      }
    });

    static::addGlobalScope('tenant', function (Builder $builder) {
      // Allow bypass by setting app('tenant_bypass') true (for super-admin / maintenance)
      if (app()->bound('tenant_bypass') && app('tenant_bypass') === true) return;

      $builder->where($builder->getModel()->getTable() . '.tenant_id', Tenancy::id());
    });
  }
}
