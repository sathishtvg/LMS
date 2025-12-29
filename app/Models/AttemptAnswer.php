<?php
namespace App\Models;


use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class AttemptAnswer extends Model
{
  use BelongsToTenant;

  protected $fillable = ['attempt_id','question_id','answer_json','is_correct','score_awarded'];
  protected $casts = ['answer_json'=>'array','is_correct'=>'boolean'];

  public function attempt(){ return $this->belongsTo(Attempt::class); }
  public function question(){ return $this->belongsTo(Question::class); }
}
