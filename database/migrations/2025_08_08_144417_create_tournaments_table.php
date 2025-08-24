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
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('game_type_id')->constrained('game_types')->onDelete('cascade');
            $table->foreignId('organiser_id')->constrained('users')->onDelete('cascade');
            $table->string('location');
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->datetime('starts_at');
            $table->datetime('ends_at');
            $table->datetime('registration_deadline');
            $table->integer('max_participants')->nullable();
            $table->integer('min_participants')->default(2);
            $table->decimal('entry_fee', 8, 2)->default(0);
            $table->decimal('prize_pool', 10, 2)->nullable();
            $table->text('prize_description')->nullable();
            $table->enum('skill_level', [1, 2, 3, 4])->default(1);
            $table->enum('status', [
                'draft',
                'open',
                'filling-fast',
                'almost-full',
                'registration-closed',
                'in-progress',
                'completed',
                'cancelled'
            ])->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->text('rules')->nullable();
            $table->string('format')->default('single-elimination');
            $table->string('bracket_type')->default('standard');
            $table->boolean('registration_enabled')->default(true);
            $table->boolean('waiting_list_enabled')->default(false);
            $table->timestamps();

            $table->index(['status', 'starts_at']);
            $table->index(['game_type_id', 'status']);
            $table->index(['organiser_id']);
            $table->index(['is_featured']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};
