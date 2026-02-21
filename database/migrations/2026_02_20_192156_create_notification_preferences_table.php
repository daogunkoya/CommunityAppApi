<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('category'); // e.g. 'messages', 'game_invites', 'discussions', 'marketing', 'system'
            $table->boolean('channel_push')->default(true);
            $table->boolean('channel_email')->default(true);
            $table->boolean('channel_sms')->default(false);
            $table->enum('frequency', ['instant', 'daily', 'weekly', 'never'])->default('instant');
            $table->timestamps();

            // A user should only have ONE preference row per category to avoid conflicts
            $table->unique(['user_id', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
