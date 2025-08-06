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
        Schema::create('game_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organiser_id')->constrained('users')->cascadeOnDelete();
            $table->tinyInteger('skill_level');
            $table->string('location');
            $table->dateTime('starts_at');
            $table->boolean('venue_booked')->default(false);
            $table->unsignedInteger('max_participants')->nullable(); // 10 max players
            $table->boolean('waiting_list_enabled')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_events');
    }
};
