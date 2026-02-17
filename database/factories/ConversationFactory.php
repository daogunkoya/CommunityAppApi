<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Conversation>
 */
class ConversationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->optional(0.7)->sentence(3),
            'type' => fake()->randomElement(['direct', 'group']),
            'context_id' => null,
            'last_message_id' => null,
        ];
    }

    /**
     * Create a direct conversation.
     */
    public function direct(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'direct',
            'title' => null,
        ]);
    }

    /**
     * Create a group conversation.
     */
    public function group(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'group',
            'name' => fake()->words(3, true),
        ]);
    }

    /**
     * Create an inactive conversation.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'last_message_at' => fake()->dateTimeBetween('-3 months', '-1 month'),
        ]);
    }

    /**
     * Create a recently active conversation.
     */
    public function recentlyActive(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_message_at' => fake()->dateTimeBetween('-1 day', 'now'),
        ]);
    }
}
