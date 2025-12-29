<?php
namespace App\Models;


use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class LessonTranslation extends Model
{
  use BelongsToTenant;

    public $timestamps = false;
    protected $fillable = ['lesson_id','lang','title','description'];
}
