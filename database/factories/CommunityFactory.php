<?php

namespace Database\Factories;

use App\Models\GameType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Community>
 */
class CommunityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true) . ' Community',
            'type' => fake()->randomElement(['sports', 'fitness', 'recreation', 'competitive']),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'country' => fake()->country(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'description' => fake()->paragraph(3),
            'image_url' => fake()->optional(0.7)->imageUrl(640, 480, 'sports'),
            'is_active' => true,
        ];
    }

    /**
     * Create a public community.
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => true,
        ]);
    }

    /**
     * Create a private community.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => false,
        ]);
    }

    /**
     * Create a large community.
     */
    public function large(): static
    {
        return $this->state(fn (array $attributes) => [
            'member_count' => fake()->numberBetween(200, 500),
            'max_members' => fake()->numberBetween(500, 1000),
        ]);
    }

    /**
     * Create a small community.
     */
    public function small(): static
    {
        return $this->state(fn (array $attributes) => [
            'member_count' => fake()->numberBetween(5, 50),
            'max_members' => fake()->numberBetween(50, 100),
        ]);
    }

    /**
     * Create a featured community.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
            'member_count' => fake()->numberBetween(100, 500),
        ]);
    }

    /**
     * Create a verified community.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
            'verified_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'verified_by' => fake()->numberBetween(1, 10),
        ]);
    }

    /**
     * Create an unverified community.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => false,
            'verified_at' => null,
            'verified_by' => null,
        ]);
    }

    /**
     * Create an inactive community.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a community with full contact information.
     */
    public function withContactInfo(): static
    {
        return $this->state(fn (array $attributes) => [
            'contact_email' => fake()->email(),
            'website' => fake()->url(),
            'social_media' => fake()->url(),
        ]);
    }

    /**
     * Create a community with meeting schedule.
     */
    public function withSchedule(): static
    {
        return $this->state(fn (array $attributes) => [
            'meeting_schedule' => fake()->randomElement([
                'Every Saturday at 2 PM',
                'Tuesdays and Thursdays at 6 PM',
                'Sunday mornings at 9 AM',
                'Weekday evenings',
                'Monthly meetups',
            ]),
        ]);
    }
}
