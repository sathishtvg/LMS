<?php
namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class LessonProgress extends Model {
  use BelongsToTenant;

  protected $fillable=['enrollment_id','lesson_id','progress_percent','completed_at','meta_json'];
  protected $casts=['completed_at'=>'datetime','meta_json'=>'array'];
}
