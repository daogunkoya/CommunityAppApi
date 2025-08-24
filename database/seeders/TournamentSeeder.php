<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tournament;
use App\Models\GameType;
use App\Models\User;
use Carbon\Carbon;

class TournamentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get game types
        $football = GameType::where('name', 'Football')->first();
        $tennis = GameType::where('name', 'Tennis')->first();
        $basketball = GameType::where('name', 'Basketball')->first();
        $swimming = GameType::where('name', 'Swimming')->first();
        $cycling = GameType::where('name', 'Cycling')->first();

        // Get a user to be the organiser
        $organiser = User::first();

        if (!$organiser) {
            $this->command->error('No users found. Please run UserSeeder first.');
            return;
        }

        $tournaments = [
            [
                'name' => 'Summer Tennis Championship',
                'description' => 'Join the biggest tennis event of the season! Compete against the best players in the region.',
                'game_type_id' => $tennis->id,
                'organiser_id' => $organiser->id,
                'location' => 'Central Sports Complex',
                'address' => '123 Sports Avenue',
                'city' => 'London',
                'state' => 'England',
                'postal_code' => 'SW1A 1AA',
                'country' => 'UK',
                'latitude' => 51.5074,
                'longitude' => -0.1278,
                'starts_at' => Carbon::now()->addDays(15),
                'ends_at' => Carbon::now()->addDays(17),
                'registration_deadline' => Carbon::now()->addDays(10),
                'max_participants' => 64,
                'min_participants' => 8,
                'entry_fee' => 25.00,
                'prize_pool' => 500.00,
                'prize_description' => 'Cash prizes for top 3 players',
                'skill_level' => 2,
                'status' => 'open',
                'is_featured' => true,
                'rules' => 'Standard tennis rules apply. Best of 3 sets.',
                'format' => 'single-elimination',
                'bracket_type' => 'seeded',
                'registration_enabled' => true,
                'waiting_list_enabled' => true,
            ],
            [
                'name' => 'Basketball 3v3 Street Tournament',
                'description' => 'Fast-paced 3v3 basketball tournament on outdoor courts. Bring your A-game!',
                'game_type_id' => $basketball->id,
                'organiser_id' => $organiser->id,
                'location' => 'Downtown Courts',
                'address' => '456 Street Court',
                'city' => 'Manchester',
                'state' => 'England',
                'postal_code' => 'M1 1AA',
                'country' => 'UK',
                'latitude' => 53.4808,
                'longitude' => -2.2426,
                'starts_at' => Carbon::now()->addDays(22),
                'ends_at' => Carbon::now()->addDays(22),
                'registration_deadline' => Carbon::now()->addDays(18),
                'max_participants' => 32,
                'min_participants' => 8,
                'entry_fee' => 15.00,
                'prize_pool' => 0.00,
                'prize_description' => 'Trophies for winners',
                'skill_level' => 1,
                'status' => 'filling-fast',
                'is_featured' => false,
                'rules' => '3v3 format, 21 points or 15 minutes. First to 21 wins.',
                'format' => 'double-elimination',
                'bracket_type' => 'random',
                'registration_enabled' => true,
                'waiting_list_enabled' => false,
            ],
            [
                'name' => 'Cycling Hill Challenge',
                'description' => 'Challenging hill climb competition for serious cyclists. Test your endurance!',
                'game_type_id' => $cycling->id,
                'organiser_id' => $organiser->id,
                'location' => 'Mountain Trails',
                'address' => '789 Hill Road',
                'city' => 'Edinburgh',
                'state' => 'Scotland',
                'postal_code' => 'EH1 1AA',
                'country' => 'UK',
                'latitude' => 55.9533,
                'longitude' => -3.1883,
                'starts_at' => Carbon::now()->addDays(35),
                'ends_at' => Carbon::now()->addDays(35),
                'registration_deadline' => Carbon::now()->addDays(30),
                'max_participants' => 50,
                'min_participants' => 10,
                'entry_fee' => 40.00,
                'prize_pool' => 1200.00,
                'prize_description' => 'Cash prizes and cycling gear',
                'skill_level' => 3,
                'status' => 'almost-full',
                'is_featured' => false,
                'rules' => 'Individual time trial. Safety equipment required.',
                'format' => 'single-elimination',
                'bracket_type' => 'standard',
                'registration_enabled' => true,
                'waiting_list_enabled' => true,
            ],
            [
                'name' => 'Swimming Masters Meet',
                'description' => 'Competitive swimming meet for experienced swimmers. Multiple events available.',
                'game_type_id' => $swimming->id,
                'organiser_id' => $organiser->id,
                'location' => 'Olympic Pool Complex',
                'address' => '321 Water Lane',
                'city' => 'Birmingham',
                'state' => 'England',
                'postal_code' => 'B1 1AA',
                'country' => 'UK',
                'latitude' => 52.4862,
                'longitude' => -1.8904,
                'starts_at' => Carbon::now()->addDays(45),
                'ends_at' => Carbon::now()->addDays(47),
                'registration_deadline' => Carbon::now()->addDays(40),
                'max_participants' => 80,
                'min_participants' => 20,
                'entry_fee' => 30.00,
                'prize_pool' => 800.00,
                'prize_description' => 'Cash prizes and swimming equipment',
                'skill_level' => 4,
                'status' => 'open',
                'is_featured' => false,
                'rules' => 'FINA rules apply. Multiple stroke events.',
                'format' => 'round-robin',
                'bracket_type' => 'seeded',
                'registration_enabled' => true,
                'waiting_list_enabled' => true,
            ],
            [
                'name' => 'Football Community Cup',
                'description' => 'Friendly football tournament for the local community. All skill levels welcome!',
                'game_type_id' => $football->id,
                'organiser_id' => $organiser->id,
                'location' => 'Community Sports Ground',
                'address' => '654 Community Street',
                'city' => 'Liverpool',
                'state' => 'England',
                'postal_code' => 'L1 1AA',
                'country' => 'UK',
                'latitude' => 53.4084,
                'longitude' => -2.9916,
                'starts_at' => Carbon::now()->addDays(60),
                'ends_at' => Carbon::now()->addDays(62),
                'registration_deadline' => Carbon::now()->addDays(55),
                'max_participants' => 100,
                'min_participants' => 16,
                'entry_fee' => 10.00,
                'prize_pool' => 200.00,
                'prize_description' => 'Trophies and team kits',
                'skill_level' => 1,
                'status' => 'open',
                'is_featured' => false,
                'rules' => '11-a-side, standard football rules. Teams of 11-15 players.',
                'format' => 'single-elimination',
                'bracket_type' => 'random',
                'registration_enabled' => true,
                'waiting_list_enabled' => false,
            ],
        ];

        foreach ($tournaments as $tournamentData) {
            Tournament::create($tournamentData);
        }

        $this->command->info('Tournaments seeded successfully!');
    }
}
