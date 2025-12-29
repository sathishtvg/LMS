<?php
namespace App\Models;


use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class QuestionOption extends Model
{
  use BelongsToTenant;

  protected $fillable = ['question_id','is_correct','sort_order','meta_json'];
  protected $casts = ['is_correct'=>'boolean','meta_json'=>'array'];

  public function question(){ return $this->belongsTo(Question::class); }
  public function translations(){ return $this->hasMany(OptionTranslation::class,'option_id'); }
}
