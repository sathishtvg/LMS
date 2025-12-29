<?php

namespace App\Models;


use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
  use BelongsToTenant;

    protected $fillable = [
        'course_id','user_id','enrollment_id',
        'certificate_no','issued_at','expires_at',
        'status','pdf_path','verification_token'
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function course(): BelongsTo { return $this->belongsTo(Course::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function enrollment(): BelongsTo { return $this->belongsTo(Enrollment::class); }
}
