<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShopInvitation\SendInvitationRequest;
use App\Models\Role;
use App\Models\Shop;
use App\Models\ShopUser;
use App\Models\User;
use App\Notifications\ShopInvitationNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ShopInvitationController extends Controller
{
    /**
     * Send a shop invitation to a user.
     */
    public function send(SendInvitationRequest $request): JsonResponse
    {
        $inviter = $request->user();

        // Verify shop access
        $shop = Shop::query()
            ->where('id', $request->shop_id)
            ->where(function ($q) use ($inviter) {
                $q->where('owner_id', $inviter->id)
                    ->orWhereHas('users', fn ($query) => $query->where('user_id', $inviter->id));
            })
            ->first();

        if (! $shop) {
            return response()->json([
                'message' => 'Shop not found or you do not have access to it.',
            ], 404);
        }

        // Verify role belongs to the shop or is a system role
        $role = Role::query()
            ->where('id', $request->role_id)
            ->where(function ($q) use ($shop) {
                $q->whereNull('shop_id')  // System roles
                    ->orWhere('shop_id', $shop->id);  // Shop-specific roles
            })
            ->first();

        if (! $role) {
            return response()->json([
                'message' => 'Role not found or does not belong to this shop.',
            ], 404);
        }

        // Find the user to invite, or create a placeholder account
        $invitedUser = User::where('email', $request->email)->first();

        $defaultPassword = null;
        $wasCreated = false;

        if (! $invitedUser) {
            // Create a placeholder user account with default password
            $defaultPassword = config('app.default_password', 'TiwineBiz@2025');
            $wasCreated = true;

            $invitedUser = User::create([
                'email' => $request->email,
                'name' => explode('@', $request->email)[0], // Use email username as placeholder
                'phone' => null, // No phone number yet
                'password_hash' => bcrypt($defaultPassword),
                'is_active' => false, // Inactive until they accept invitation and set password
                'email_verified_at' => null, // Will need to verify email
            ]);
        }

        // Check for existing membership or pending invitation
        $existingMembership = ShopUser::query()
            ->where('shop_id', $shop->id)
            ->where('user_id', $invitedUser->id)
            ->first();

        if ($existingMembership) {
            // Check if it's a pending invitation (not accepted yet)
            if (is_null($existingMembership->invitation_accepted_at) && $existingMembership->invitation_expires_at > now()) {
                return response()->json([
                    'message' => 'A pending invitation already exists for this user.',
                ], 422);
            }

            // Check if user is an active member
            if ($existingMembership->is_active) {
                return response()->json([
                    'message' => 'User is already a member of this shop.',
                ], 422);
            }

            // User was previously a member but is now inactive
            return response()->json([
                'message' => 'User was previously a member of this shop. Please reactivate their membership instead.',
            ], 422);
        }

        // Create invitation
        DB::beginTransaction();

        try {
            $invitationToken = Str::random(64);

            $shopUser = ShopUser::create([
                'shop_id' => $shop->id,
                'user_id' => $invitedUser->id,
                'role_id' => $role->id,
                'is_active' => false,
                'invited_by' => $inviter->id,
                'invitation_token' => $invitationToken,
                'invitation_expires_at' => now()->addDays(7),
            ]);

            // Generate acceptance URL
            $acceptUrl = config('app.frontend_url').'/invitations/accept/'.$invitationToken;

            // Send notification (include default password if a new user was created)
            $invitedUser->notify(new ShopInvitationNotification(
                $shop->name,
                $inviter->name,
                $role->display_name,
                $acceptUrl,
                $wasCreated ? $defaultPassword : null
            ));

            DB::commit();

            return response()->json([
                'message' => 'Invitation sent successfully.',
                'data' => [
                    'shop_id' => $shop->id,
                    'user_email' => $invitedUser->email,
                    'role_name' => $role->display_name,
                    'expires_at' => $shopUser->invitation_expires_at->toIso8601String(),
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to send invitation.',
            ], 500);
        }
    }

    /**
     * Accept a shop invitation.
     */
    public function accept(Request $request, string $token): JsonResponse
    {
        $user = $request->user();

        // Find the invitation
        $shopUser = ShopUser::query()
            ->where('invitation_token', $token)
            ->where('user_id', $user->id)
            ->whereNull('invitation_accepted_at')
            ->where('invitation_expires_at', '>', now())
            ->first();

        if (! $shopUser) {
            return response()->json([
                'message' => 'Invalid or expired invitation.',
            ], 404);
        }

        // Accept the invitation
        $shopUser->update([
            'is_active' => true,
            'invitation_accepted_at' => now(),
            'joined_at' => now(),
            'invitation_token' => null, // Clear token after acceptance
        ]);

        return response()->json([
            'message' => 'Invitation accepted successfully.',
            'data' => [
                'shop_id' => $shopUser->shop_id,
                'role_id' => $shopUser->role_id,
            ],
        ]);
    }

    /**
     * Decline a shop invitation.
     */
    public function decline(Request $request, string $token): JsonResponse
    {
        $user = $request->user();

        // Find the invitation
        $shopUser = ShopUser::query()
            ->where('invitation_token', $token)
            ->where('user_id', $user->id)
            ->whereNull('invitation_accepted_at')
            ->where('invitation_expires_at', '>', now())
            ->first();

        if (! $shopUser) {
            return response()->json([
                'message' => 'Invalid or expired invitation.',
            ], 404);
        }

        // Delete the invitation
        $shopUser->delete();

        return response()->json([
            'message' => 'Invitation declined successfully.',
        ]);
    }

    /**
     * List pending invitations for the authenticated user.
     */
    public function pending(Request $request): JsonResponse
    {
        $user = $request->user();

        $invitations = ShopUser::query()
            ->where('user_id', $user->id)
            ->whereNull('invitation_accepted_at')
            ->where('invitation_expires_at', '>', now())
            ->with(['shop', 'role', 'invitedBy'])
            ->orderBy('invitation_expires_at', 'asc')
            ->get();

        return response()->json([
            'data' => $invitations->map(function ($invitation) {
                return [
                    'token' => $invitation->invitation_token,
                    'shop' => [
                        'id' => $invitation->shop->id,
                        'name' => $invitation->shop->name,
                    ],
                    'role' => [
                        'id' => $invitation->role->id,
                        'display_name' => $invitation->role->display_name,
                    ],
                    'invited_by' => [
                        'name' => $invitation->invitedBy->name ?? 'Unknown',
                    ],
                    'expires_at' => $invitation->invitation_expires_at->toIso8601String(),
                ];
            }),
        ]);
    }

    /**
     * List pending invitations sent by a shop.
     */
    public function pendingForShop(Request $request): JsonResponse
    {
        $request->validate([
            'shop_id' => ['required', 'uuid'],
        ]);

        $user = $request->user();

        // Verify shop access
        $shop = Shop::query()
            ->where('id', $request->shop_id)
            ->where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                    ->orWhereHas('users', fn ($query) => $query->where('user_id', $user->id));
            })
            ->first();

        if (! $shop) {
            return response()->json([
                'message' => 'Shop not found or you do not have access to it.',
            ], 404);
        }

        // Get pending invitations for this shop
        $invitations = ShopUser::query()
            ->where('shop_id', $shop->id)
            ->whereNull('invitation_accepted_at')
            ->where('invitation_expires_at', '>', now())
            ->with(['user', 'role', 'invitedBy'])
            ->orderBy('invitation_expires_at', 'desc')
            ->get();

        return response()->json([
            'data' => $invitations->map(function ($invitation) {
                return [
                    'id' => $invitation->id,
                    'email' => $invitation->user?->email,
                    'role' => [
                        'id' => $invitation->role->id,
                        'display_name' => $invitation->role->display_name,
                        'name' => $invitation->role->name,
                    ],
                    'invited_by' => [
                        'name' => $invitation->invitedBy->name ?? 'Unknown',
                    ],
                    'invited_at' => $invitation->invitation_expires_at->subDays(7)->toIso8601String(),
                    'expires_at' => $invitation->invitation_expires_at->toIso8601String(),
                ];
            }),
        ]);
    }

    /**
     * Cancel a shop invitation (by the inviter).
     */
    public function cancel(Request $request, string $shopId, string $userId): JsonResponse
    {
        $canceler = $request->user();

        // Verify shop access
        $shop = Shop::query()
            ->where('id', $shopId)
            ->where('owner_id', $canceler->id)
            ->first();

        if (! $shop) {
            return response()->json([
                'message' => 'Shop not found or you do not have permission to cancel invitations.',
            ], 404);
        }

        // Find the pending invitation
        $shopUser = ShopUser::query()
            ->where('shop_id', $shopId)
            ->where('user_id', $userId)
            ->whereNull('invitation_accepted_at')
            ->where('invitation_expires_at', '>', now())
            ->first();

        if (! $shopUser) {
            return response()->json([
                'message' => 'Pending invitation not found.',
            ], 404);
        }

        // Delete the invitation
        $shopUser->delete();

        return response()->json([
            'message' => 'Invitation cancelled successfully.',
        ]);
    }
}
