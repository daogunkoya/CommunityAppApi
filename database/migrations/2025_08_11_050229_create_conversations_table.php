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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['direct', 'group', 'tournament', 'community']);
            $table->string('name')->nullable();
            $table->unsignedBigInteger('context_id')->nullable(); // For tournament/community chats
            $table->unsignedBigInteger('last_message_id')->nullable();
            $table->timestamps();

            $table->index(['type', 'context_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
