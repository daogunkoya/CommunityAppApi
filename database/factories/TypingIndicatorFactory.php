<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TypingIndicator>
 */
class TypingIndicatorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'context_id' => Conversation::factory(),
            'context_type' => Conversation::class,
            'started_at' => fake()->dateTimeBetween('-5 minutes', 'now'),
            'expires_at' => fake()->dateTimeBetween('now', '+5 minutes'),
        ];
    }

    /**
     * Create an active typing indicator.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'last_activity' => fake()->dateTimeBetween('-30 seconds', 'now'),
        ]);
    }

    /**
     * Create an inactive typing indicator.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'last_activity' => fake()->dateTimeBetween('-5 minutes', '-1 minute'),
        ]);
    }

    /**
     * Create a recently started typing indicator.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'started_at' => fake()->dateTimeBetween('-1 minute', 'now'),
            'last_activity' => fake()->dateTimeBetween('-30 seconds', 'now'),
        ]);
    }

    /**
     * Create a long-running typing indicator.
     */
    public function longRunning(): static
    {
        return $this->state(fn (array $attributes) => [
            'started_at' => fake()->dateTimeBetween('-10 minutes', '-5 minutes'),
            'last_activity' => fake()->dateTimeBetween('-2 minutes', 'now'),
        ]);
    }
}
