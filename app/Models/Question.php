<?php
namespace App\Models;


use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
  use BelongsToTenant;

  use SoftDeletes;

  protected $fillable = ['bank_id','type','is_critical','points','sort_order','meta_json'];
  protected $casts = ['is_critical'=>'boolean','meta_json'=>'array'];

  public function bank(){ return $this->belongsTo(QuestionBank::class,'bank_id'); }
  public function translations(){ return $this->hasMany(QuestionTranslation::class); }
  public function options(){ return $this->hasMany(QuestionOption::class)->orderBy('sort_order'); }
}
