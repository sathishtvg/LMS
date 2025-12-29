<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('users', function (Blueprint $table) {
      $table->id();
      $table->string('name',120);
      $table->string('email',190)->nullable()->unique();
      $table->string('phone',30)->nullable()->unique();
      $table->string('password');
      $table->enum('role',['admin','trainer','learner'])->default('learner');
      $table->enum('language',['en','ms','ta','zh'])->default('en');
      $table->enum('status',['active','inactive'])->default('active');
      $table->timestamp('last_login_at')->nullable();
      $table->rememberToken();
      $table->timestamps();
    });
  }
  public function down(): void { Schema::dropIfExists('users'); }
};
