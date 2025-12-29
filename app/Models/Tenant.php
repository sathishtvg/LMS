<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
  protected $fillable = ['code','name','subdomain','status','branding_json'];

  protected $casts = [
    'branding_json' => 'array',
  ];
}
