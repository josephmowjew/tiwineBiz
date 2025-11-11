<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_user_can_register_with_valid_data(): void
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+265991234567',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ];

        $response = $this->postJson('/api/v1/auth/register', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                ],
                'token',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'phone' => '+265991234567',
        ]);
    }

    public function test_user_cannot_register_with_invalid_phone(): void
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '12345',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ];

        $response = $this->postJson('/api/v1/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_user_can_login_with_email(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password_hash' => bcrypt('Password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'john@example.com',
            'password' => 'Password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'user',
                'token',
            ]);
    }

    public function test_user_can_login_with_phone(): void
    {
        $user = User::factory()->create([
            'phone' => '+265991234567',
            'password_hash' => bcrypt('Password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => '+265991234567',
            'password' => 'Password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'user',
                'token',
            ]);
    }

    public function test_user_cannot_login_with_wrong_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password_hash' => bcrypt('Password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'john@example.com',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['login']);
    }

    public function test_authenticated_user_can_get_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/user');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                ],
            ]);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Logout successful.',
            ]);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password_hash' => bcrypt('Password123'),
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'john@example.com',
            'password' => 'Password123',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Your account has been deactivated. Please contact support.',
            ]);
    }

    public function test_user_can_request_password_reset(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'john@example.com',
        ]);

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Password reset link sent to your email address.',
            ]);
    }

    public function test_forgot_password_fails_with_invalid_email(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_forgot_password_requires_email(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_reset_password_with_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password_hash' => Hash::make('OldPassword123'),
        ]);

        $token = Password::createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'john@example.com',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Password reset successful. Please login with your new password.',
            ]);

        $user->refresh();
        $this->assertTrue(Hash::check('NewPassword123', $user->password_hash));
    }

    public function test_password_reset_fails_with_invalid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
        ]);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'invalid-token',
            'email' => 'john@example.com',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Invalid or expired reset token. Please request a new one.',
            ]);
    }

    public function test_password_reset_requires_password_confirmation(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
        ]);

        $token = Password::createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'john@example.com',
            'password' => 'NewPassword123',
            'password_confirmation' => 'DifferentPassword123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_password_reset_revokes_existing_tokens(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password_hash' => Hash::make('OldPassword123'),
        ]);

        // Create a token
        $oldToken = $user->createToken('auth-token')->plainTextToken;

        // Reset password
        $resetToken = Password::createToken($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $resetToken,
            'email' => 'john@example.com',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        // Verify old token is revoked
        $this->assertEquals(0, $user->tokens()->count());
    }

    public function test_authenticated_user_can_change_password(): void
    {
        $user = User::factory()->create([
            'password_hash' => Hash::make('OldPassword123'),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/auth/change-password', [
                'current_password' => 'OldPassword123',
                'password' => 'NewPassword123',
                'password_confirmation' => 'NewPassword123',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Password changed successfully.',
            ]);

        $user->refresh();
        $this->assertTrue(Hash::check('NewPassword123', $user->password_hash));
    }

    public function test_change_password_fails_with_wrong_current_password(): void
    {
        $user = User::factory()->create([
            'password_hash' => Hash::make('OldPassword123'),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/auth/change-password', [
                'current_password' => 'WrongPassword',
                'password' => 'NewPassword123',
                'password_confirmation' => 'NewPassword123',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    }

    public function test_change_password_requires_authentication(): void
    {
        $response = $this->putJson('/api/v1/auth/change-password', [
            'current_password' => 'OldPassword123',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $response->assertStatus(401);
    }

    public function test_change_password_requires_confirmation(): void
    {
        $user = User::factory()->create([
            'password_hash' => Hash::make('OldPassword123'),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/auth/change-password', [
                'current_password' => 'OldPassword123',
                'password' => 'NewPassword123',
                'password_confirmation' => 'DifferentPassword123',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_change_password_requires_new_password_to_be_different(): void
    {
        $user = User::factory()->create([
            'password_hash' => Hash::make('OldPassword123'),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/auth/change-password', [
                'current_password' => 'OldPassword123',
                'password' => 'OldPassword123',
                'password_confirmation' => 'OldPassword123',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_change_password_revokes_other_device_tokens(): void
    {
        $user = User::factory()->create([
            'password_hash' => Hash::make('OldPassword123'),
        ]);

        // Create multiple tokens (simulating multiple devices)
        $token1 = $user->createToken('device1')->plainTextToken;
        $token2 = $user->createToken('device2')->plainTextToken;
        $currentToken = $user->createToken('current-device')->plainTextToken;

        // Initially should have 3 tokens
        $this->assertEquals(3, $user->tokens()->count());

        // Change password using current token
        $response = $this->withHeader('Authorization', 'Bearer '.$currentToken)
            ->putJson('/api/v1/auth/change-password', [
                'current_password' => 'OldPassword123',
                'password' => 'NewPassword123',
                'password_confirmation' => 'NewPassword123',
            ]);

        $response->assertStatus(200);

        // Should only have 1 token left (current device)
        $user->refresh();
        $this->assertEquals(1, $user->tokens()->count());
    }
}
