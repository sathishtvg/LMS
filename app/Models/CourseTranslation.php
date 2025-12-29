<?php
namespace App\Models;


use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class CourseTranslation extends Model
{
  use BelongsToTenant;

    public $timestamps = false;
    protected $fillable = ['course_id','lang','title','description'];
}
