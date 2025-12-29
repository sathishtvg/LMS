<?php
namespace App\Models;


use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class QuestionBank extends Model
{
  use BelongsToTenant;

  protected $fillable = ['assessment_id','name'];

  public function assessment(){ return $this->belongsTo(Assessment::class); }
  public function questions(){ return $this->hasMany(Question::class,'bank_id')->orderBy('sort_order'); }
}
