<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('enrollments', function(Blueprint $table){
      $table->id();
      $table->unsignedBigInteger('course_id');
      $table->unsignedBigInteger('user_id');
      $table->enum('status',['assigned','in_progress','completed','archived'])->default('assigned');
      $table->unsignedBigInteger('assigned_by')->nullable();
      $table->timestamp('assigned_at')->nullable();
      $table->date('due_date')->nullable();
      $table->timestamps();
      $table->unique(['course_id','user_id']);
      $table->index(['user_id','status']);
      $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
      $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
    });

    Schema::create('lesson_progress', function(Blueprint $table){
      $table->id();
      $table->unsignedBigInteger('enrollment_id');
      $table->unsignedBigInteger('lesson_id');
      $table->decimal('progress_percent',5,2)->default(0);
      $table->timestamp('completed_at')->nullable();
      $table->json('meta_json')->nullable();
      $table->timestamps();
      $table->unique(['enrollment_id','lesson_id']);
      $table->index(['enrollment_id','completed_at']);
      $table->foreign('enrollment_id')->references('id')->on('enrollments')->cascadeOnDelete();
      $table->foreign('lesson_id')->references('id')->on('lessons')->cascadeOnDelete();
    });

    Schema::create('course_completion', function(Blueprint $table){
      $table->id();
      $table->unsignedBigInteger('enrollment_id')->unique();
      $table->timestamp('completed_at');
      $table->foreign('enrollment_id')->references('id')->on('enrollments')->cascadeOnDelete();
    });
  }

  public function down(): void {
    Schema::dropIfExists('course_completion');
    Schema::dropIfExists('lesson_progress');
    Schema::dropIfExists('enrollments');
  }
};
