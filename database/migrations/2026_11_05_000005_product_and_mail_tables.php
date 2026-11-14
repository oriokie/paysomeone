<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->boolean('orders')->default(true);
            $table->boolean('messages')->default(true);
            $table->boolean('payments')->default(true);
            $table->timestamps();
        });

        Schema::create('app_mail_logs', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('subject');
            $table->string('category', 32);
            $table->string('status', 32);
            $table->text('detail')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('app_saved_briefs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('title');
            $table->text('brief');
            $table->timestamps();
        });

        Schema::create('app_disputes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('order_id');
            $table->text('reason');
            $table->string('status', 32)->default('open');
            $table->text('resolution')->nullable();
            $table->unsignedBigInteger('staff_id')->nullable();
            $table->timestamps();
        });

        Schema::create('app_account_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('type', 32);
            $table->string('status', 32)->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('app_review_consents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rating_id');
            $table->unsignedBigInteger('customer_id');
            $table->boolean('consented')->default(false);
            $table->timestamps();
            $table->unique('rating_id');
        });

        Schema::create('app_job_heartbeats', function (Blueprint $table) {
            $table->string('name')->primary();
            $table->string('status', 32);
            $table->text('detail')->nullable();
            $table->timestamp('ran_at')->nullable();
        });

        Schema::create('app_deadline_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->date('alert_on');
            $table->unique(['order_id', 'alert_on']);
        });

        Schema::create('app_staff_resets', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('token');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_staff_resets');
        Schema::dropIfExists('app_deadline_alerts');
        Schema::dropIfExists('app_job_heartbeats');
        Schema::dropIfExists('app_review_consents');
        Schema::dropIfExists('app_account_requests');
        Schema::dropIfExists('app_disputes');
        Schema::dropIfExists('app_saved_briefs');
        Schema::dropIfExists('app_mail_logs');
        Schema::dropIfExists('app_notification_preferences');
    }
};
