<?php

namespace Database\Factories;

use App\Models\GameType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserSkillLevel>
 */
class UserSkillLevelFactory extends Factory
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
            'game_type_id' => GameType::factory(),
            'skill_level' => fake()->randomElement(['beginner', 'intermediate', 'advanced', 'expert']),
        ];
    }

    /**
     * Create a beginner skill level.
     */
    public function beginner(): static
    {
        return $this->state(fn (array $attributes) => [
            'skill_level' => 'beginner',
            'years_experience' => fake()->numberBetween(0, 2),
        ]);
    }

    /**
     * Create an intermediate skill level.
     */
    public function intermediate(): static
    {
        return $this->state(fn (array $attributes) => [
            'skill_level' => 'intermediate',
            'years_experience' => fake()->numberBetween(2, 5),
        ]);
    }

    /**
     * Create an advanced skill level.
     */
    public function advanced(): static
    {
        return $this->state(fn (array $attributes) => [
            'skill_level' => 'advanced',
            'years_experience' => fake()->numberBetween(5, 10),
        ]);
    }

    /**
     * Create an expert skill level.
     */
    public function expert(): static
    {
        return $this->state(fn (array $attributes) => [
            'skill_level' => 'expert',
            'years_experience' => fake()->numberBetween(10, 20),
        ]);
    }

    /**
     * Create a verified skill level.
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
     * Create an unverified skill level.
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
     * Create a skill level with high experience.
     */
    public function experienced(): static
    {
        return $this->state(fn (array $attributes) => [
            'years_experience' => fake()->numberBetween(8, 20),
            'skill_level' => fake()->randomElement(['advanced', 'expert']),
        ]);
    }

    /**
     * Create a skill level with low experience.
     */
    public function inexperienced(): static
    {
        return $this->state(fn (array $attributes) => [
            'years_experience' => fake()->numberBetween(0, 3),
            'skill_level' => fake()->randomElement(['beginner', 'intermediate']),
        ]);
    }
}
