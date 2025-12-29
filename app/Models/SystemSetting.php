<?php

namespace App\Models;


use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
  use BelongsToTenant;

    protected $fillable = ['key','value_json','updated_by'];
    protected $casts = ['value_json' => 'array'];
}
