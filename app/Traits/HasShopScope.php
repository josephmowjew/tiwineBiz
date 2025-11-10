<?php

namespace App\Traits;

use App\Models\Shop;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

trait HasShopScope
{
    /**
     * Get shop IDs accessible by the authenticated user.
     */
    protected function getAccessibleShopIds(): Collection
    {
        $user = auth()->user();

        if (! $user) {
            return collect();
        }

        return Shop::where('owner_id', $user->id)
            ->orWhereHas('users', fn ($q) => $q->where('user_id', $user->id))
            ->pluck('id');
    }

    /**
     * Apply shop scope to query.
     */
    protected function applyShopScope(Builder $query): Builder
    {
        $shopIds = $this->getAccessibleShopIds();

        if ($shopIds->isEmpty()) {
            // If no accessible shops, return empty result
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('shop_id', $shopIds);
    }

    /**
     * Verify that a shop belongs to the authenticated user.
     */
    protected function verifyShopAccess(string $shopId): Shop
    {
        $user = request()->user();

        return Shop::where('id', $shopId)
            ->where(fn ($q) => $q->where('owner_id', $user->id)
                ->orWhereHas('users', fn ($q) => $q->where('user_id', $user->id)))
            ->firstOrFail();
    }

    /**
     * Verify that a model belongs to user's accessible shops.
     */
    protected function verifyModelShopAccess($model): void
    {
        if (! property_exists($model, 'shop_id') && ! isset($model->shop_id)) {
            return;
        }

        $this->verifyShopAccess($model->shop_id);
    }
}
