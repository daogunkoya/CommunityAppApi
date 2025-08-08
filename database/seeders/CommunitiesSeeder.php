<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Community;

class CommunitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $communities = [
            // London Boroughs
            ['name' => 'Camden', 'type' => 'borough', 'city' => 'London', 'state' => 'England', 'country' => 'UK', 'latitude' => 51.5455, 'longitude' => -0.1622],
            ['name' => 'Islington', 'type' => 'borough', 'city' => 'London', 'state' => 'England', 'country' => 'UK', 'latitude' => 51.5362, 'longitude' => -0.1033],
            ['name' => 'Hackney', 'type' => 'borough', 'city' => 'London', 'state' => 'England', 'country' => 'UK', 'latitude' => 51.5455, 'longitude' => -0.0557],
            ['name' => 'Tower Hamlets', 'type' => 'borough', 'city' => 'London', 'state' => 'England', 'country' => 'UK', 'latitude' => 51.5200, 'longitude' => -0.0297],
            ['name' => 'Newham', 'type' => 'borough', 'city' => 'London', 'state' => 'England', 'country' => 'UK', 'latitude' => 51.5255, 'longitude' => 0.0352],
            ['name' => 'Greenwich', 'type' => 'borough', 'city' => 'London', 'state' => 'England', 'country' => 'UK', 'latitude' => 51.4800, 'longitude' => 0.0000],
            ['name' => 'Lewisham', 'type' => 'borough', 'city' => 'London', 'state' => 'England', 'country' => 'UK', 'latitude' => 51.4500, 'longitude' => -0.0167],
            ['name' => 'Southwark', 'type' => 'borough', 'city' => 'London', 'state' => 'England', 'country' => 'UK', 'latitude' => 51.5000, 'longitude' => -0.0833],
            ['name' => 'Lambeth', 'type' => 'borough', 'city' => 'London', 'state' => 'England', 'country' => 'UK', 'latitude' => 51.4833, 'longitude' => -0.1167],
            ['name' => 'Wandsworth', 'type' => 'borough', 'city' => 'London', 'state' => 'England', 'country' => 'UK', 'latitude' => 51.4500, 'longitude' => -0.2000],
            ['name' => 'Hammersmith and Fulham', 'type' => 'borough', 'city' => 'London', 'state' => 'England', 'country' => 'UK', 'latitude' => 51.5000, 'longitude' => -0.2333],
            ['name' => 'Kensington and Chelsea', 'type' => 'borough', 'city' => 'London', 'state' => 'England', 'country' => 'UK', 'latitude' => 51.5000, 'longitude' => -0.1833],
            ['name' => 'Westminster', 'type' => 'borough', 'city' => 'London', 'state' => 'England', 'country' => 'UK', 'latitude' => 51.5000, 'longitude' => -0.1167],
            ['name' => 'Brent', 'type' => 'borough', 'city' => 'London', 'state' => 'England', 'country' => 'UK', 'latitude' => 51.5500, 'longitude' => -0.3000],
            ['name' => 'Ealing', 'type' => 'borough', 'city' => 'London', 'state' => 'England', 'country' => 'UK', 'latitude' => 51.5167, 'longitude' => -0.3167],
            ['name' => 'Hounslow', 'type' => 'borough', 'city' => 'London', 'state' => 'England', 'country' => 'UK', 'latitude' => 51.4667, 'longitude' => -0.3667],
            ['name' => 'Richmond upon Thames', 'type' => 'borough', 'city' => 'London', 'state' => 'England', 'country' => 'UK', 'latitude' => 51.4500, 'longitude' => -0.3000],
            ['name' => 'Kingston upon Thames', 'type' => 'borough', 'city' => 'London', 'state' => 'England', 'country' => 'UK', 'latitude' => 51.4167, 'longitude' => -0.3000],
            ['name' => 'Merton', 'type' => 'borough', 'city' => 'London', 'state' => 'England', 'country' => 'UK', 'latitude' => 51.4000, 'longitude' => -0.2000],
            ['name' => 'Sutton', 'type' => 'borough', 'city' => 'London', 'state' => 'England', 'country' => 'UK', 'latitude' => 51.3667, 'longitude' => -0.2000],
            ['name' => 'Croydon', 'type' => 'borough', 'city' => 'London', 'state' => 'England', 'country' => 'UK', 'latitude' => 51.3667, 'longitude' => -0.1000],
            ['name' => 'Bromley', 'type' => 'borough', 'city' => 'London', 'state' => 'England', 'country' => 'UK', 'latitude' => 51.4000, 'longitude' => 0.0167],
            ['name' => 'Bexley', 'type' => 'borough', 'city' => 'London', 'state' => 'England', 'country' => 'UK', 'latitude' => 51.4500, 'longitude' => 0.1500],
            ['name' => 'Harrow', 'type' => 'borough', 'city' => 'London', 'state' => 'England', 'country' => 'UK', 'latitude' => 51.5833, 'longitude' => -0.3333],
            ['name' => 'Hillingdon', 'type' => 'borough', 'city' => 'London', 'state' => 'England', 'country' => 'UK', 'latitude' => 51.5500, 'longitude' => -0.4667],
            ['name' => 'Barnet', 'type' => 'borough', 'city' => 'London', 'state' => 'England', 'country' => 'UK', 'latitude' => 51.6167, 'longitude' => -0.2000],
            ['name' => 'Enfield', 'type' => 'borough', 'city' => 'London', 'state' => 'England', 'country' => 'UK', 'latitude' => 51.6500, 'longitude' => -0.0833],
            ['name' => 'Waltham Forest', 'type' => 'borough', 'city' => 'London', 'state' => 'England', 'country' => 'UK', 'latitude' => 51.5833, 'longitude' => 0.0000],
            ['name' => 'Redbridge', 'type' => 'borough', 'city' => 'London', 'state' => 'England', 'country' => 'UK', 'latitude' => 51.5667, 'longitude' => 0.0833],
            ['name' => 'Havering', 'type' => 'borough', 'city' => 'London', 'state' => 'England', 'country' => 'UK', 'latitude' => 51.5833, 'longitude' => 0.2000],
            ['name' => 'Barking and Dagenham', 'type' => 'borough', 'city' => 'London', 'state' => 'England', 'country' => 'UK', 'latitude' => 51.5500, 'longitude' => 0.1333],

            // Other UK Cities
            ['name' => 'Manchester', 'type' => 'city', 'city' => 'Manchester', 'state' => 'England', 'country' => 'UK', 'latitude' => 53.4808, 'longitude' => -2.2426],
            ['name' => 'Birmingham', 'type' => 'city', 'city' => 'Birmingham', 'state' => 'England', 'country' => 'UK', 'latitude' => 52.4862, 'longitude' => -1.8904],
            ['name' => 'Liverpool', 'type' => 'city', 'city' => 'Liverpool', 'state' => 'England', 'country' => 'UK', 'latitude' => 53.4084, 'longitude' => -2.9916],
            ['name' => 'Leeds', 'type' => 'city', 'city' => 'Leeds', 'state' => 'England', 'country' => 'UK', 'latitude' => 53.8008, 'longitude' => -1.5491],
            ['name' => 'Sheffield', 'type' => 'city', 'city' => 'Sheffield', 'state' => 'England', 'country' => 'UK', 'latitude' => 53.3811, 'longitude' => -1.4701],
            ['name' => 'Bristol', 'type' => 'city', 'city' => 'Bristol', 'state' => 'England', 'country' => 'UK', 'latitude' => 51.4545, 'longitude' => -2.5879],
            ['name' => 'Glasgow', 'type' => 'city', 'city' => 'Glasgow', 'state' => 'Scotland', 'country' => 'UK', 'latitude' => 55.8642, 'longitude' => -4.2518],
            ['name' => 'Edinburgh', 'type' => 'city', 'city' => 'Edinburgh', 'state' => 'Scotland', 'country' => 'UK', 'latitude' => 55.9533, 'longitude' => -3.1883],
            ['name' => 'Cardiff', 'type' => 'city', 'city' => 'Cardiff', 'state' => 'Wales', 'country' => 'UK', 'latitude' => 51.4816, 'longitude' => -3.1791],
            ['name' => 'Belfast', 'type' => 'city', 'city' => 'Belfast', 'state' => 'Northern Ireland', 'country' => 'UK', 'latitude' => 54.5973, 'longitude' => -5.9301],
        ];

        foreach ($communities as $communityData) {
            Community::firstOrCreate(
                [
                    'name' => $communityData['name'],
                    'city' => $communityData['city'],
                    'state' => $communityData['state'],
                    'country' => $communityData['country'],
                ],
                [
                    'type' => $communityData['type'],
                    'latitude' => $communityData['latitude'],
                    'longitude' => $communityData['longitude'],
                    'description' => "Community in {$communityData['city']}, {$communityData['state']}",
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Communities seeded successfully!');
    }
}
