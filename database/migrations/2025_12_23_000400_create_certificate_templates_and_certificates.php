<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('certificate_templates', function(Blueprint $table){
      $table->id();
      $table->string('code',50)->unique();
      $table->string('name',120);
      $table->json('layout_json');
      $table->timestamps();
    });

    Schema::create('certificates', function(Blueprint $table){
      $table->id();
      $table->unsignedBigInteger('course_id');
      $table->unsignedBigInteger('user_id');
      $table->unsignedBigInteger('enrollment_id');
      $table->string('certificate_no',60)->unique();
      $table->timestamp('issued_at');
      $table->timestamp('expires_at')->nullable();
      $table->enum('status',['issued','revoked','expired'])->default('issued');
      $table->text('pdf_path')->nullable();
      $table->string('verification_token',64)->unique();
      $table->timestamps();
      $table->index(['user_id','course_id']);
      $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
      $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
      $table->foreign('enrollment_id')->references('id')->on('enrollments')->cascadeOnDelete();
    });

    Schema::create('certificate_verifications', function(Blueprint $table){
      $table->string('token',64)->primary();
      $table->unsignedBigInteger('certificate_id');
      $table->timestamp('created_at');
      $table->foreign('certificate_id')->references('id')->on('certificates')->cascadeOnDelete();
    });
  }
  public function down(): void {
    Schema::dropIfExists('certificate_verifications');
    Schema::dropIfExists('certificates');
    Schema::dropIfExists('certificate_templates');
  }
};
