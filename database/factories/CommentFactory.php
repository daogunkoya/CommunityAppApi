<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Discussion;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comment>
 */
class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $comments = [
            'I\'d love to join! What time are you thinking?',
            'Great idea! I\'m definitely interested in this.',
            'I know a good place for this. Let me check the details.',
            'Count me in! I\'ve been looking for something like this.',
            'What skill level are you looking for? I\'m intermediate.',
            'I can help organize this if needed.',
            'Perfect timing! I\'ve been wanting to get back into this.',
            'Do you have equipment or should I bring my own?',
            'I\'m a beginner, is that okay?',
            'This sounds like fun! Where exactly are you planning to meet?',
            'I have some experience with this. Happy to help beginners!',
            'What\'s the cost involved? Just want to plan ahead.',
            'I\'m available most weekends. Flexible on timing.',
            'Great initiative! The community needs more events like this.',
            'I can bring some friends along if that helps.',
            'What\'s the age range you\'re targeting?',
            'I have some equipment we can share.',
            'This is exactly what I\'ve been looking for!',
            'Do you need any help with logistics?',
            'I\'m in! Let me know the final details.',
            'What\'s the expected duration?',
            'I can help with transportation if needed.',
            'This sounds perfect for my skill level.',
            'I\'m excited about this! When do we start?',
            'Do you have a backup plan for bad weather?',
            'I can help spread the word to other interested people.',
            'What should I bring with me?',
            'I\'m available both weekdays and weekends.',
            'This is a great way to meet new people!',
            'I have some tips to share if anyone\'s interested.'
        ];

        return [
            'body' => fake()->randomElement($comments),
            'user_id' => User::factory(),
            'discussion_id' => Discussion::factory(),
            'created_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'updated_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Create a helpful comment.
     */
    public function helpful(): static
    {
        $helpfulComments = [
            'I can help organize this if needed.',
            'I have some equipment we can share.',
            'I can help with transportation if needed.',
            'I can help spread the word to other interested people.',
            'I have some tips to share if anyone\'s interested.',
            'I know a good place for this. Let me check the details.',
            'I have some experience with this. Happy to help beginners!'
        ];

        return $this->state(fn (array $attributes) => [
            'body' => fake()->randomElement($helpfulComments),
        ]);
    }

    /**
     * Create an enthusiastic comment.
     */
    public function enthusiastic(): static
    {
        $enthusiasticComments = [
            'I\'d love to join! What time are you thinking?',
            'Great idea! I\'m definitely interested in this.',
            'Count me in! I\'ve been looking for something like this.',
            'Perfect timing! I\'ve been wanting to get back into this.',
            'This sounds like fun! Where exactly are you planning to meet?',
            'Great initiative! The community needs more events like this.',
            'This is exactly what I\'ve been looking for!',
            'I\'m excited about this! When do we start?',
            'This is a great way to meet new people!'
        ];

        return $this->state(fn (array $attributes) => [
            'body' => fake()->randomElement($enthusiasticComments),
        ]);
    }

    /**
     * Create a recent comment.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'updated_at' => fake()->dateTimeBetween('-7 days', 'now'),
        ]);
    }
}
