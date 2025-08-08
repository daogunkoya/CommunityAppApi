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
        Schema::table('game_events', function (Blueprint $table) {
            $table->foreignId('community_id')->nullable()->after('organiser_id')->constrained()->nullOnDelete();
            $table->index('community_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_events', function (Blueprint $table) {
            $table->dropForeign(['community_id']);
            $table->dropIndex(['community_id']);
            $table->dropColumn('community_id');
        });
    }
};
