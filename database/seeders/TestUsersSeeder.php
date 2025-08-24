<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test users if they don't exist
        $testUsers = [
            [
                'first_name' => 'Maria',
                'last_name' => 'Garcia',
                'email' => 'maria.garcia@example.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'is_active' => true,
                'location' => 'London, UK',
            ],
            [
                'first_name' => 'Alex',
                'last_name' => 'White',
                'email' => 'alex.white@example.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'is_active' => true,
                'location' => 'London, UK',
            ],
            [
                'first_name' => 'Sarah',
                'last_name' => 'Johnson',
                'email' => 'sarah.johnson@example.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'is_active' => true,
                'location' => 'London, UK',
            ],
            [
                'first_name' => 'David',
                'last_name' => 'Brown',
                'email' => 'david.brown@example.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'is_active' => true,
                'location' => 'London, UK',
            ],
            [
                'first_name' => 'Emma',
                'last_name' => 'Wilson',
                'email' => 'emma.wilson@example.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'is_active' => true,
                'location' => 'London, UK',
            ],
        ];

        foreach ($testUsers as $userData) {
            User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }

        $this->command->info('Test users created successfully!');
    }
}







