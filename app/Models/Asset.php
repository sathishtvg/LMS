<?php
namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model {
  use BelongsToTenant;

  protected $fillable=['lesson_id','asset_type','storage_driver','path_or_url','is_external','meta_json'];
  protected $casts=['is_external'=>'boolean','meta_json'=>'array'];
}
