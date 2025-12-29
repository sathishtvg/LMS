<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('assessments', function(Blueprint $table){
      $table->id();
      $table->unsignedBigInteger('course_id');
      $table->string('title',190);
      $table->enum('mode',['quiz','scenario'])->default('quiz');
      $table->integer('duration_sec')->nullable();
      $table->integer('attempts_limit')->nullable();
      $table->integer('pass_percent')->default(80);
      $table->boolean('critical_enabled')->default(true);
      $table->integer('allowed_critical_mistakes')->default(0);
      $table->boolean('shuffle_questions')->default(true);
      $table->boolean('shuffle_options')->default(true);
      $table->json('rules_json')->nullable();
      $table->timestamps();
      $table->softDeletes();
      $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
    });

    Schema::create('attempts', function(Blueprint $table){
      $table->id();
      $table->unsignedBigInteger('assessment_id');
      $table->unsignedBigInteger('enrollment_id');
      $table->timestamp('started_at')->nullable();
      $table->timestamp('submitted_at')->nullable();
      $table->decimal('score_percent',5,2)->default(0);
      $table->boolean('passed')->default(false);
      $table->integer('critical_wrong_count')->default(0);
      $table->enum('status',['in_progress','submitted','passed','failed'])->default('in_progress');
      $table->timestamps();
      $table->index(['assessment_id','enrollment_id']);
      $table->foreign('assessment_id')->references('id')->on('assessments')->cascadeOnDelete();
      $table->foreign('enrollment_id')->references('id')->on('enrollments')->cascadeOnDelete();
    });
  }
  public function down(): void {
    Schema::dropIfExists('attempts');
    Schema::dropIfExists('assessments');
  }
};
