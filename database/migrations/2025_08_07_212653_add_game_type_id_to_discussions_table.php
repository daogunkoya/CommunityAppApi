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
        Schema::table('discussions', function (Blueprint $table) {
            $table->foreignId('game_type_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->index('game_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('discussions', function (Blueprint $table) {
            $table->dropForeign(['game_type_id']);
            $table->dropIndex(['game_type_id']);
            $table->dropColumn('game_type_id');
        });
    }
};
