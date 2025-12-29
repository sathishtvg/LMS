<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('question_banks', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('assessment_id');
      $table->string('name',190);
      $table->timestamps();
      $table->foreign('assessment_id')->references('id')->on('assessments')->cascadeOnDelete();
    });

    Schema::create('questions', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('bank_id');
      $table->enum('type',['mcq','truefalse','short'])->default('mcq');
      $table->boolean('is_critical')->default(false);
      $table->integer('points')->default(1);
      $table->integer('sort_order')->default(1);
      $table->json('meta_json')->nullable();
      $table->timestamps();
      $table->softDeletes();
      $table->foreign('bank_id')->references('id')->on('question_banks')->cascadeOnDelete();
    });

    Schema::create('question_translations', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('question_id');
      $table->enum('lang',['en','ms','ta','zh'])->default('en');
      $table->text('question_text');
      $table->text('explanation_text')->nullable();
      $table->unique(['question_id','lang']);
      $table->foreign('question_id')->references('id')->on('questions')->cascadeOnDelete();
    });

    Schema::create('question_options', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('question_id');
      $table->boolean('is_correct')->default(false);
      $table->integer('sort_order')->default(1);
      $table->json('meta_json')->nullable();
      $table->timestamps();
      $table->foreign('question_id')->references('id')->on('questions')->cascadeOnDelete();
    });

    Schema::create('option_translations', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('option_id');
      $table->enum('lang',['en','ms','ta','zh'])->default('en');
      $table->text('option_text');
      $table->unique(['option_id','lang']);
      $table->foreign('option_id')->references('id')->on('question_options')->cascadeOnDelete();
    });

    Schema::create('attempt_answers', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('attempt_id');
      $table->unsignedBigInteger('question_id');
      $table->json('answer_json')->nullable();
      $table->boolean('is_correct')->default(false);
      $table->integer('score_awarded')->default(0);
      $table->timestamps();
      $table->unique(['attempt_id','question_id']);
      $table->foreign('attempt_id')->references('id')->on('attempts')->cascadeOnDelete();
      $table->foreign('question_id')->references('id')->on('questions')->cascadeOnDelete();
    });
  }

  public function down(): void {
    Schema::dropIfExists('attempt_answers');
    Schema::dropIfExists('option_translations');
    Schema::dropIfExists('question_options');
    Schema::dropIfExists('question_translations');
    Schema::dropIfExists('questions');
    Schema::dropIfExists('question_banks');
  }
};
