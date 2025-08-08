<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Community;
use Illuminate\Support\Facades\Hash;

class UsersWithLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'first_name' => 'Sarah',
                'last_name' => 'Wilson',
                'email' => 'sarah@example.com',
                'password' => Hash::make('password'),
                'latitude' => 51.5320,
                'longitude' => -0.1530,
                'community_name' => 'Camden',
                'city' => 'London',
                'state' => 'England',
                'country' => 'UK',
                'address' => 'Regent\'s Park, London',
                'email_verified_at' => now(),
            ],
            [
                'first_name' => 'Mike',
                'last_name' => 'Johnson',
                'email' => 'mike@example.com',
                'password' => Hash::make('password'),
                'latitude' => 51.5074,
                'longitude' => -0.1278,
                'community_name' => 'Westminster',
                'city' => 'London',
                'state' => 'England',
                'country' => 'UK',
                'address' => 'Trafalgar Square, London',
                'email_verified_at' => now(),
            ],
            [
                'first_name' => 'Emily',
                'last_name' => 'Brown',
                'email' => 'emily@example.com',
                'password' => Hash::make('password'),
                'latitude' => 51.5200,
                'longitude' => -0.1000,
                'community_name' => 'Islington',
                'city' => 'London',
                'state' => 'England',
                'country' => 'UK',
                'address' => 'Angel, London',
                'email_verified_at' => now(),
            ],
            [
                'first_name' => 'David',
                'last_name' => 'Taylor',
                'email' => 'david@example.com',
                'password' => Hash::make('password'),
                'latitude' => 51.5500,
                'longitude' => -0.1333,
                'community_name' => 'Barking and Dagenham',
                'city' => 'London',
                'state' => 'England',
                'country' => 'UK',
                'address' => 'Barking, London',
                'email_verified_at' => now(),
            ],
            [
                'first_name' => 'Lisa',
                'last_name' => 'Garcia',
                'email' => 'lisa@example.com',
                'password' => Hash::make('password'),
                'latitude' => 51.5455,
                'longitude' => -0.1622,
                'community_name' => 'Camden',
                'city' => 'London',
                'state' => 'England',
                'country' => 'UK',
                'address' => 'Camden Town, London',
                'email_verified_at' => now(),
            ],
            [
                'first_name' => 'Carlos',
                'last_name' => 'Martinez',
                'email' => 'carlos@example.com',
                'password' => Hash::make('password'),
                'latitude' => 51.5833,
                'longitude' => 0.2000,
                'community_name' => 'Havering',
                'city' => 'London',
                'state' => 'England',
                'country' => 'UK',
                'address' => 'Romford, London',
                'email_verified_at' => now(),
            ],
            [
                'first_name' => 'Anna',
                'last_name' => 'Anderson',
                'email' => 'anna@example.com',
                'password' => Hash::make('password'),
                'latitude' => 51.4808,
                'longitude' => -2.2426,
                'community_name' => 'Bristol',
                'city' => 'Bristol',
                'state' => 'England',
                'country' => 'UK',
                'address' => 'Bristol City Centre',
                'email_verified_at' => now(),
            ],
            [
                'first_name' => 'James',
                'last_name' => 'Thompson',
                'email' => 'james@example.com',
                'password' => Hash::make('password'),
                'latitude' => 53.4808,
                'longitude' => -2.2426,
                'community_name' => 'Manchester',
                'city' => 'Manchester',
                'state' => 'England',
                'country' => 'UK',
                'address' => 'Manchester City Centre',
                'email_verified_at' => now(),
            ],
            [
                'first_name' => 'Maria',
                'last_name' => 'Rodriguez',
                'email' => 'maria@example.com',
                'password' => Hash::make('password'),
                'latitude' => 52.4862,
                'longitude' => -1.8904,
                'community_name' => 'Birmingham',
                'city' => 'Birmingham',
                'state' => 'England',
                'country' => 'UK',
                'address' => 'Birmingham City Centre',
                'email_verified_at' => now(),
            ],
            [
                'first_name' => 'Tom',
                'last_name' => 'Williams',
                'email' => 'tom@example.com',
                'password' => Hash::make('password'),
                'latitude' => 51.4545,
                'longitude' => -2.5879,
                'community_name' => 'Bristol',
                'city' => 'Bristol',
                'state' => 'England',
                'country' => 'UK',
                'address' => 'Bristol Harbour',
                'email_verified_at' => now(),
            ],
        ];

        foreach ($users as $userData) {
            $user = User::create($userData);

            // Assign user to community
            $community = Community::where('name', $userData['community_name'])
                ->where('city', $userData['city'])
                ->first();

            if ($community) {
                $user->communities()->attach($community->id, [
                    'is_primary' => true,
                    'is_active' => true,
                    'joined_at' => now(),
                ]);
            }
        }

        $this->command->info('Users with location data seeded successfully!');
    }
}
