<?php
namespace App\Models;


use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assessment extends Model
{
  use BelongsToTenant;

  use SoftDeletes;

  protected $fillable = [
    'course_id','title','mode','duration_sec','attempts_limit','pass_percent',
    'critical_enabled','allowed_critical_mistakes','shuffle_questions','shuffle_options','rules_json'
  ];

  protected $casts = [
    'critical_enabled'=>'boolean',
    'shuffle_questions'=>'boolean',
    'shuffle_options'=>'boolean',
    'rules_json'=>'array',
  ];

  public function course(){ return $this->belongsTo(Course::class); }
  public function banks(){ return $this->hasMany(QuestionBank::class); }
  public function attempts(){ return $this->hasMany(Attempt::class); }
}
