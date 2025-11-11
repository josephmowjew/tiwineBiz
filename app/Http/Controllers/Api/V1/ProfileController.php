<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Profile\UpdateProfileRequest;
use App\Http\Requests\Api\V1\Profile\UploadPhotoRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Get authenticated user's profile.
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Load relationships if requested
            if ($request->has('include')) {
                $includes = explode(',', $request->input('include'));
                $allowedIncludes = ['ownedShops', 'shops', 'branches'];
                $validIncludes = array_intersect($includes, $allowedIncludes);

                if (! empty($validIncludes)) {
                    $user->load($validIncludes);
                }
            }

            return response()->json([
                'user' => new UserResource($user),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve profile.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update authenticated user's profile.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Update user profile
            $user->update($request->validated());

            return response()->json([
                'message' => 'Profile updated successfully.',
                'user' => new UserResource($user->fresh()),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update profile.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload user profile photo.
     */
    public function uploadPhoto(UploadPhotoRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Delete old photo if exists
            if ($user->profile_photo_url) {
                $oldPhotoPath = str_replace('/storage/', '', $user->profile_photo_url);
                if (Storage::disk('public')->exists($oldPhotoPath)) {
                    Storage::disk('public')->delete($oldPhotoPath);
                }
            }

            // Store new photo
            $path = $request->file('photo')->store('profile-photos', 'public');

            // Update user profile photo URL
            $user->update([
                'profile_photo_url' => '/storage/'.$path,
            ]);

            return response()->json([
                'message' => 'Profile photo uploaded successfully.',
                'user' => new UserResource($user->fresh()),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to upload profile photo.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete user profile photo.
     */
    public function deletePhoto(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Delete photo if exists
            if ($user->profile_photo_url) {
                $photoPath = str_replace('/storage/', '', $user->profile_photo_url);
                if (Storage::disk('public')->exists($photoPath)) {
                    Storage::disk('public')->delete($photoPath);
                }

                // Update user to remove photo URL
                $user->update([
                    'profile_photo_url' => null,
                ]);

                return response()->json([
                    'message' => 'Profile photo deleted successfully.',
                    'user' => new UserResource($user->fresh()),
                ], 200);
            }

            return response()->json([
                'message' => 'No profile photo to delete.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete profile photo.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
