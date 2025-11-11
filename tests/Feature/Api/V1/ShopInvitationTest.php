<?php

namespace Tests\Feature\Api\V1;

use App\Models\Role;
use App\Models\Shop;
use App\Models\ShopUser;
use App\Models\User;
use App\Notifications\ShopInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ShopInvitationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_shop_owner_can_send_invitation(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $shop = Shop::factory()->create(['owner_id' => $owner->id]);
        $role = Role::factory()->create(['shop_id' => $shop->id]);
        $invitedUser = User::factory()->create();

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/shop-invitations/send', [
                'shop_id' => $shop->id,
                'email' => $invitedUser->email,
                'role_id' => $role->id,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Invitation sent successfully.',
            ]);

        $this->assertDatabaseHas('shop_users', [
            'shop_id' => $shop->id,
            'user_id' => $invitedUser->id,
            'role_id' => $role->id,
            'is_active' => false,
            'invited_by' => $owner->id,
        ]);

        Notification::assertSentTo($invitedUser, ShopInvitationNotification::class);
    }

    public function test_shop_member_can_send_invitation(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $member = User::factory()->create();
        $shop = Shop::factory()->create(['owner_id' => $owner->id]);
        $role = Role::factory()->create(['shop_id' => $shop->id]);

        // Add member to shop
        ShopUser::create([
            'shop_id' => $shop->id,
            'user_id' => $member->id,
            'role_id' => $role->id,
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $invitedUser = User::factory()->create();

        $response = $this->actingAs($member, 'sanctum')
            ->postJson('/api/v1/shop-invitations/send', [
                'shop_id' => $shop->id,
                'email' => $invitedUser->email,
                'role_id' => $role->id,
            ]);

        $response->assertStatus(201);

        Notification::assertSentTo($invitedUser, ShopInvitationNotification::class);
    }

    public function test_cannot_send_invitation_to_inaccessible_shop(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $shop = Shop::factory()->create(['owner_id' => $otherUser->id]);
        $role = Role::factory()->create(['shop_id' => $shop->id]);
        $invitedUser = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/shop-invitations/send', [
                'shop_id' => $shop->id,
                'email' => $invitedUser->email,
                'role_id' => $role->id,
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Shop not found or you do not have access to it.',
            ]);
    }

    public function test_cannot_send_invitation_with_role_from_different_shop(): void
    {
        $owner = User::factory()->create();
        $shop = Shop::factory()->create(['owner_id' => $owner->id]);
        $otherShop = Shop::factory()->create(['owner_id' => $owner->id]);
        $roleFromOtherShop = Role::factory()->create(['shop_id' => $otherShop->id]);
        $invitedUser = User::factory()->create();

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/shop-invitations/send', [
                'shop_id' => $shop->id,
                'email' => $invitedUser->email,
                'role_id' => $roleFromOtherShop->id,
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Role not found or does not belong to this shop.',
            ]);
    }

    public function test_cannot_send_invitation_to_existing_active_member(): void
    {
        $owner = User::factory()->create();
        $shop = Shop::factory()->create(['owner_id' => $owner->id]);
        $role = Role::factory()->create(['shop_id' => $shop->id]);
        $existingMember = User::factory()->create();

        ShopUser::create([
            'shop_id' => $shop->id,
            'user_id' => $existingMember->id,
            'role_id' => $role->id,
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/shop-invitations/send', [
                'shop_id' => $shop->id,
                'email' => $existingMember->email,
                'role_id' => $role->id,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'User is already a member of this shop.',
            ]);
    }

    public function test_cannot_send_duplicate_pending_invitation(): void
    {
        $owner = User::factory()->create();
        $shop = Shop::factory()->create(['owner_id' => $owner->id]);
        $role = Role::factory()->create(['shop_id' => $shop->id]);
        $invitedUser = User::factory()->create();

        // Create pending invitation
        ShopUser::create([
            'shop_id' => $shop->id,
            'user_id' => $invitedUser->id,
            'role_id' => $role->id,
            'is_active' => false,
            'invited_by' => $owner->id,
            'invitation_token' => 'test-token',
            'invitation_expires_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/shop-invitations/send', [
                'shop_id' => $shop->id,
                'email' => $invitedUser->email,
                'role_id' => $role->id,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'A pending invitation already exists for this user.',
            ]);
    }

    public function test_user_can_accept_invitation(): void
    {
        $owner = User::factory()->create();
        $shop = Shop::factory()->create(['owner_id' => $owner->id]);
        $role = Role::factory()->create(['shop_id' => $shop->id]);
        $invitedUser = User::factory()->create();

        $invitation = ShopUser::create([
            'shop_id' => $shop->id,
            'user_id' => $invitedUser->id,
            'role_id' => $role->id,
            'is_active' => false,
            'invited_by' => $owner->id,
            'invitation_token' => 'test-token',
            'invitation_expires_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($invitedUser, 'sanctum')
            ->postJson('/api/v1/shop-invitations/test-token/accept');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Invitation accepted successfully.',
            ]);

        $invitation->refresh();
        $this->assertTrue($invitation->is_active);
        $this->assertNotNull($invitation->invitation_accepted_at);
        $this->assertNotNull($invitation->joined_at);
        $this->assertNull($invitation->invitation_token);
    }

    public function test_cannot_accept_expired_invitation(): void
    {
        $owner = User::factory()->create();
        $shop = Shop::factory()->create(['owner_id' => $owner->id]);
        $role = Role::factory()->create(['shop_id' => $shop->id]);
        $invitedUser = User::factory()->create();

        ShopUser::create([
            'shop_id' => $shop->id,
            'user_id' => $invitedUser->id,
            'role_id' => $role->id,
            'is_active' => false,
            'invited_by' => $owner->id,
            'invitation_token' => 'expired-token',
            'invitation_expires_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($invitedUser, 'sanctum')
            ->postJson('/api/v1/shop-invitations/expired-token/accept');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Invalid or expired invitation.',
            ]);
    }

    public function test_user_can_decline_invitation(): void
    {
        $owner = User::factory()->create();
        $shop = Shop::factory()->create(['owner_id' => $owner->id]);
        $role = Role::factory()->create(['shop_id' => $shop->id]);
        $invitedUser = User::factory()->create();

        ShopUser::create([
            'shop_id' => $shop->id,
            'user_id' => $invitedUser->id,
            'role_id' => $role->id,
            'is_active' => false,
            'invited_by' => $owner->id,
            'invitation_token' => 'test-token',
            'invitation_expires_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($invitedUser, 'sanctum')
            ->postJson('/api/v1/shop-invitations/test-token/decline');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Invitation declined successfully.',
            ]);

        $this->assertDatabaseMissing('shop_users', [
            'invitation_token' => 'test-token',
        ]);
    }

    public function test_user_can_list_pending_invitations(): void
    {
        $owner = User::factory()->create();
        $shop1 = Shop::factory()->create(['owner_id' => $owner->id]);
        $shop2 = Shop::factory()->create(['owner_id' => $owner->id]);
        $role1 = Role::factory()->create(['shop_id' => $shop1->id]);
        $role2 = Role::factory()->create(['shop_id' => $shop2->id]);
        $invitedUser = User::factory()->create();

        ShopUser::create([
            'shop_id' => $shop1->id,
            'user_id' => $invitedUser->id,
            'role_id' => $role1->id,
            'is_active' => false,
            'invited_by' => $owner->id,
            'invitation_token' => 'token1',
            'invitation_expires_at' => now()->addDays(7),
        ]);

        ShopUser::create([
            'shop_id' => $shop2->id,
            'user_id' => $invitedUser->id,
            'role_id' => $role2->id,
            'is_active' => false,
            'invited_by' => $owner->id,
            'invitation_token' => 'token2',
            'invitation_expires_at' => now()->addDays(5),
        ]);

        $response = $this->actingAs($invitedUser, 'sanctum')
            ->getJson('/api/v1/shop-invitations/pending');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_shop_owner_can_cancel_pending_invitation(): void
    {
        $owner = User::factory()->create();
        $shop = Shop::factory()->create(['owner_id' => $owner->id]);
        $role = Role::factory()->create(['shop_id' => $shop->id]);
        $invitedUser = User::factory()->create();

        ShopUser::create([
            'shop_id' => $shop->id,
            'user_id' => $invitedUser->id,
            'role_id' => $role->id,
            'is_active' => false,
            'invited_by' => $owner->id,
            'invitation_token' => 'test-token',
            'invitation_expires_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($owner, 'sanctum')
            ->deleteJson("/api/v1/shop-invitations/{$shop->id}/{$invitedUser->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Invitation cancelled successfully.',
            ]);

        $this->assertDatabaseMissing('shop_users', [
            'shop_id' => $shop->id,
            'user_id' => $invitedUser->id,
        ]);
    }

    public function test_non_owner_cannot_cancel_invitation(): void
    {
        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        $shop = Shop::factory()->create(['owner_id' => $owner->id]);
        $role = Role::factory()->create(['shop_id' => $shop->id]);
        $invitedUser = User::factory()->create();

        ShopUser::create([
            'shop_id' => $shop->id,
            'user_id' => $invitedUser->id,
            'role_id' => $role->id,
            'is_active' => false,
            'invited_by' => $owner->id,
            'invitation_token' => 'test-token',
            'invitation_expires_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($nonOwner, 'sanctum')
            ->deleteJson("/api/v1/shop-invitations/{$shop->id}/{$invitedUser->id}");

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Shop not found or you do not have permission to cancel invitations.',
            ]);
    }

    public function test_invitation_requires_valid_email(): void
    {
        $owner = User::factory()->create();
        $shop = Shop::factory()->create(['owner_id' => $owner->id]);
        $role = Role::factory()->create(['shop_id' => $shop->id]);

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/shop-invitations/send', [
                'shop_id' => $shop->id,
                'email' => 'invalid-email',
                'role_id' => $role->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_unauthenticated_user_cannot_access_invitations(): void
    {
        $response = $this->getJson('/api/v1/shop-invitations/pending');

        $response->assertStatus(401);
    }
}
