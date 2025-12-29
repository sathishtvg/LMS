<?php
namespace App\Models;


use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enrollment extends Model
{
  use BelongsToTenant;

  protected $fillable=['course_id','user_id','status','assigned_by','assigned_at','due_date'];
  protected $casts=['assigned_at'=>'datetime','due_date'=>'date'];

  public function course(): BelongsTo { return $this->belongsTo(Course::class); }
  public function user(): BelongsTo { return $this->belongsTo(User::class); }
  public function lessonProgress(): HasMany { return $this->hasMany(LessonProgress::class); }
}
