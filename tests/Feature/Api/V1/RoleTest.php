<?php

namespace Tests\Feature\Api\V1;

use App\Models\Role;
use App\Models\Shop;
use App\Models\ShopUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_authenticated_user_can_list_roles_from_accessible_shops(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create(['owner_id' => $user->id]);
        $role1 = Role::factory()->create(['shop_id' => $shop->id]);
        $role2 = Role::factory()->create(['shop_id' => $shop->id]);

        // Create role from another shop (should not appear)
        $otherUser = User::factory()->create();
        $otherShop = Shop::factory()->create(['owner_id' => $otherUser->id]);
        Role::factory()->create(['shop_id' => $otherShop->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/roles');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'shop_id',
                        'name',
                        'display_name',
                        'description',
                        'is_system_role',
                        'permissions',
                    ],
                ],
                'meta',
            ]);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_user_can_filter_roles_by_shop_id(): void
    {
        $user = User::factory()->create();
        $shop1 = Shop::factory()->create(['owner_id' => $user->id]);
        $shop2 = Shop::factory()->create(['owner_id' => $user->id]);

        Role::factory()->create(['shop_id' => $shop1->id]);
        Role::factory()->create(['shop_id' => $shop2->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/roles?shop_id={$shop1->id}");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($shop1->id, $response->json('data.0.shop_id'));
    }

    public function test_user_can_search_roles_by_name(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create(['owner_id' => $user->id]);

        Role::factory()->create([
            'shop_id' => $shop->id,
            'name' => 'cashier',
            'display_name' => 'Cashier',
        ]);

        Role::factory()->create([
            'shop_id' => $shop->id,
            'name' => 'manager',
            'display_name' => 'Manager',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/roles?search=cashier');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('cashier', $response->json('data.0.name'));
    }

    public function test_authenticated_user_can_create_role(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create(['owner_id' => $user->id]);

        $roleData = [
            'shop_id' => $shop->id,
            'name' => 'cashier',
            'display_name' => 'Cashier',
            'description' => 'Handles sales and customer transactions',
            'permissions' => ['view_products', 'create_sales', 'process_payments'],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/roles', $roleData);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Role created successfully.',
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'shop_id',
                    'name',
                    'display_name',
                    'permissions',
                ],
            ]);

        $this->assertDatabaseHas('roles', [
            'shop_id' => $shop->id,
            'name' => 'cashier',
            'display_name' => 'Cashier',
            'is_system_role' => false,
        ]);
    }

    public function test_cannot_create_role_with_duplicate_name_in_same_shop(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create(['owner_id' => $user->id]);

        Role::factory()->create([
            'shop_id' => $shop->id,
            'name' => 'cashier',
        ]);

        $roleData = [
            'shop_id' => $shop->id,
            'name' => 'cashier',
            'display_name' => 'Cashier',
            'permissions' => ['view_products'],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/roles', $roleData);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'A role with this name already exists in this shop.',
            ]);
    }

    public function test_can_create_role_with_same_name_in_different_shop(): void
    {
        $user = User::factory()->create();
        $shop1 = Shop::factory()->create(['owner_id' => $user->id]);
        $shop2 = Shop::factory()->create(['owner_id' => $user->id]);

        Role::factory()->create([
            'shop_id' => $shop1->id,
            'name' => 'cashier',
        ]);

        $roleData = [
            'shop_id' => $shop2->id,
            'name' => 'cashier',
            'display_name' => 'Cashier',
            'permissions' => ['view_products'],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/roles', $roleData);

        $response->assertStatus(201);
    }

    public function test_cannot_create_role_for_inaccessible_shop(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherShop = Shop::factory()->create(['owner_id' => $otherUser->id]);

        $roleData = [
            'shop_id' => $otherShop->id,
            'name' => 'cashier',
            'display_name' => 'Cashier',
            'permissions' => ['view_products'],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/roles', $roleData);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Shop not found or you do not have access to it.',
            ]);
    }

    public function test_role_creation_validates_required_fields(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create(['owner_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/roles', [
                'shop_id' => $shop->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'display_name', 'permissions']);
    }

    public function test_role_name_must_be_lowercase_alphanumeric_with_underscores(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create(['owner_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/roles', [
                'shop_id' => $shop->id,
                'name' => 'Invalid Role Name!',
                'display_name' => 'Display Name',
                'permissions' => ['view_products'],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_role_must_have_at_least_one_permission(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create(['owner_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/roles', [
                'shop_id' => $shop->id,
                'name' => 'test_role',
                'display_name' => 'Test Role',
                'permissions' => [],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['permissions']);
    }

    public function test_authenticated_user_can_view_single_role(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create(['owner_id' => $user->id]);
        $role = Role::factory()->create(['shop_id' => $shop->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/roles/{$role->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'shop_id',
                    'name',
                    'display_name',
                    'permissions',
                ],
            ]);
    }

    public function test_cannot_view_role_from_inaccessible_shop(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherShop = Shop::factory()->create(['owner_id' => $otherUser->id]);
        $role = Role::factory()->create(['shop_id' => $otherShop->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/roles/{$role->id}");

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Role not found or you do not have access to it.',
            ]);
    }

    public function test_authenticated_user_can_update_role(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create(['owner_id' => $user->id]);
        $role = Role::factory()->create([
            'shop_id' => $shop->id,
            'name' => 'old_name',
            'display_name' => 'Old Name',
        ]);

        $updateData = [
            'name' => 'new_name',
            'display_name' => 'New Name',
            'permissions' => ['new_permission'],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/roles/{$role->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Role updated successfully.',
            ]);

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => 'new_name',
            'display_name' => 'New Name',
        ]);
    }

    public function test_cannot_update_system_role(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create(['owner_id' => $user->id]);
        $role = Role::factory()->system()->create(['shop_id' => $shop->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/roles/{$role->id}", [
                'name' => 'new_name',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'System roles cannot be modified.',
            ]);
    }

    public function test_cannot_update_role_name_to_duplicate_in_same_shop(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create(['owner_id' => $user->id]);

        $role1 = Role::factory()->create([
            'shop_id' => $shop->id,
            'name' => 'cashier',
        ]);

        $role2 = Role::factory()->create([
            'shop_id' => $shop->id,
            'name' => 'manager',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/roles/{$role2->id}", [
                'name' => 'cashier',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'A role with this name already exists in this shop.',
            ]);
    }

    public function test_cannot_update_inaccessible_role(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherShop = Shop::factory()->create(['owner_id' => $otherUser->id]);
        $role = Role::factory()->create(['shop_id' => $otherShop->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/roles/{$role->id}", [
                'name' => 'new_name',
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Role not found or you do not have access to it.',
            ]);
    }

    public function test_authenticated_user_can_delete_role(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create(['owner_id' => $user->id]);
        $role = Role::factory()->create(['shop_id' => $shop->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/roles/{$role->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Role deleted successfully.',
            ]);

        $this->assertDatabaseMissing('roles', [
            'id' => $role->id,
        ]);
    }

    public function test_cannot_delete_system_role(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create(['owner_id' => $user->id]);
        $role = Role::factory()->system()->create(['shop_id' => $shop->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/roles/{$role->id}");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'System roles cannot be deleted.',
            ]);
    }

    public function test_cannot_delete_role_assigned_to_users(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create(['owner_id' => $user->id]);
        $role = Role::factory()->create(['shop_id' => $shop->id]);

        // Assign role to a user
        $assignedUser = User::factory()->create();
        ShopUser::create([
            'shop_id' => $shop->id,
            'user_id' => $assignedUser->id,
            'role_id' => $role->id,
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/roles/{$role->id}");

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Cannot delete role that is assigned to users.',
            ]);

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
        ]);
    }

    public function test_cannot_delete_inaccessible_role(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherShop = Shop::factory()->create(['owner_id' => $otherUser->id]);
        $role = Role::factory()->create(['shop_id' => $otherShop->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/roles/{$role->id}");

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Role not found or you do not have access to it.',
            ]);
    }

    public function test_unauthenticated_user_cannot_access_roles(): void
    {
        $response = $this->getJson('/api/v1/roles');

        $response->assertStatus(401);
    }
}
