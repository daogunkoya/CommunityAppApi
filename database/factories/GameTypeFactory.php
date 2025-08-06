<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GameType>
 */
class GameTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sports = [
            'Football' => 'The world\'s most popular sport, played with 11 players per team on a rectangular field.',
            'Basketball' => 'A fast-paced team sport played on a court with two hoops, requiring skill and teamwork.',
            'Tennis' => 'A racket sport played individually or in doubles, requiring agility and strategy.',
            'Swimming' => 'A water-based sport that builds endurance and works all major muscle groups.',
            'Cycling' => 'A cardiovascular sport that can be done on roads, trails, or in velodromes.',
            'Running' => 'A fundamental sport that improves cardiovascular health and can be done anywhere.',
            'Volleyball' => 'A team sport played on a court with a net, requiring coordination and teamwork.',
            'Baseball' => 'America\'s pastime, a bat-and-ball game played between two teams of nine players.',
            'Soccer' => 'A team sport played with feet, emphasizing ball control and strategic play.',
            'Golf' => 'A precision sport played on a course, requiring focus and technique.',
            'Badminton' => 'A racket sport played with a shuttlecock, requiring quick reflexes and agility.',
            'Squash' => 'A fast-paced racket sport played in an enclosed court, requiring speed and strategy.',
            'Table Tennis' => 'A fast-paced indoor sport played on a table with small rackets and a lightweight ball.',
            'Cricket' => 'A bat-and-ball game popular in many countries, requiring strategy and skill.',
            'Rugby' => 'A physical team sport with oval ball, emphasizing strength and teamwork.',
        ];

        $sport = fake()->unique()->randomElement(array_keys($sports));

        return [
            'name' => $sport,
            'description' => $sports[$sport],
            'icon_path' => '/icons/' . strtolower(str_replace(' ', '-', $sport)) . '.svg',
        ];
    }

    /**
     * Create a popular sport type.
     */
    public function popular(): static
    {
        $popularSports = ['Football', 'Basketball', 'Tennis', 'Swimming', 'Running'];

        return $this->state(fn (array $attributes) => [
            'name' => fake()->unique()->randomElement($popularSports),
        ]);
    }

    /**
     * Create an indoor sport type.
     */
    public function indoor(): static
    {
        $indoorSports = ['Basketball', 'Volleyball', 'Badminton', 'Squash', 'Table Tennis'];

        return $this->state(fn (array $attributes) => [
            'name' => fake()->unique()->randomElement($indoorSports),
        ]);
    }

    /**
     * Create an outdoor sport type.
     */
    public function outdoor(): static
    {
        $outdoorSports = ['Football', 'Tennis', 'Running', 'Cycling', 'Golf', 'Cricket'];

        return $this->state(fn (array $attributes) => [
            'name' => fake()->unique()->randomElement($outdoorSports),
        ]);
    }
}
