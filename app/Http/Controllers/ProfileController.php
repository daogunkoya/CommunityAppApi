<?php

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
                    'location' => $user->location,
                    'phone' => $user->phone,
                    'profile_picture' => $user->profile_picture,
                    'full_name' => $user->full_name,
                    'email_verified_at' => $user->email_verified_at,
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
}
