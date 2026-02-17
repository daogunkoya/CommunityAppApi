<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserPreferredFacility>
 */
class UserPreferredFacilityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $facilityTypes = [
            'Central Park Sports Complex',
            'Downtown Recreation Center',
            'Riverside Athletic Club',
            'Community Sports Hub',
            'Elite Training Center',
            'Neighborhood Gym',
            'University Sports Center',
            'Professional Sports Arena',
            'Outdoor Sports Park',
            'Indoor Sports Facility',
        ];

        $facility = fake()->randomElement($facilityTypes);
        $city = fake()->city();
        $state = fake()->state();

        return [
            'user_id' => User::factory(),
            'facility_id' => 'facility_' . strtolower(str_replace(' ', '_', $facility)),
            'facility_name' => $facility,
            'facility_address' => fake()->streetAddress() . ', ' . $city . ', ' . $state,
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'membership_type' => fake()->randomElement(['member', 'pay-per-use', 'other']),
        ];
    }

    /**
     * Create a gym facility preference.
     */
    public function gym(): static
    {
        return $this->state(fn (array $attributes) => [
            'facility_name' => fake()->randomElement([
                'Elite Fitness Center',
                'Powerhouse Gym',
                'FitLife Club',
                'StrongBody Gym',
            ]),
            'membership_type' => 'member',
        ]);
    }

    /**
     * Create a sports complex preference.
     */
    public function sportsComplex(): static
    {
        return $this->state(fn (array $attributes) => [
            'facility_name' => fake()->randomElement([
                'Central Sports Complex',
                'Riverside Athletic Center',
                'Community Sports Hub',
                'Elite Sports Arena',
            ]),
            'membership_type' => fake()->randomElement(['member', 'pay-per-use']),
        ]);
    }

    /**
     * Create an outdoor facility preference.
     */
    public function outdoor(): static
    {
        return $this->state(fn (array $attributes) => [
            'facility_name' => fake()->randomElement([
                'Central Park Sports Fields',
                'Riverside Sports Park',
                'Community Recreation Area',
                'Outdoor Sports Complex',
            ]),
            'membership_type' => 'pay-per-use',
        ]);
    }

    /**
     * Create a high preference level.
     */
    public function highPreference(): static
    {
        return $this->state(fn (array $attributes) => [
            'preference_level' => fake()->numberBetween(4, 5),
        ]);
    }

    /**
     * Create a low preference level.
     */
    public function lowPreference(): static
    {
        return $this->state(fn (array $attributes) => [
            'preference_level' => fake()->numberBetween(1, 2),
        ]);
    }

    /**
     * Create an inactive facility preference.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a member facility preference.
     */
    public function member(): static
    {
        return $this->state(fn (array $attributes) => [
            'membership_type' => 'member',
        ]);
    }

    /**
     * Create a pay-per-use facility preference.
     */
    public function payPerUse(): static
    {
        return $this->state(fn (array $attributes) => [
            'membership_type' => 'pay-per-use',
        ]);
    }
}
