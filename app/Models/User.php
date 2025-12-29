<?php
namespace App\Models;


use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
  use BelongsToTenant;

    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name','email','phone','password','role','language','status','last_login_at'
    ];

    protected $hidden = ['password','remember_token'];

    protected $casts = [
        'last_login_at' => 'datetime',
    ];
}
