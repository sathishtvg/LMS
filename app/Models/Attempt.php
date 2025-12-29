<?php
namespace App\Models;


use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Attempt extends Model
{
  use BelongsToTenant;

  protected $fillable = [
    'assessment_id','enrollment_id','started_at','submitted_at','score_percent','passed','critical_wrong_count','status'
  ];

  protected $casts = [
    'started_at'=>'datetime',
    'submitted_at'=>'datetime',
    'passed'=>'boolean',
  ];

  public function assessment(){ return $this->belongsTo(Assessment::class); }
  public function enrollment(){ return $this->belongsTo(Enrollment::class); }
  public function answers(){ return $this->hasMany(AttemptAnswer::class); }
}
