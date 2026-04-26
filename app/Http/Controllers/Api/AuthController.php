<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\Organizer;
use App\Models\OtpCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    /**
     * Send OTP to the provided email address.
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $email = strtolower(trim($request->email));

        // Invalidate any existing unused OTPs for this email
        OtpCode::where('email', $email)
            ->where('is_used', false)
            ->update(['is_used' => true]);

        // Generate a 6-digit OTP
        $otpPlain = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store hashed OTP with 5-minute expiry
        OtpCode::create([
            'email' => $email,
            'code' => Hash::make($otpPlain),
            'expires_at' => now()->addMinutes(5),
        ]);

        // Send OTP via email
        Mail::to($email)->send(new OtpMail($otpPlain));

        return response()->json([
            'message' => 'OTP sent successfully. Please check your email.',
        ]);
    }

    /**
     * Verify OTP and authenticate the user.
     * Auto-creates organizer if email doesn't exist.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|max:255',
            'otp' => 'required|string|size:6',
        ]);

        $email = strtolower(trim($request->email));

        // Find the latest valid OTP for this email
        $otpRecord = OtpCode::valid($email)->first();

        if (!$otpRecord) {
            return response()->json([
                'message' => 'Invalid or expired OTP. Please request a new one.',
            ], 401);
        }

        // Verify OTP hash
        if (!Hash::check($request->otp, $otpRecord->code)) {
            return response()->json([
                'message' => 'Invalid OTP. Please try again.',
            ], 401);
        }

        // Mark OTP as used
        $otpRecord->update(['is_used' => true]);

        // Find or create organizer
        $isNew = false;
        $organizer = Organizer::where('email', $email)->first();

        if (!$organizer) {
            $organizer = Organizer::create([
                'email' => $email,
                'email_verified_at' => now(),
            ]);
            $isNew = true;
        } else {
            // Update verified timestamp on each login
            if (!$organizer->email_verified_at) {
                $organizer->update(['email_verified_at' => now()]);
            }
        }

        // Revoke any existing tokens
        $organizer->tokens()->delete();

        // Create new Sanctum token with configured expiry
        // Create new Sanctum token with configured expiry (default: 1 year)
        $expiryMinutes = (int) env('SANCTUM_TOKEN_EXPIRY_MINUTES', 525600);
        $token = $organizer->createToken(
            'auth-token',
            ['*'],
            now()->addMinutes($expiryMinutes)
        );

        return response()->json([
            'message' => $isNew ? 'Account created successfully.' : 'Logged in successfully.',
            'token' => $token->plainTextToken,
            'organizer' => [
                'id' => $organizer->id,
                'email' => $organizer->email,
                'name' => $organizer->name,
            ],
            'is_new' => $isNew,
        ]);
    }

    /**
     * Get the authenticated organizer's profile.
     */
    public function me(Request $request): JsonResponse
    {
        $organizer = $request->user();

        return response()->json([
            'organizer' => [
                'id' => $organizer->id,
                'email' => $organizer->email,
                'name' => $organizer->name,
            ],
        ]);
    }

    /**
     * Logout - revoke current token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}
