<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Game>
 */
class GameFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'location' => fake()->city(),
            'date' => fake()->dateTime(),
            'venue_booked' => fake()->boolean(),
            'notes' => fake()->paragraph(),
            'user_id' => User::factory(),

        ];
    }
}
