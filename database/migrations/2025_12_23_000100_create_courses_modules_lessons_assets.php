<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('courses', function(Blueprint $table){
      $table->id();
      $table->string('code',50)->unique();
      $table->enum('status',['draft','published','archived'])->default('draft');
      $table->enum('default_language',['en','ms','ta','zh'])->default('en');
      $table->json('available_languages_json')->nullable();
      $table->json('completion_rules_json')->nullable(); // sequential lock, min watch, must view all
      $table->boolean('certificate_enabled')->default(true);
      $table->json('certificate_validity_json')->nullable(); // {type: days|months|date, value: n|YYYY-MM-DD}
      $table->unsignedBigInteger('template_id')->nullable();
      $table->unsignedBigInteger('created_by')->nullable();
      $table->timestamps();
      $table->softDeletes();
    });

    Schema::create('course_translations', function(Blueprint $table){
      $table->id();
      $table->unsignedBigInteger('course_id');
      $table->enum('lang',['en','ms','ta','zh']);
      $table->string('title',190);
      $table->text('description')->nullable();
      $table->unique(['course_id','lang']);
      $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
    });

    Schema::create('modules', function(Blueprint $table){
      $table->id();
      $table->unsignedBigInteger('course_id');
      $table->integer('sort_order')->default(1);
      $table->timestamps();
      $table->softDeletes();
      $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
    });

    Schema::create('module_translations', function(Blueprint $table){
      $table->id();
      $table->unsignedBigInteger('module_id');
      $table->enum('lang',['en','ms','ta','zh']);
      $table->string('title',190);
      $table->unique(['module_id','lang']);
      $table->foreign('module_id')->references('id')->on('modules')->cascadeOnDelete();
    });

    Schema::create('lessons', function(Blueprint $table){
      $table->id();
      $table->unsignedBigInteger('module_id');
      $table->enum('type',['video','pdf','ppt','other']);
      $table->integer('sort_order')->default(1);
      $table->boolean('required')->default(true);
      $table->integer('min_watch_percent')->nullable();
      $table->boolean('must_view_all_slides')->default(true);
      $table->timestamps();
      $table->softDeletes();
      $table->foreign('module_id')->references('id')->on('modules')->cascadeOnDelete();
    });

    Schema::create('lesson_translations', function(Blueprint $table){
      $table->id();
      $table->unsignedBigInteger('lesson_id');
      $table->enum('lang',['en','ms','ta','zh']);
      $table->string('title',190);
      $table->text('description')->nullable();
      $table->unique(['lesson_id','lang']);
      $table->foreign('lesson_id')->references('id')->on('lessons')->cascadeOnDelete();
    });

    Schema::create('assets', function(Blueprint $table){
      $table->id();
      $table->unsignedBigInteger('lesson_id');
      $table->enum('asset_type',['video','pdf','ppt','image','other']);
      $table->string('storage_driver',50)->default('local');
      $table->text('path_or_url');
      $table->boolean('is_external')->default(false);
      $table->json('meta_json')->nullable();
      $table->timestamps();
      $table->foreign('lesson_id')->references('id')->on('lessons')->cascadeOnDelete();
    });

    Schema::table('courses', function(Blueprint $table){
      $table->index(['status']);
    });
  }

  public function down(): void {
    Schema::dropIfExists('assets');
    Schema::dropIfExists('lesson_translations');
    Schema::dropIfExists('lessons');
    Schema::dropIfExists('module_translations');
    Schema::dropIfExists('modules');
    Schema::dropIfExists('course_translations');
    Schema::dropIfExists('courses');
  }
};
