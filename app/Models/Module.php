<?php
namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model {
  use BelongsToTenant;

  protected $fillable=['course_id','sort_order'];
  public function translations(): HasMany { return $this->hasMany(ModuleTranslation::class); }
  public function lessons(): HasMany { return $this->hasMany(Lesson::class); }
}
