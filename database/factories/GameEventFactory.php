<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\GameType;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GameEvent>
 */
class GameEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $skillLevels = [
            1 => 'Beginner',
            2 => 'Intermediate',
            3 => 'Advanced',
            4 => 'Expert'
        ];

        $venues = [
            'Central Park Sports Complex',
            'Downtown Recreation Center',
            'Riverside Athletic Club',
            'Community Sports Field',
            'Indoor Sports Arena',
            'University Sports Center',
            'Local Tennis Club',
            'Swimming Pool Complex',
            'Basketball Court - City Park',
            'Soccer Field - Memorial Stadium'
        ];

        $startDate = fake()->dateTimeBetween('now', '+2 months');

        return [
            'game_type_id' => GameType::factory(),
            'organiser_id' => User::factory(),
            'skill_level' => fake()->randomElement([1, 2, 3]),
            'location' => fake()->randomElement($venues),
            'starts_at' => $startDate,
            'venue_booked' => fake()->boolean(80), // 80% chance of being booked
            'max_participants' => fake()->randomElement([4, 6, 8, 10, 12, 16, 20]),
            'waiting_list_enabled' => fake()->boolean(70), // 70% chance of having waiting list
            'notes' => fake()->paragraph(2),
        ];
    }

    /**
     * Create an upcoming event.
     */
    public function upcoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => fake()->dateTimeBetween('now', '+1 month'),
            'status' => 'upcoming',
        ]);
    }

    /**
     * Create a beginner-friendly event.
     */
    public function beginner(): static
    {
        return $this->state(fn (array $attributes) => [
            'skill_level' => 1,
            'notes' => fake()->paragraph(2) . ' Perfect for beginners! All skill levels welcome.',
        ]);
    }

    /**
     * Create an advanced event.
     */
    public function advanced(): static
    {
        return $this->state(fn (array $attributes) => [
            'skill_level' => fake()->randomElement([2, 3]),
            'notes' => fake()->paragraph(2) . ' Advanced players only. Competitive play expected.',
        ]);
    }

    /**
     * Create a large group event.
     */
    public function largeGroup(): static
    {
        return $this->state(fn (array $attributes) => [
            'max_participants' => fake()->randomElement([16, 20, 24, 30]),
            'notes' => fake()->paragraph(2) . ' Large group event - great for socializing!',
        ]);
    }

    /**
     * Create an indoor event.
     */
    public function indoor(): static
    {
        $indoorVenues = [
            'Downtown Recreation Center',
            'Indoor Sports Arena',
            'University Sports Center',
            'Local Tennis Club',
            'Swimming Pool Complex'
        ];

        return $this->state(fn (array $attributes) => [
            'location' => fake()->randomElement($indoorVenues),
            'notes' => fake()->paragraph(2) . ' Indoor event - weather won\'t affect us!',
        ]);
    }
}
