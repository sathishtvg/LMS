<?php
namespace App\Models;


use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
  use BelongsToTenant;

    protected $fillable = [
        'code','status','default_language','available_languages_json',
        'certificate_enabled','certificate_validity_json','template_id',
        'created_by','completion_rules_json'
    ];

    protected $casts = [
        'available_languages_json' => 'array',
        'certificate_enabled' => 'boolean',
        'certificate_validity_json' => 'array',
        'completion_rules_json' => 'array'
    ];

    public function translations(): HasMany { return $this->hasMany(CourseTranslation::class); }
    public function modules(): HasMany { return $this->hasMany(Module::class); }
    public function assessments(): HasMany { return $this->hasMany(Assessment::class); }
}
