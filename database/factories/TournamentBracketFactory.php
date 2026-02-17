<?php

namespace Database\Factories;

use App\Models\Tournament;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TournamentBracket>
 */
class TournamentBracketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tournament_id' => Tournament::factory(),
            'name' => fake()->randomElement(['Main Bracket', 'Losers Bracket', 'Quarter Finals', 'Semi Finals', 'Finals']),
            'type' => fake()->randomElement(['main', 'losers', 'winners']),
            'round' => fake()->numberBetween(1, 8),
            'max_players' => fake()->randomElement([8, 16, 32, 64]),
            'status' => fake()->randomElement(['pending', 'active', 'completed']),
        ];
    }

    /**
     * Create a main bracket.
     */
    public function main(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Main Bracket',
            'round' => 1,
        ]);
    }

    /**
     * Create a losers bracket.
     */
    public function losers(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Losers Bracket',
            'round' => 2,
        ]);
    }

    /**
     * Create a finals bracket.
     */
    public function finals(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Finals',
            'round' => fake()->numberBetween(6, 8),
        ]);
    }

    /**
     * Create an active bracket.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'is_active' => true,
        ]);
    }

    /**
     * Create a completed bracket.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'is_active' => false,
            'end_date' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Create a pending bracket.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'is_active' => false,
        ]);
    }

    /**
     * Create a bracket with participants.
     */
    public function withParticipants(int $count = 8): static
    {
        return $this->state(fn (array $attributes) => [
            'current_participants' => $count,
            'max_participants' => max($count, $attributes['max_participants'] ?? 16),
        ]);
    }
}
