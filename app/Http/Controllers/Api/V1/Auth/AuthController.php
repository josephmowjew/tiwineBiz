<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\ShopUser;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password_hash' => Hash::make($request->password),
                'profile_photo_url' => $request->profile_photo_url,
                'preferred_language' => $request->preferred_language ?? 'en',
                'timezone' => $request->timezone ?? 'Africa/Blantyre',
                'is_active' => true,
            ]);

            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'message' => 'Registration successful.',
                'user' => new UserResource($user),
                'token' => $token,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Registration failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Login user with email or phone.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            // Determine if login is email or phone
            $loginField = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

            // Find user by email or phone
            $user = User::where($loginField, $request->login)->first();

            // Check if user exists and password is correct
            if (! $user || ! Hash::check($request->password, $user->password_hash)) {
                throw ValidationException::withMessages([
                    'login' => ['The provided credentials are incorrect.'],
                ]);
            }

            // Check if user is active
            if (! $user->is_active) {
                return response()->json([
                    'message' => 'Your account has been deactivated. Please contact support.',
                ], 403);
            }

            // Check if account is locked
            if ($user->locked_until && $user->locked_until->isFuture()) {
                return response()->json([
                    'message' => 'Your account is temporarily locked. Please try again later.',
                    'locked_until' => $user->locked_until,
                ], 403);
            }

            // Update last login
            $user->update([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ]);

            // Load user's shop and role information
            $user->load(['ownedShops', 'shops']);

            // Get user's primary shop (first owned shop or first associated shop)
            $primaryShop = $user->ownedShops->first() ?? $user->shops->first();

            // Get user's role in their primary shop
            $role = null;
            $shopId = null;

            if ($primaryShop) {
                $shopId = $primaryShop->id;
                $shopUser = ShopUser::where('user_id', $user->id)
                    ->where('shop_id', $shopId)
                    ->with('role')
                    ->first();

                if ($shopUser && $shopUser->role) {
                    $role = $shopUser->role->name;
                }
            }

            // Create token
            $token = $user->createToken('auth-token')->plainTextToken;

            // Add role and shop_id to user resource
            $userArray = (new \App\Http\Resources\UserResource($user))->toArray(request());
            $userArray['role'] = $role;
            $userArray['shop_id'] = $shopId;

            return response()->json([
                'message' => 'Login successful.',
                'user' => $userArray,
                'token' => $token,
            ], 200);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Login failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Logout user (revoke token).
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $token = $request->user()->currentAccessToken();

            if ($token) {
                $token->delete();
            }

            return response()->json([
                'message' => 'Logout successful.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Logout failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get authenticated user.
     */
    public function user(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Load relationships if requested
            if ($request->has('include')) {
                $includes = explode(',', $request->input('include'));
                $allowedIncludes = ['ownedShops', 'shops'];
                $validIncludes = array_intersect($includes, $allowedIncludes);

                if (! empty($validIncludes)) {
                    $user->load($validIncludes);
                }
            }

            // Get user's primary shop and role
            $user->load(['ownedShops', 'shops']);
            $primaryShop = $user->ownedShops->first() ?? $user->shops->first();

            $role = null;
            $shopId = null;

            if ($primaryShop) {
                $shopId = $primaryShop->id;
                $shopUser = ShopUser::where('user_id', $user->id)
                    ->where('shop_id', $shopId)
                    ->with('role')
                    ->first();

                if ($shopUser && $shopUser->role) {
                    $role = $shopUser->role->name;
                }
            }

            // Add role and shop_id to user resource
            $userArray = (new \App\Http\Resources\UserResource($user))->toArray($request);
            $userArray['role'] = $role;
            $userArray['shop_id'] = $shopId;

            return response()->json([
                'user' => $userArray,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve user data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send password reset link to user's email.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $status = Password::sendResetLink(
                $request->only('email')
            );

            if ($status === Password::RESET_LINK_SENT) {
                return response()->json([
                    'message' => 'Password reset link sent to your email address.',
                ], 200);
            }

            return response()->json([
                'message' => 'Unable to send password reset link. Please try again.',
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send password reset link.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reset user password with token.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function (User $user, string $password) {
                    // Update password_hash field instead of password
                    $user->forceFill([
                        'password_hash' => Hash::make($password),
                    ])->save();

                    // Revoke all existing tokens for security
                    $user->tokens()->delete();
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return response()->json([
                    'message' => 'Password reset successful. Please login with your new password.',
                ], 200);
            }

            if ($status === Password::INVALID_TOKEN) {
                return response()->json([
                    'message' => 'Invalid or expired reset token. Please request a new one.',
                ], 400);
            }

            return response()->json([
                'message' => 'Unable to reset password. Please try again.',
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to reset password.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Change authenticated user's password.
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Verify current password
            if (! Hash::check($request->current_password, $user->password_hash)) {
                return response()->json([
                    'message' => 'The current password is incorrect.',
                    'errors' => [
                        'current_password' => ['The current password is incorrect.'],
                    ],
                ], 422);
            }

            // Update password
            $user->update([
                'password_hash' => Hash::make($request->password),
            ]);

            // Revoke all tokens except current one
            // This forces logout on other devices for security
            $currentToken = $user->currentAccessToken();
            if ($currentToken) {
                $user->tokens()->where('id', '!=', $currentToken->id)->delete();
            }

            return response()->json([
                'message' => 'Password changed successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to change password.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send email verification link.
     */
    public function sendVerificationEmail(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Check if already verified
            if ($user->email_verified_at) {
                return response()->json([
                    'message' => 'Email already verified.',
                ], 400);
            }

            // Generate verification URL
            $verificationUrl = $this->generateVerificationUrl($user);

            // Send verification email
            $user->notify(new VerifyEmailNotification($verificationUrl));

            return response()->json([
                'message' => 'Verification email sent successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send verification email.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify user's email address.
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        try {
            $userId = $request->route('id');
            $hash = $request->route('hash');

            // Find user
            $user = User::findOrFail($userId);

            // Check if already verified
            if ($user->email_verified_at) {
                return response()->json([
                    'message' => 'Email already verified.',
                ], 400);
            }

            // Verify hash matches email
            if (! hash_equals($hash, sha1($user->email))) {
                return response()->json([
                    'message' => 'Invalid verification link.',
                ], 400);
            }

            // Verify signature
            if (! $request->hasValidSignature()) {
                return response()->json([
                    'message' => 'Invalid or expired verification link.',
                ], 400);
            }

            // Mark email as verified
            $user->update([
                'email_verified_at' => now(),
            ]);

            return response()->json([
                'message' => 'Email verified successfully.',
                'user' => new UserResource($user),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to verify email.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate signed verification URL.
     */
    protected function generateVerificationUrl(User $user): string
    {
        return URL::temporarySignedRoute(
            'api.v1.auth.verify-email',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );
    }
}
