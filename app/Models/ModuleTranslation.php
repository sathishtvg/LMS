<?php
namespace App\Models;


use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ModuleTranslation extends Model
{
  use BelongsToTenant;

    public $timestamps = false;
    protected $fillable = ['module_id','lang','title'];
}
