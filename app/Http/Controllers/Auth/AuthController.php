<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AuthController extends Controller
{
    // ────────────────────────────────────────────────────────────────
    // REGISTER
    // ────────────────────────────────────────────────────────────────

    /**
     * POST /api/auth/register
     * Roles: customer | vendor
     */
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'              => ['required', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()],
            'role'                  => ['required', 'in:customer,vendor'],

            // Only required when registering as vendor
            'store_name'            => ['required_if:role,vendor', 'string', 'max:255', 'unique:vendors'],
            'store_description'     => ['nullable', 'string', 'max:1000'],
        ]);

        DB::beginTransaction();

        try {
            $verificationToken = Str::random(64);

            $user = User::create([
                'name'                       => $data['name'],
                'email'                      => $data['email'],
                'password'                   => $data['password'],
                'role'                       => $data['role'],
                'email_verification_token'   => $verificationToken,
            ]);

            // If registering as vendor → create pending vendor profile
            if ($data['role'] === 'vendor') {
                Vendor::create([
                    'user_id'     => $user->id,
                    'store_name'  => $data['store_name'],
                    'description' => $data['store_description'] ?? null,
                    'status'      => 'pending',
                ]);
            }

            // Send verification email
            event(new Registered($user));

            DB::commit();

            return response()->json([
                'message' => 'Registration successful. Please verify your email.',
                'user'    => $this->userResponse($user),
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Registration failed.', 'error' => $e->getMessage()], 500);
        }
    }

    // ────────────────────────────────────────────────────────────────
    // LOGIN
    // ────────────────────────────────────────────────────────────────

    /**
     * POST /api/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        /** @var User $user */
        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();
            return response()->json(['message' => 'Your account has been suspended.'], 403);
        }

        // Revoke all previous tokens (single session)
        $user->tokens()->delete();

        $token = $user->createToken('auth_token', [$user->role])->plainTextToken;

        return response()->json([
            'message'       => 'Login successful.',
            'token'         => $token,
            'token_type'    => 'Bearer',
            'user'          => $this->userResponse($user),
        ]);
    }

    // ────────────────────────────────────────────────────────────────
    // LOGOUT
    // ────────────────────────────────────────────────────────────────

    /**
     * POST /api/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    // ────────────────────────────────────────────────────────────────
    // ME (current user profile)
    // ────────────────────────────────────────────────────────────────

    /**
     * GET /api/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('vendor');

        return response()->json(['user' => $this->userResponse($user)]);
    }

    // ────────────────────────────────────────────────────────────────
    // EMAIL VERIFICATION
    // ────────────────────────────────────────────────────────────────

    /**
     * GET /api/auth/verify-email/{token}
     */
    public function verifyEmail(Request $request, string $token): JsonResponse
    {
        $user = User::where('email_verification_token', $token)->first();

        if (! $user) {
            return response()->json(['message' => 'Invalid or expired verification token.'], 400);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.']);
        }

        $user->markEmailAsVerified();
        $user->update(['email_verification_token' => null]);

        return response()->json(['message' => 'Email verified successfully. You can now log in.']);
    }

    /**
     * POST /api/auth/resend-verification
     */
    public function resendVerification(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.'], 400);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification email resent.']);
    }

    // ────────────────────────────────────────────────────────────────
    // PASSWORD RESET
    // ────────────────────────────────────────────────────────────────

    /**
     * POST /api/auth/forgot-password
     * Sends a password reset link
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'Password reset link sent to your email.']);
        }

        return response()->json(['message' => 'Unable to send reset link. Please check your email.'], 400);
    }

    /**
     * POST /api/auth/reset-password
     * Resets the password with the token from email
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password'       => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                // Revoke all tokens for security
                $user->tokens()->delete();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Password reset successfully. Please log in.']);
        }

        return response()->json(['message' => 'Invalid or expired reset token.'], 400);
    }

    // ────────────────────────────────────────────────────────────────
    // HELPERS
    // ────────────────────────────────────────────────────────────────

    private function userResponse(User $user): array
    {
        $data = [
            'id'                => $user->id,
            'name'              => $user->name,
            'email'             => $user->email,
            'role'              => $user->role,
            'avatar'            => $user->avatar,
            'phone'             => $user->phone,
            'email_verified'    => ! is_null($user->email_verified_at),
            'is_active'         => $user->is_active,
            'created_at'        => $user->created_at,
        ];

        // Attach vendor info if applicable
        if ($user->relationLoaded('vendor') && $user->vendor) {
            $data['vendor'] = [
                'id'          => $user->vendor->id,
                'store_name'  => $user->vendor->store_name,
                'store_slug'  => $user->vendor->store_slug,
                'logo'        => $user->vendor->logo,
                'status'      => $user->vendor->status,
            ];
        }

        return $data;
    }
}
