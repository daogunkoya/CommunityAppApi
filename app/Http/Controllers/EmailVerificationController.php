<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class EmailVerificationController extends Controller
{
    public function verify(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'token' => 'required|string',
        ]);

        $user = User::findOrFail($request->id);

        if ($user->email_verified_at) {
            return response()->json([
                'message' => 'Email already verified.',
                'verified' => true,
            ]);
        }

        if ($user->isEmailVerificationTokenExpired()) {
            return response()->json([
                'message' => 'Verification token has expired. Please request a new one.',
                'expired' => true,
            ], 400);
        }

        if ($user->verifyEmail($request->token)) {
            // Create token after successful verification
            $token = $user->createToken('auth-token')->accessToken;

            return response()->json([
                'message' => 'Email verified successfully! You can now log in.',
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                ],
                'token' => [
                    'accessTokenId' => $token->id,
                    'tokenType' => 'Bearer',
                    'expiresIn' => 60 * 24 * 30, // 30 days
                    'accessToken' => $token->token,
                ],
                'verified' => true,
            ]);
        }

        return response()->json([
            'message' => 'Invalid verification token.',
            'verified' => false,
        ], 400);
    }

    public function resend(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user->email_verified_at) {
            return response()->json([
                'message' => 'Email is already verified.',
            ], 400);
        }

        // Generate new verification token
        $verificationToken = $user->generateEmailVerificationToken();

        // Send new verification email
        $user->notify(new \App\Notifications\EmailVerificationNotification($verificationToken));

        return response()->json([
            'message' => 'Verification email sent successfully.',
        ]);
    }
}
