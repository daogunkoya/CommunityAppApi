<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\GameType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class RegistrationControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Create some game types for testing
        GameType::create(['name' => 'Tennis']);
        GameType::create(['name' => 'Football']);
        GameType::create(['name' => 'Basketball']);
    }

    /**
     * Test get sports endpoint
     */
    public function test_get_sports()
    {
        $response = $this->getJson('/api/registration/sports');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                    ],
                ],
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    /**
     * Test successful registration
     */
    public function test_successful_registration()
    {
        $response = $this->postJson('/api/registration/register', [
            'fullName' => 'John Doe',
            'dateOfBirth' => '2000-01-01',
            'gender' => 'male',
            'location' => 'London, UK',
            'radius' => 10,
            'selectedSports' => [1, 2], // Tennis and Football
            'skillLevels' => [
                1 => 'intermediate',
                2 => 'beginner',
            ],
            'mainGoal' => 'Make friends and stay active',
            'email' => 'john@example.com',
            'password' => 'password123',
            'authProvider' => 'email',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Registration successful',
            ])
            ->assertJsonStructure([
                'data' => [
                    'user' => [
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                        // 'age', // age is removed
                        'gender',
                        'location',
                        'radius',
                        'main_goal',
                        'auth_provider',
                        'skill_levels' => [
                            '*' => [
                                'id',
                                'game_type_id',
                                'skill_level',
                                'game_type' => [
                                    'id',
                                    'name',
                                ],
                            ],
                        ],
                    ],
                    'token',
                ],
            ]);

        // Verify user was created
        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => '2000-01-01 00:00:00',
            'gender' => 'male',
            'location' => 'London, UK',
            'radius' => 10,
            'main_goal' => 'Make friends and stay active',
            'auth_provider' => 'email',
        ]);

        // Verify skill levels were created
        $user = User::where('email', 'john@example.com')->first();
        $this->assertCount(2, $user->skillLevels);
    }

    /**
     * Test registration with Google auth provider
     */
    public function test_registration_with_google_auth()
    {
        $response = $this->postJson('/api/registration/register', [
            'fullName' => 'Jane Smith',
            'dateOfBirth' => '1995-01-01', // 30 years old
            'gender' => 'female',
            'location' => 'Manchester, UK',
            'radius' => 5,
            'selectedSports' => [1], // Tennis only
            'skillLevels' => [
                1 => 'advanced',
            ],
            'mainGoal' => 'Competitive play',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'authProvider' => 'google',
            'authProviderId' => 'google_123456',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
            'auth_provider' => 'google',
            'auth_provider_id' => 'google_123456',
        ]);
    }

    /**
     * Test registration validation errors
     */
    public function test_registration_validation_errors()
    {
        $response = $this->postJson('/api/registration/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'fullName',
                'dateOfBirth',
                'selectedSports',
                'skillLevels',
                'selectedSports',
                'skillLevels',
                'mainGoal',
                'email',
                'authProvider'
            ]);
    }

    /**
     * Test registration with invalid age
     */
    public function test_registration_with_invalid_age()
    {
        $response = $this->postJson('/api/registration/register', [
            'fullName' => 'John Doe',
            'dateOfBirth' => now()->subYears(5)->format('Y-m-d'), // 5 years old (Too young)
            'gender' => 'male',
            'location' => 'London, UK',
            'radius' => 10,
            'selectedSports' => [1],
            'skillLevels' => [1 => 'beginner'],
            'mainGoal' => 'Make friends',
            'email' => 'john@example.com',
            'password' => 'password123',
            'authProvider' => 'email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['dateOfBirth']);
    }

    /**
     * Test registration with invalid gender
     */
    public function test_registration_with_invalid_gender()
    {
        $response = $this->postJson('/api/registration/register', [
            'fullName' => 'John Doe',
            'dateOfBirth' => '2000-01-01',
            'gender' => 'invalid_gender',
            'location' => 'London, UK',
            'radius' => 10,
            'selectedSports' => [1],
            'skillLevels' => [1 => 'beginner'],
            'mainGoal' => 'Make friends',
            'email' => 'john@example.com',
            'password' => 'password123',
            'authProvider' => 'email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['gender']);
    }

    /**
     * Test registration with invalid skill level
     */
    public function test_registration_with_invalid_skill_level()
    {
        $response = $this->postJson('/api/registration/register', [
            'fullName' => 'John Doe',
            'dateOfBirth' => '2000-01-01',
            'gender' => 'male',
            'location' => 'London, UK',
            'radius' => 10,
            'selectedSports' => [1],
            'skillLevels' => [1 => 'invalid_level'],
            'mainGoal' => 'Make friends',
            'email' => 'john@example.com',
            'password' => 'password123',
            'authProvider' => 'email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['skillLevels.1']);
    }

    /**
     * Test registration with duplicate email
     */
    public function test_registration_with_duplicate_email()
    {
        User::factory()->create(['email' => 'john@example.com']);

        $response = $this->postJson('/api/registration/register', [
            'fullName' => 'John Doe',
            'dateOfBirth' => '2000-01-01',
            'gender' => 'male',
            'location' => 'London, UK',
            'radius' => 10,
            'selectedSports' => [1],
            'skillLevels' => [1 => 'beginner'],
            'mainGoal' => 'Make friends',
            'email' => 'john@example.com',
            'password' => 'password123',
            'authProvider' => 'email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test registration with invalid sport ID
     */
    public function test_registration_with_invalid_sport_id()
    {
        $response = $this->postJson('/api/registration/register', [
            'fullName' => 'John Doe',
            'dateOfBirth' => '2000-01-01',
            'gender' => 'male',
            'location' => 'London, UK',
            'radius' => 10,
            'selectedSports' => [999], // Non-existent sport
            'skillLevels' => [999 => 'beginner'],
            'mainGoal' => 'Make friends',
            'email' => 'john@example.com',
            'password' => 'password123',
            'authProvider' => 'email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['selectedSports.0']);
    }

    /**
     * Test registration with weak password
     */
    public function test_registration_with_weak_password()
    {
        $response = $this->postJson('/api/registration/register', [
            'fullName' => 'John Doe',
            'dateOfBirth' => '2000-01-01',
            'gender' => 'male',
            'location' => 'London, UK',
            'radius' => 10,
            'selectedSports' => [1],
            'skillLevels' => [1 => 'beginner'],
            'mainGoal' => 'Make friends',
            'email' => 'john@example.com',
            'password' => '123', // Too short
            'authProvider' => 'email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test registration with invalid auth provider
     */
    public function test_registration_with_invalid_auth_provider()
    {
        $response = $this->postJson('/api/registration/register', [
            'fullName' => 'John Doe',
            'dateOfBirth' => '2000-01-01',
            'gender' => 'male',
            'location' => 'London, UK',
            'radius' => 10,
            'selectedSports' => [1],
            'skillLevels' => [1 => 'beginner'],
            'mainGoal' => 'Make friends',
            'email' => 'john@example.com',
            'password' => 'password123',
            'authProvider' => 'invalid_provider',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['authProvider']);
    }

    /**
     * Test facilities search endpoint
     */
    public function test_search_facilities()
    {
        $response = $this->postJson('/api/registration/facilities/search', [
            'location' => 'London, UK',
            'radius' => 5,
        ]);

        // This might return 503 if Google Maps service is not configured
        // or 200 if it's working
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'location',
            ]);
    }

    /**
     * Test facilities search validation
     */
    public function test_search_facilities_validation()
    {
        $response = $this->postJson('/api/registration/facilities/search', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['location']);

        $response = $this->postJson('/api/registration/facilities/search', [
            'location' => 'London, UK',
            'radius' => 100, // Too large
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['radius']);
    }

    /**
     * Test registration password required for email provider
     */
    public function test_registration_password_required_for_email_provider()
    {
        $response = $this->postJson('/api/registration/register', [
            'fullName' => 'John Doe',
            'dateOfBirth' => '2000-01-01',
            'gender' => 'male',
            'location' => 'London, UK',
            'radius' => 10,
            'selectedSports' => [1],
            'skillLevels' => [1 => 'beginner'],
            'mainGoal' => 'Make friends',
            'email' => 'john@example.com',
            // password missing
            'authProvider' => 'email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }
}
