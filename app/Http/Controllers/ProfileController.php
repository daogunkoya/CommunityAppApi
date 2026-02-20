<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    /**
     * Get the current user's profile
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'location' => $user->location,
                    'phone' => $user->phone,
                    'profile_picture' => $user->profile_picture,
                    'full_name' => $user->full_name,
                    'email_verified_at' => $user->email_verified_at,
                    'bio' => $user->bio,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Profile show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch profile',
            ], 500);
        }
    }

    /**
     * Update the current user's profile
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $validated = $request->validated();

            // Handle profile picture upload
            Log::info('Checking for profile picture upload');
            Log::info('Request all data:', $request->all());
            Log::info('Request files:', $request->allFiles());
            if ($request->hasFile('profile_picture')) {
                $file = $request->file('profile_picture');

                // Validate file
                if (!$file->isValid()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid file upload',
                    ], 422);
                }

                // Check file size (max 5MB)
                if ($file->getSize() > 5 * 1024 * 1024) {
                    return response()->json([
                        'success' => false,
                        'message' => 'File size must be less than 5MB',
                    ], 422);
                }

                // Check file type
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!in_array($file->getMimeType(), $allowedTypes)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Only JPEG, PNG, and GIF images are allowed',
                    ], 422);
                }

                // Delete old profile picture if exists
                if ($user->profile_picture) {
                    Storage::disk('public')->delete($user->profile_picture);
                }

                // Store new profile picture
                $path = $file->store('profile-pictures', 'public');
                Log::info('Profile picture stored at: ' . $path);
                $validated['profile_picture'] = $path;
            } elseif (isset($validated['profile_picture']) && is_string($validated['profile_picture'])) {
                // Handle base64 image from mobile app
                $base64Image = $validated['profile_picture'];

                // Validate base64 format
                if (!preg_match('/^data:image\/(jpeg|jpg|png|gif);base64,/', $base64Image)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid image format',
                    ], 422);
                }

                // Extract image data
                $imageData = base64_decode(explode(',', $base64Image)[1]);

                // Check file size (max 2MB)
                if (strlen($imageData) > 2 * 1024 * 1024) {
                    return response()->json([
                        'success' => false,
                        'message' => 'File size must be less than 2MB',
                    ], 422);
                }

                // Delete old profile picture if exists
                if ($user->profile_picture && !str_starts_with($user->profile_picture, 'http')) {
                    Storage::disk('public')->delete($user->profile_picture);
                }

                // Generate filename and store
                $filename = 'profile-pictures/' . uniqid() . '.jpg';
                Storage::disk('public')->put($filename, $imageData);
                Log::info('Base64 profile picture stored at: ' . $filename);
                $validated['profile_picture'] = $filename;
            }

            // Check if email is being changed
            $emailChanged = isset($validated['email']) && $validated['email'] !== $user->email;

            // Update user
            $user->update($validated);

            // If email was changed, mark as unverified and send verification email
            if ($emailChanged) {
                $user->email_verified_at = null;
                $user->save();

                // Send verification email (you can implement this later)
                // $user->sendEmailVerificationNotification();
            }

            // Refresh the user model to get the updated data
            $user->refresh();

            $message = 'Profile updated successfully';
            if ($emailChanged) {
                $message .= '. Please check your new email address for verification.';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'location' => $user->location,
                    'phone' => $user->phone,
                    'profile_picture' => $user->profile_picture,
                    'full_name' => $user->full_name,
                    'email_verified_at' => $user->email_verified_at,
                    'bio' => $user->bio,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Profile update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
            ], 500);
        }
    }

    /**
     * Get user's sport interests
     */
    public function getInterests(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $interests = $user->gameInterests()
                ->get()
                ->map(function ($gameType) {
                    return [
                        'game_type_id' => $gameType->id,
                        'name' => $gameType->name,
                        'skill_level' => $gameType->pivot->skill_level ?? 1,
                        'color' => $gameType->color,
                        'icon_path' => $gameType->icon_path,
                        'description' => $gameType->description,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $interests,
            ]);

        } catch (\Exception $e) {
            Log::error('Get interests error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch interests',
            ], 500);
        }
    }

    /**
     * Update user's sport interests
     */
    public function updateInterests(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $validated = $request->validate([
                'interests' => 'required|array',
                'interests.*.game_type_id' => 'required|exists:game_types,id',
                'interests.*.skill_level' => 'required|integer|min:1|max:4',
            ]);

            // Prepare sync data
            $syncData = [];
            foreach ($validated['interests'] as $interest) {
                $syncData[$interest['game_type_id']] = [
                    'skill_level' => $interest['skill_level']
                ];
            }

            // Sync user interests
            $user->gameInterests()->sync($syncData);

            Log::info("User {$user->id} interests updated", [
                'interests' => $syncData,
            ]);

            // Return updated interests
            $updatedInterests = $user->gameInterests()
                ->get()
                ->map(function ($gameType) {
                    return [
                        'game_type_id' => $gameType->id,
                        'name' => $gameType->name,
                        'skill_level' => $gameType->pivot->skill_level,
                        'color' => $gameType->color,
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Interests updated successfully',
                'data' => $updatedInterests,
            ]);

        } catch (\Exception $e) {
            Log::error('Update interests error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update interests',
            ], 500);
        }
    }
    /**
     * Update user's password
     */
    public function updatePassword(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $validated = $request->validate([
                'current_password' => 'required|string',
                'password' => 'required|string|min:8|confirmed',
            ]);

            // Verify current password
            if (!\Illuminate\Support\Facades\Hash::check($validated['current_password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The provided current password does not match our records.',
                ], 422);
            }

            // Update with new hashed password
            $user->password = \Illuminate\Support\Facades\Hash::make($validated['password']);
            $user->save();

            Log::info("User {$user->id} updated their password.");

            return response()->json([
                'success' => true,
                'message' => 'Password updated successfully.',
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Update password error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update password',
            ], 500);
        }
    }

    public function destroy(Request $request): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            // Delete profile picture
            if ($user->profile_picture && !str_starts_with($user->profile_picture, 'http')) {
                Storage::disk('public')->delete($user->profile_picture);
            }

            // Delete user
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Account deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Account deletion error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete account'
            ], 500);
        }
    }
}
