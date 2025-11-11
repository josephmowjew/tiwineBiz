<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_authenticated_user_can_get_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/profile');

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

    public function test_unauthenticated_user_cannot_access_profile(): void
    {
        $response = $this->getJson('/api/v1/profile');

        $response->assertStatus(401);
    }

    public function test_user_can_update_profile_name(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile', [
                'name' => 'New Name',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Profile updated successfully.',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
        ]);
    }

    public function test_user_can_update_profile_email(): void
    {
        $user = User::factory()->create([
            'email' => 'old@example.com',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile', [
                'email' => 'new@example.com',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'new@example.com',
        ]);
    }

    public function test_user_cannot_update_email_to_existing_email(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile', [
                'email' => 'existing@example.com',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_update_preferred_language(): void
    {
        $user = User::factory()->create([
            'preferred_language' => 'en',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile', [
                'preferred_language' => 'ny',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'preferred_language' => 'ny',
        ]);
    }

    public function test_user_can_update_timezone(): void
    {
        $user = User::factory()->create([
            'timezone' => 'Africa/Blantyre',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile', [
                'timezone' => 'America/New_York',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'timezone' => 'America/New_York',
        ]);
    }

    public function test_user_can_upload_profile_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $file = UploadedFile::fake()->image('profile.jpg');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/profile/photo', [
                'photo' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Profile photo uploaded successfully.',
            ]);

        $user->refresh();
        $this->assertNotNull($user->profile_photo_url);
        $this->assertStringContainsString('/storage/profile-photos/', $user->profile_photo_url);

        // Verify file was stored
        $photoPath = str_replace('/storage/', '', $user->profile_photo_url);
        Storage::disk('public')->assertExists($photoPath);
    }

    public function test_uploading_new_photo_deletes_old_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'profile_photo_url' => '/storage/profile-photos/old-photo.jpg',
        ]);

        // Create old photo file
        Storage::disk('public')->put('profile-photos/old-photo.jpg', 'old content');

        $newFile = UploadedFile::fake()->image('new-profile.jpg');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/profile/photo', [
                'photo' => $newFile,
            ]);

        $response->assertStatus(200);

        // Old photo should be deleted
        Storage::disk('public')->assertMissing('profile-photos/old-photo.jpg');

        // New photo should exist
        $user->refresh();
        $newPhotoPath = str_replace('/storage/', '', $user->profile_photo_url);
        Storage::disk('public')->assertExists($newPhotoPath);
    }

    public function test_photo_upload_requires_valid_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/profile/photo', [
                'photo' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['photo']);
    }

    public function test_photo_upload_validates_file_size(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        // Create file larger than 2MB
        $file = UploadedFile::fake()->create('huge-image.jpg', 3000);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/profile/photo', [
                'photo' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['photo']);
    }

    public function test_user_can_delete_profile_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'profile_photo_url' => '/storage/profile-photos/photo.jpg',
        ]);

        // Create the photo file
        Storage::disk('public')->put('profile-photos/photo.jpg', 'content');

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/profile/photo');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Profile photo deleted successfully.',
            ]);

        // Photo should be deleted from storage
        Storage::disk('public')->assertMissing('profile-photos/photo.jpg');

        // User's profile_photo_url should be null
        $user->refresh();
        $this->assertNull($user->profile_photo_url);
    }

    public function test_deleting_photo_when_no_photo_exists_returns_404(): void
    {
        $user = User::factory()->create([
            'profile_photo_url' => null,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/profile/photo');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'No profile photo to delete.',
            ]);
    }

    public function test_profile_update_validates_invalid_language(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile', [
                'preferred_language' => 'invalid',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['preferred_language']);
    }

    public function test_profile_update_validates_invalid_timezone(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile', [
                'timezone' => 'Invalid/Timezone',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['timezone']);
    }
}
