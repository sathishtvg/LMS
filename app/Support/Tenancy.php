<?php

namespace App\Support;

use App\Models\Tenant;

class Tenancy
{
  public static function tenant(): ?Tenant
  {
    return app()->bound('tenant') ? app('tenant') : null;
  }

  public static function id(): int
  {
    return (int) (self::tenant()?->id ?? 1);
  }
}
