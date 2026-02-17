<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ConversationParticipant>
 */
class ConversationParticipantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'user_id' => User::factory(),
        ];
    }

    /**
     * Create an admin participant.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }

    /**
     * Create a moderator participant.
     */
    public function moderator(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'moderator',
        ]);
    }

    /**
     * Create a regular participant.
     */
    public function participant(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'participant',
        ]);
    }

    /**
     * Create an inactive participant (left the conversation).
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'left_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Create a muted participant.
     */
    public function muted(): static
    {
        return $this->state(fn (array $attributes) => [
            'muted_until' => fake()->dateTimeBetween('now', '+1 week'),
        ]);
    }

    /**
     * Create a recently joined participant.
     */
    public function recentlyJoined(): static
    {
        return $this->state(fn (array $attributes) => [
            'joined_at' => fake()->dateTimeBetween('-1 day', 'now'),
        ]);
    }

    /**
     * Create a participant who hasn't read recent messages.
     */
    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_read_at' => fake()->dateTimeBetween('-1 week', '-1 day'),
        ]);
    }
}
