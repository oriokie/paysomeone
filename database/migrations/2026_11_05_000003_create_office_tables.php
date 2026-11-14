<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_role_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('employee_group');
            $table->string('permission', 80);
            $table->boolean('allowed')->default(true);
            $table->timestamps();
            $table->unique(['employee_group', 'permission']);
        });

        Schema::create('app_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->string('action', 80);
            $table->string('subject_type', 80)->nullable();
            $table->string('subject_id', 40)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('app_recovery_codes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->string('code_hash', 64);
            $table->timestamp('used_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('app_staff_devices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('app_order_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('employee_id');
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('app_saved_views', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->string('name', 80);
            $table->json('filters');
            $table->timestamps();
        });

        Schema::create('app_office_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_office_settings');
        Schema::dropIfExists('app_saved_views');
        Schema::dropIfExists('app_order_notes');
        Schema::dropIfExists('app_staff_devices');
        Schema::dropIfExists('app_recovery_codes');
        Schema::dropIfExists('app_audit_logs');
        Schema::dropIfExists('app_role_permissions');
    }
};
