<?php
namespace App\Models;


use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class OptionTranslation extends Model
{
  use BelongsToTenant;

  public $timestamps = false;
  protected $fillable = ['option_id','lang','option_text'];

  public function option(){ return $this->belongsTo(QuestionOption::class,'option_id'); }
}
