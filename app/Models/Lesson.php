<?php
namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model {
  use BelongsToTenant;

  protected $fillable=['module_id','type','sort_order','required','min_watch_percent','must_view_all_slides'];
  protected $casts=['required'=>'boolean','must_view_all_slides'=>'boolean'];
  public function translations(): HasMany { return $this->hasMany(LessonTranslation::class); }
  public function assets(): HasMany { return $this->hasMany(Asset::class); }
}
