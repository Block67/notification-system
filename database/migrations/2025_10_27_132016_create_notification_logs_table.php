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
        Schema::create('notifications_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('api_key_id');
            $table->string('type'); // web_push, email, whatsapp
            $table->string('recipient');
            $table->string('status'); // pending, sent, failed
            $table->json('payload');
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            // Clé étrangère vers api_keys
            $table->foreign('api_key_id')
                ->references('id')
                ->on('api_keys')
                ->onDelete('cascade');

            // Index
            $table->index(['api_key_id', 'created_at']);
            $table->index('status');
        });

        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('endpoint')->unique();
            $table->string('public_key')->nullable();
            $table->string('auth_token')->nullable();
            $table->string('content_encoding')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications_log');
        Schema::dropIfExists('push_subscriptions');
    }
};
