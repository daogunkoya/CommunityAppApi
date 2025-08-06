<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Discussion>
 */
class DiscussionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $topics = [
            'Best tennis courts in the city?',
            'Looking for basketball players this weekend',
            'Swimming technique tips needed',
            'Football tournament coming up - who\'s in?',
            'Running group for beginners',
            'Cycling routes around the area',
            'Volleyball skills workshop',
            'Golf lessons recommendations',
            'Badminton doubles partner needed',
            'Squash court availability',
            'Table tennis tournament signup',
            'Cricket team recruitment',
            'Rugby training schedule',
            'Baseball league information',
            'Soccer pickup games location'
        ];

        $bodies = [
            'I\'m looking for some good tennis courts in the city. Any recommendations for places with good surfaces and reasonable rates?',
            'Planning to organize a basketball game this weekend. Looking for players of all skill levels. Let me know if you\'re interested!',
            'I\'ve been trying to improve my swimming technique. Anyone have tips for better freestyle form?',
            'There\'s a local football tournament coming up next month. Looking for team members. Anyone interested in joining?',
            'Starting a running group for beginners. We\'ll meet twice a week for 30-minute sessions. All paces welcome!',
            'Exploring new cycling routes around the area. Any scenic paths you\'d recommend for weekend rides?',
            'Organizing a volleyball skills workshop for intermediate players. Will cover serving, spiking, and positioning.',
            'Looking for golf lesson recommendations. Preferably someone who works with beginners and has flexible scheduling.',
            'Need a badminton doubles partner for weekly games. Intermediate level preferred. Anyone available?',
            'Trying to find available squash courts in the area. Any recommendations for facilities with good booking systems?',
            'Signing up for the upcoming table tennis tournament. Looking for practice partners to prepare.',
            'Our cricket team is recruiting new players for the upcoming season. All positions available, training provided.',
            'Rugby training sessions starting next week. New players welcome, equipment provided. Contact for details.',
            'Baseball league registration is now open. Teams forming for spring season. All skill levels welcome.',
            'Regular soccer pickup games at Memorial Park. Sundays at 2 PM. All skill levels welcome, just bring cleats!'
        ];

        $topic = fake()->unique()->randomElement($topics);
        $bodyIndex = array_search($topic, $topics);
        $body = $bodies[$bodyIndex] ?? fake()->paragraph(3);

        return [
            'title' => $topic,
            'body' => $body,
            'user_id' => User::factory(),
            'created_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'updated_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Create a popular discussion.
     */
    public function popular(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => fake()->randomElement([
                'Best tennis courts in the city?',
                'Looking for basketball players this weekend',
                'Football tournament coming up - who\'s in?',
                'Running group for beginners'
            ]),
        ]);
    }

    /**
     * Create a recent discussion.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'updated_at' => fake()->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    /**
     * Create a discussion about a specific sport.
     */
    public function forSport(string $sport): static
    {
        $sportTopics = [
            'Football' => 'Football tournament coming up - who\'s in?',
            'Basketball' => 'Looking for basketball players this weekend',
            'Tennis' => 'Best tennis courts in the city?',
            'Swimming' => 'Swimming technique tips needed',
            'Running' => 'Running group for beginners',
            'Cycling' => 'Cycling routes around the area',
            'Volleyball' => 'Volleyball skills workshop',
            'Golf' => 'Golf lessons recommendations',
            'Badminton' => 'Badminton doubles partner needed',
            'Squash' => 'Squash court availability',
            'Table Tennis' => 'Table tennis tournament signup',
            'Cricket' => 'Cricket team recruitment',
            'Rugby' => 'Rugby training schedule',
            'Baseball' => 'Baseball league information',
            'Soccer' => 'Soccer pickup games location'
        ];

        return $this->state(fn (array $attributes) => [
            'title' => $sportTopics[$sport] ?? fake()->sentence(),
        ]);
    }
}
