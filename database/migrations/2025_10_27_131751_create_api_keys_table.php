<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('key')->unique();
            $table->string('secret');
            $table->boolean('is_active')->default(true);
            $table->json('permissions')->nullable(); // ['web_push', 'email', 'whatsapp', 'Telegram']
            $table->integer('rate_limit')->default(1000); // Requests per hour
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index('key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
