<?php
namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class CourseCompletion extends Model {
  use BelongsToTenant;

  public $timestamps=false;
  protected $fillable=['enrollment_id','completed_at'];
  protected $casts=['completed_at'=>'datetime'];
}
