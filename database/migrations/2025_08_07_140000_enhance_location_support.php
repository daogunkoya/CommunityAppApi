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
        // Add enhanced location fields to users table
        Schema::table('users', function (Blueprint $table) {
            $table->string('address')->nullable()->after('location');
            $table->string('city')->nullable()->after('address');
            $table->string('state')->nullable()->after('city');
            $table->string('postal_code')->nullable()->after('state');
            $table->string('country')->nullable()->after('postal_code');
            $table->decimal('latitude', 10, 8)->nullable()->after('country');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->string('community_name')->nullable()->after('longitude'); // e.g., "Camden", "Islington"
            $table->string('borough')->nullable()->after('community_name'); // e.g., "Camden", "Islington"
            $table->boolean('location_verified')->default(false)->after('borough');
        });

        // Add location fields to game_events table
        Schema::table('game_events', function (Blueprint $table) {
            $table->string('address')->nullable()->after('location');
            $table->string('city')->nullable()->after('address');
            $table->string('state')->nullable()->after('city');
            $table->string('postal_code')->nullable()->after('state');
            $table->string('country')->nullable()->after('postal_code');
            $table->decimal('latitude', 10, 8)->nullable()->after('country');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->string('community_name')->nullable()->after('longitude');
            $table->string('borough')->nullable()->after('community_name');
        });

        // Create communities table for managing community-specific features
        Schema::create('communities', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Camden", "Islington"
            $table->string('type')->default('borough'); // borough, district, neighborhood
            $table->string('city');
            $table->string('state');
            $table->string('country');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['name', 'city', 'state', 'country']);
            $table->index(['city', 'state', 'country']);
            $table->index(['latitude', 'longitude']);
        });

        // Create user_communities table for user-community relationships
        Schema::create('user_communities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('community_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false); // User's primary community
            $table->boolean('is_active')->default(true);
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'community_id']);
            $table->index(['user_id', 'is_primary']);
        });

        // Add indexes for better performance
        Schema::table('users', function (Blueprint $table) {
            $table->index(['city', 'state', 'country']);
            $table->index(['community_name', 'borough']);
            $table->index(['latitude', 'longitude']);
            $table->index('location_verified');
        });

        Schema::table('game_events', function (Blueprint $table) {
            $table->index(['city', 'state', 'country']);
            $table->index(['community_name', 'borough']);
            $table->index(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop user_communities table
        Schema::dropIfExists('user_communities');

        // Drop communities table
        Schema::dropIfExists('communities');

        // Remove enhanced location fields from game_events
        Schema::table('game_events', function (Blueprint $table) {
            $table->dropIndex(['city', 'state', 'country']);
            $table->dropIndex(['community_name', 'borough']);
            $table->dropIndex(['latitude', 'longitude']);

            $table->dropColumn([
                'address', 'city', 'state', 'postal_code', 'country',
                'latitude', 'longitude', 'community_name', 'borough'
            ]);
        });

        // Remove enhanced location fields from users
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['city', 'state', 'country']);
            $table->dropIndex(['community_name', 'borough']);
            $table->dropIndex(['latitude', 'longitude']);
            $table->dropIndex('location_verified');

            $table->dropColumn([
                'address', 'city', 'state', 'postal_code', 'country',
                'latitude', 'longitude', 'community_name', 'borough', 'location_verified'
            ]);
        });
    }
};
