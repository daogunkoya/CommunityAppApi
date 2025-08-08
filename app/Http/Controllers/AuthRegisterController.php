<?php

namespace App\Http\Controllers;

use App\Http\Resources\AuthResource;
use App\Models\User;
use App\Models\Community;
use App\Notifications\EmailVerificationNotification;
use App\Services\LocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class AuthRegisterController extends Controller
{
    public function __invoke(Request $request)
    {
        $validatedRequest = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'password_confirmation' => ['required'],
            'location' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'community_name' => ['nullable', 'string', 'max:255'],
            'borough' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'in:male,female,other,prefer_not_to_say'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        try {
            DB::beginTransaction();

            // Hash the password before creating user
            $validatedRequest['password'] = Hash::make($validatedRequest['password']);

            // Remove password_confirmation as it's not needed in the database
            unset($validatedRequest['password_confirmation']);

            // Set location_verified to true if we have coordinates
            if (!empty($validatedRequest['latitude']) && !empty($validatedRequest['longitude'])) {
                $validatedRequest['location_verified'] = true;
            }

            // Create user
            $user = User::create($validatedRequest);

            // Assign user to community if we have location data
            if (!empty($validatedRequest['community_name']) || !empty($validatedRequest['borough'])) {
                $this->assignUserToCommunity($user, $validatedRequest);
            }

            // Generate email verification token
            $verificationToken = $user->generateEmailVerificationToken();

            // Send email verification notification
            $user->notify(new EmailVerificationNotification($verificationToken));

            DB::commit();

            // Return response without token (user needs to verify email first)
            return response()->json([
                'message' => 'Registration successful! Please check your email to verify your account.',
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                ],
                'requires_verification' => true,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            throw ValidationException::withMessages([
                'email' => ['Registration failed. Please try again.'],
            ]);
        }
    }

    /**
     * Assign user to community based on location data
     */
    private function assignUserToCommunity(User $user, array $locationData): void
    {
        try {
            $communityName = $locationData['community_name'] ?? $locationData['borough'] ?? null;
            $city = $locationData['city'] ?? 'London';
            $state = $locationData['state'] ?? 'England';
            $country = $locationData['country'] ?? 'UK';

            if ($communityName) {
                // Find or create community
                $community = Community::firstOrCreate(
                    [
                        'name' => $communityName,
                        'city' => $city,
                        'state' => $state,
                        'country' => $country,
                    ],
                    [
                        'type' => 'borough',
                        'latitude' => $locationData['latitude'] ?? null,
                        'longitude' => $locationData['longitude'] ?? null,
                        'description' => "Community in {$city}, {$state}",
                        'is_active' => true,
                    ]
                );

                // Assign user to community
                $user->communities()->attach($community->id, [
                    'is_primary' => true,
                    'is_active' => true,
                    'joined_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            // Log error but don't fail registration
            Log::error('Failed to assign user to community', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
