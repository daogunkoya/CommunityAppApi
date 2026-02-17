<?php

namespace Database\Factories;

use App\Models\GameType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tournament>
 */
class TournamentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('now', '+3 months');
        $endsAt = fake()->dateTimeBetween($startsAt, '+4 months');
        $registrationDeadline = fake()->dateTimeBetween('now', $startsAt);

        return [
            'name' => fake()->words(3, true) . ' Tournament',
            'description' => fake()->paragraph(3),
            'game_type_id' => GameType::factory(),
            'organiser_id' => User::factory(),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'registration_deadline' => $registrationDeadline,
            'location' => fake()->city() . ', ' . fake()->state(),
            'max_participants' => fake()->randomElement([8, 16, 32, 64]),
            'entry_fee' => fake()->randomElement([0, 10, 25, 50, 100]),
            'prize_pool' => fake()->numberBetween(100, 5000),
            'skill_level' => fake()->randomElement([1, 2, 3, 4]),
            'status' => fake()->randomElement(['draft', 'open', 'filling-fast', 'almost-full', 'registration-closed', 'in-progress', 'completed', 'cancelled']),
            'format' => fake()->randomElement(['single-elimination', 'double-elimination', 'round-robin', 'swiss']),
            'rules' => fake()->paragraphs(2, true),
            'is_featured' => fake()->boolean(20),
        ];
    }

    /**
     * Create a tournament that's currently open for registration.
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'open',
            'registration_deadline' => fake()->dateTimeBetween('+1 day', '+1 month'),
        ]);
    }

    /**
     * Create a tournament that's in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'starts_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'ends_at' => fake()->dateTimeBetween('now', '+2 weeks'),
        ]);
    }

    /**
     * Create a completed tournament.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'starts_at' => fake()->dateTimeBetween('-2 months', '-1 month'),
            'ends_at' => fake()->dateTimeBetween('-1 month', '-1 week'),
        ]);
    }

    /**
     * Create a featured tournament.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
            'prize_pool' => fake()->numberBetween(500, 10000),
        ]);
    }

    /**
     * Create a free tournament.
     */
    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'entry_fee' => 0,
        ]);
    }

    /**
     * Create a tournament with participants.
     */
    public function withParticipants(int $count = 8): static
    {
        return $this->state(fn (array $attributes) => [
            'current_participants' => $count,
            'max_participants' => max($count, $attributes['max_participants'] ?? 16),
        ]);
    }
}
