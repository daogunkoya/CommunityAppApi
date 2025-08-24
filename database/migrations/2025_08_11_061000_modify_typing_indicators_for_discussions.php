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
        // For SQLite, we need to recreate the table to rename columns
        if (config('database.default') === 'sqlite') {
            // Drop the existing table and recreate it
            Schema::dropIfExists('typing_indicators');

            Schema::create('typing_indicators', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->unsignedBigInteger('context_id');
                $table->string('context_type')->default('conversation');
                $table->timestamp('started_at');
                $table->timestamp('expires_at');
                $table->timestamps();

                // Ensure one typing indicator per user per context
                $table->unique(['user_id', 'context_id', 'context_type']);
            });
        } else {
            // For other databases, add context_type and rename the column
            Schema::table('typing_indicators', function (Blueprint $table) {
                $table->string('context_type')->default('conversation')->after('conversation_id');
            });

            Schema::table('typing_indicators', function (Blueprint $table) {
                $table->renameColumn('conversation_id', 'context_id');
            });

            // Check if the unique constraint exists before dropping it
            $indexExists = Schema::hasIndex('typing_indicators', 'typing_indicators_user_id_context_id_unique');
            if ($indexExists) {
                Schema::table('typing_indicators', function (Blueprint $table) {
                    $table->dropUnique(['user_id', 'context_id']);
                });
            }
            
            Schema::table('typing_indicators', function (Blueprint $table) {
                $table->unique(['user_id', 'context_id', 'context_type']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('typing_indicators', function (Blueprint $table) {
            // Revert the changes
            $table->dropUnique(['user_id', 'context_id', 'context_type']);
            $table->dropColumn('context_type');
            $table->renameColumn('context_id', 'conversation_id');
            $table->unique(['user_id', 'conversation_id']);
        });
    }
};
