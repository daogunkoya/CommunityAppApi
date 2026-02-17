<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
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
            'content' => fake()->paragraph(1),
            'read_at' => fake()->optional(0.8)->dateTimeBetween('-1 day', 'now'),
        ];
    }

    /**
     * Create a text message.
     */
    public function text(): static
    {
        return $this->state(fn (array $attributes) => [
            'message_type' => 'text',
            'content' => fake()->paragraph(1),
        ]);
    }

    /**
     * Create an image message.
     */
    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'message_type' => 'image',
            'content' => fake()->imageUrl(640, 480, 'sports'),
        ]);
    }

    /**
     * Create a file message.
     */
    public function file(): static
    {
        return $this->state(fn (array $attributes) => [
            'message_type' => 'file',
            'content' => fake()->filePath(),
        ]);
    }

    /**
     * Create a system message.
     */
    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'message_type' => 'system',
            'content' => fake()->randomElement([
                'User joined the conversation',
                'User left the conversation',
                'Conversation created',
                'Tournament reminder',
            ]),
        ]);
    }

    /**
     * Create an unread message.
     */
    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    /**
     * Create a read message.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => true,
            'read_at' => fake()->dateTimeBetween('-1 day', 'now'),
        ]);
    }

    /**
     * Create an edited message.
     */
    public function edited(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_edited' => true,
            'edited_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Create a recent message.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => fake()->dateTimeBetween('-1 hour', 'now'),
        ]);
    }
}
