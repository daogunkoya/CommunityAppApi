<?php

namespace Database\Factories;

use App\Models\Tournament;
use App\Models\TournamentBracket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TournamentMatch>
 */
class TournamentMatchFactory extends Factory
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
            'bracket_id' => TournamentBracket::factory(),
            'player1_id' => User::factory(),
            'player2_id' => User::factory(),
            'winner_id' => null,
            'loser_id' => null,
            'round' => fake()->numberBetween(1, 8),
            'match_number' => fake()->numberBetween(1, 100),
            'scheduled_at' => fake()->dateTimeBetween('now', '+1 month'),
            'started_at' => null,
            'completed_at' => null,
            'status' => fake()->randomElement(['scheduled', 'in-progress', 'completed', 'cancelled']),
            'score' => null,
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    /**
     * Create a scheduled match.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
            'actual_start_time' => null,
            'actual_end_time' => null,
            'winner_id' => null,
            'loser_id' => null,
        ]);
    }

    /**
     * Create an in-progress match.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'actual_start_time' => fake()->dateTimeBetween('-2 hours', 'now'),
            'actual_end_time' => null,
            'winner_id' => null,
            'loser_id' => null,
        ]);
    }

    /**
     * Create a completed match.
     */
    public function completed(): static
    {
        $startTime = fake()->dateTimeBetween('-4 hours', '-2 hours');
        $endTime = fake()->dateTimeBetween($startTime, 'now');
        $winner = fake()->randomElement([1, 2]);

        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'actual_start_time' => $startTime,
            'actual_end_time' => $endTime,
            'score_player1' => $winner === 1 ? fake()->numberBetween(11, 21) : fake()->numberBetween(0, 19),
            'score_player2' => $winner === 2 ? fake()->numberBetween(11, 21) : fake()->numberBetween(0, 19),
            'winner_id' => $winner === 1 ? $attributes['player1_id'] : $attributes['player2_id'],
            'loser_id' => $winner === 1 ? $attributes['player2_id'] : $attributes['player1_id'],
        ]);
    }

    /**
     * Create a cancelled match.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'notes' => fake()->randomElement([
                'Player withdrew',
                'Weather conditions',
                'Venue unavailable',
                'Scheduling conflict',
            ]),
        ]);
    }

    /**
     * Create a postponed match.
     */
    public function postponed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'postponed',
            'scheduled_time' => fake()->dateTimeBetween('+1 day', '+1 week'),
            'notes' => 'Match postponed due to scheduling conflict',
        ]);
    }

    /**
     * Create a featured match.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
            'venue' => fake()->randomElement([
                'Main Arena',
                'Center Court',
                'Championship Field',
                'Elite Sports Complex',
            ]),
        ]);
    }

    /**
     * Create a match with scores.
     */
    public function withScores(): static
    {
        $score1 = fake()->numberBetween(0, 21);
        $score2 = fake()->numberBetween(0, 21);

        return $this->state(fn (array $attributes) => [
            'score_player1' => $score1,
            'score_player2' => $score2,
            'winner_id' => $score1 > $score2 ? $attributes['player1_id'] : $attributes['player2_id'],
            'loser_id' => $score1 > $score2 ? $attributes['player2_id'] : $attributes['player1_id'],
        ]);
    }

    /**
     * Create a match in early rounds.
     */
    public function earlyRound(): static
    {
        return $this->state(fn (array $attributes) => [
            'round' => fake()->numberBetween(1, 4),
        ]);
    }

    /**
     * Create a match in later rounds.
     */
    public function lateRound(): static
    {
        return $this->state(fn (array $attributes) => [
            'round' => fake()->numberBetween(5, 8),
        ]);
    }
}
