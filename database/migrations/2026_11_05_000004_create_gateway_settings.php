<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_gateway_settings', function (Blueprint $table) {
            $table->string('gateway')->primary();
            $table->boolean('enabled')->default(false);
            $table->boolean('test_mode')->default(true);
            $table->string('currency', 8)->default('USD');
            $table->decimal('fee_percent', 8, 2)->default(0);
            $table->decimal('fee_fixed', 8, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_gateway_settings');
    }
};
