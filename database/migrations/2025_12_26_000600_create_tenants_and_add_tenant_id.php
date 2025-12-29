<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
  public function up(): void {

    // Tenants
    if (!Schema::hasTable('tenants')) {
      Schema::create('tenants', function(Blueprint $table){
        $table->id();
        $table->string('code',50)->unique();
        $table->string('name',190);
        $table->string('subdomain',80)->nullable()->unique();
        $table->enum('status',['active','suspended'])->default('active');
        $table->json('branding_json')->nullable(); // {portal_name, company_name, primary_color, logo_path}
        $table->timestamps();
      });
    }

    // Helper: add tenant_id if missing
    $tables = [
      'users','courses','course_translations','modules','module_translations','lessons','lesson_translations','assets',
      'enrollments','lesson_progress','course_completion',
      'assessments','question_banks','questions','question_translations','question_options','option_translations',
      'attempts','attempt_answers',
      'certificate_templates','certificates','certificate_verifications',
      'system_settings','audit_logs'
    ];

    foreach ($tables as $t) {
      if (Schema::hasTable($t) && !Schema::hasColumn($t,'tenant_id')) {
        Schema::table($t, function(Blueprint $table) use ($t){
          // Keep nullable for legacy rows; we will backfill to 1 below
          $table->unsignedBigInteger('tenant_id')->nullable()->index();
        });
      }
    }

    // Backfill tenant_id=1 where null for existing data
    foreach ($tables as $t) {
      if (Schema::hasTable($t) && Schema::hasColumn($t,'tenant_id')) {
        DB::table($t)->whereNull('tenant_id')->update(['tenant_id' => 1]);
      }
    }

    // users unique constraints should be tenant-scoped (drop global unique if exists, then add composite)
    if (Schema::hasTable('users')) {
      // Drop old uniques if present
      try { DB::statement('ALTER TABLE `users` DROP INDEX `users_email_unique`'); } catch (\Throwable $e) {}
      try { DB::statement('ALTER TABLE `users` DROP INDEX `users_phone_unique`'); } catch (\Throwable $e) {}

      // Add composite uniques
      try { DB::statement('ALTER TABLE `users` ADD UNIQUE `users_tenant_email_unique` (`tenant_id`,`email`)'); } catch (\Throwable $e) {}
      try { DB::statement('ALTER TABLE `users` ADD UNIQUE `users_tenant_phone_unique` (`tenant_id`,`phone`)'); } catch (\Throwable $e) {}

      // Ensure tenant_id is not null
      Schema::table('users', function(Blueprint $table){
        $table->unsignedBigInteger('tenant_id')->default(1)->change();
      });
    }

    // courses code unique should be tenant-scoped
    if (Schema::hasTable('courses')) {
      try { DB::statement('ALTER TABLE `courses` DROP INDEX `courses_code_unique`'); } catch (\Throwable $e) {}
      try { DB::statement('ALTER TABLE `courses` ADD UNIQUE `courses_tenant_code_unique` (`tenant_id`,`code`)'); } catch (\Throwable $e) {}
      Schema::table('courses', function(Blueprint $table){
        $table->unsignedBigInteger('tenant_id')->default(1)->change();
      });
    }

    // enrollments unique tenant/course/user
    if (Schema::hasTable('enrollments')) {
      try { DB::statement('ALTER TABLE `enrollments` ADD UNIQUE `enrollments_tenant_course_user_unique` (`tenant_id`,`course_id`,`user_id`)'); } catch (\Throwable $e) {}
      Schema::table('enrollments', function(Blueprint $table){
        $table->unsignedBigInteger('tenant_id')->default(1)->change();
      });
    }

    // certificates verify token already unique; ensure tenant_id not null
    foreach (['certificates','assessments','attempts','certificate_templates'] as $t) {
      if (Schema::hasTable($t) && Schema::hasColumn($t,'tenant_id')) {
        Schema::table($t, function(Blueprint $table){
          $table->unsignedBigInteger('tenant_id')->default(1)->change();
        });
      }
    }
  }

  public function down(): void {
    // Keep tenant_id columns to avoid data loss; only drop tenants table
    if (Schema::hasTable('tenants')) Schema::drop('tenants');
  }
};
