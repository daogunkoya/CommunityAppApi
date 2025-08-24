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
        Schema::table('users', function (Blueprint $table) {
            // Only add columns that don't exist
            if (!Schema::hasColumn('users', 'radius')) {
                $table->integer('radius')->default(5)->after('longitude');
            }
            if (!Schema::hasColumn('users', 'main_goal')) {
                $table->text('main_goal')->nullable()->after('radius');
            }
            if (!Schema::hasColumn('users', 'auth_provider')) {
                $table->string('auth_provider')->nullable()->after('main_goal');
            }
            if (!Schema::hasColumn('users', 'auth_provider_id')) {
                $table->string('auth_provider_id')->nullable()->after('auth_provider');
            }
            if (!Schema::hasColumn('users', 'allow_notifications')) {
                $table->boolean('allow_notifications')->default(false)->after('auth_provider_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'radius',
                'main_goal',
                'auth_provider',
                'auth_provider_id',
                'allow_notifications',
            ]);
        });
    }
};
