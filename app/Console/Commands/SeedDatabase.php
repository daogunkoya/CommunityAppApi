<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\DatabaseSeeder;

class SeedDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:db {--fresh : Run migrations fresh before seeding}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the database with comprehensive test data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🌱 Starting database seeding process...');

        if ($this->option('fresh')) {
            $this->info('🔄 Running fresh migrations...');
            $this->call('migrate:fresh');
        }

        $this->info('📊 Seeding database with comprehensive data...');
        $this->call('db:seed');

        $this->info('✅ Database seeded successfully!');
        $this->info('');
        $this->info('📋 Test Account:');
        $this->info('   Email: john@example.com');
        $this->info('   Password: password');
        $this->info('');
        $this->info('📊 Data Created:');
        $this->info('   • 15 Sport Types (Football, Basketball, Tennis, etc.)');
        $this->info('   • 21 Users (including test user)');
        $this->info('   • 20 Game Events (upcoming and ongoing)');
        $this->info('   • 25 Discussions with realistic topics');
        $this->info('   • 75+ Comments on discussions');
        $this->info('   • 100+ Likes on discussions');
        $this->info('   • 50+ Event Participants');
        $this->info('');
        $this->info('🚀 Ready to test the application!');
    }
}
