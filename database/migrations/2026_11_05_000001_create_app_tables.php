<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('excerpt', 400);
            $table->longText('body');
            $table->string('author_name')->default('Studio');
            $table->string('meta_description', 300)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('app_contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('company')->nullable();
            $table->text('message');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('app_project_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('service', 40);
            $table->string('name');
            $table->string('email');
            $table->string('title');
            $table->text('brief');
            $table->unsignedBigInteger('deadline_id')->nullable();
            $table->decimal('quote_total', 14, 2)->default(0);
            $table->json('quote')->nullable();
            $table->unsignedBigInteger('legacy_order_id')->nullable();
            $table->timestamps();
        });

        Schema::create('app_password_resets', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_password_resets');
        Schema::dropIfExists('app_project_requests');
        Schema::dropIfExists('app_contact_messages');
        Schema::dropIfExists('app_posts');
    }
};
