<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Passport\Client;

class SocialAuthControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Create personal access client for tests
        $client = Client::create([
            'id' => 'test-client-id',
            'name' => 'Test Personal Access Client',
            'secret' => 'test-secret',
            'provider' => 'users',
            'redirect_uris' => 'http://localhost',
            'grant_types' => json_encode(['personal_access']),
            'revoked' => false,
        ]);

        // Create personal access client record
        \Illuminate\Support\Facades\DB::table('oauth_personal_access_clients')->insert([
            'client_id' => $client->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test successful Google authentication for new user
     */
    public function test_successful_google_auth_new_user()
    {
        $response = $this->postJson('/api/social-auth', [
            'provider' => 'google',
            'provider_id' => 'google_123456',
            'email' => 'test@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'profile_picture' => 'https://example.com/avatar.jpg',
            'access_token' => 'valid_google_token',
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Social authentication successful',
                ])
                ->assertJsonStructure([
                    'data' => [
                        'user' => [
                            'id',
                            'first_name',
                            'last_name',
                            'email',
                            'auth_provider',
                            'auth_provider_id',
                            'profile_picture',
                            'email_verified_at',
                        ],
                        'token',
                    ],
                ]);

        // Verify user was created
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'auth_provider' => 'google',
            'auth_provider_id' => 'google_123456',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
    }

    /**
     * Test successful Google authentication for existing user
     */
    public function test_successful_google_auth_existing_user()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'auth_provider' => 'google',
            'auth_provider_id' => 'google_123456',
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/social-auth', [
            'provider' => 'google',
            'provider_id' => 'google_123456',
            'email' => 'test@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'profile_picture' => 'https://example.com/avatar.jpg',
            'access_token' => 'valid_google_token',
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Social authentication successful',
                ]);

        // Verify user was not duplicated
        $this->assertDatabaseCount('users', 1);
    }

    /**
     * Test linking existing email user to Google
     */
    public function test_link_existing_email_user_to_google()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'auth_provider' => 'email',
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/social-auth', [
            'provider' => 'google',
            'provider_id' => 'google_123456',
            'email' => 'test@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'profile_picture' => 'https://example.com/avatar.jpg',
            'access_token' => 'valid_google_token',
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                ]);

        // Verify user was updated with Google auth
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'test@example.com',
            'auth_provider' => 'google',
            'auth_provider_id' => 'google_123456',
        ]);
    }

    /**
     * Test Facebook authentication
     */
    public function test_facebook_auth()
    {
        $response = $this->postJson('/api/social-auth', [
            'provider' => 'facebook',
            'provider_id' => 'fb_123456',
            'email' => 'test@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'profile_picture' => 'https://example.com/avatar.jpg',
            'access_token' => 'valid_facebook_token',
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'auth_provider' => 'facebook',
            'auth_provider_id' => 'fb_123456',
        ]);
    }

    /**
     * Test Apple authentication
     */
    public function test_apple_auth()
    {
        $response = $this->postJson('/api/social-auth', [
            'provider' => 'apple',
            'provider_id' => 'apple_123456',
            'email' => 'test@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'profile_picture' => null,
            'access_token' => 'valid_apple_token',
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'auth_provider' => 'apple',
            'auth_provider_id' => 'apple_123456',
        ]);
    }

    /**
     * Test validation errors
     */
    public function test_validation_errors()
    {
        $response = $this->postJson('/api/social-auth', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['provider', 'provider_id', 'email', 'first_name', 'last_name', 'access_token']);

        $response = $this->postJson('/api/social-auth', [
            'provider' => 'invalid_provider',
            'provider_id' => '',
            'email' => 'invalid-email',
            'first_name' => '',
            'last_name' => '',
            'access_token' => '',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['provider', 'provider_id', 'email', 'first_name', 'last_name', 'access_token']);
    }

    /**
     * Test invalid provider
     */
    public function test_invalid_provider()
    {
        $response = $this->postJson('/api/social-auth', [
            'provider' => 'invalid_provider',
            'provider_id' => 'test_123',
            'email' => 'test@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'access_token' => 'valid_token',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['provider']);
    }

    /**
     * Test missing required fields
     */
    public function test_missing_required_fields()
    {
        $response = $this->postJson('/api/social-auth', [
            'provider' => 'google',
            'email' => 'test@example.com',
            // Missing provider_id, first_name, last_name, access_token
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['provider_id', 'first_name', 'last_name', 'access_token']);
    }

    /**
     * Test invalid email format
     */
    public function test_invalid_email_format()
    {
        $response = $this->postJson('/api/social-auth', [
            'provider' => 'google',
            'provider_id' => 'google_123456',
            'email' => 'invalid-email',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'access_token' => 'valid_token',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test profile picture URL validation
     */
    public function test_profile_picture_url_validation()
    {
        $response = $this->postJson('/api/social-auth', [
            'provider' => 'google',
            'provider_id' => 'google_123456',
            'email' => 'test@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'profile_picture' => 'not-a-url',
            'access_token' => 'valid_token',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['profile_picture']);
    }
}
