<?php
namespace App\Models;


use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class QuestionTranslation extends Model
{
  use BelongsToTenant;

  public $timestamps = false;
  protected $fillable = ['question_id','lang','question_text','explanation_text'];

  public function question(){ return $this->belongsTo(Question::class); }
}
