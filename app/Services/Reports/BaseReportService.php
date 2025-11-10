<?php

namespace App\Services\Reports;

use App\Traits\HasBranchScope;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

abstract class BaseReportService
{
    use HasBranchScope;

    /**
     * Cache duration in minutes for reports.
     */
    protected int $cacheDuration = 30;

    /**
     * Get accessible branch IDs for the current user.
     */
    protected function getAccessibleBranches(?string $shopId = null): Collection
    {
        return $this->getAccessibleBranchIds($shopId);
    }

    /**
     * Parse date range from request parameters.
     */
    protected function parseDateRange(?string $startDate = null, ?string $endDate = null): array
    {
        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::today()->startOfDay();
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::today()->endOfDay();

        return [$start, $end];
    }

    /**
     * Get cache key for a report.
     */
    protected function getCacheKey(string $reportType, array $params = []): string
    {
        $user = auth()->user();
        $userId = $user ? $user->id : 'guest';

        ksort($params);
        $paramsString = http_build_query($params);

        return "report:{$reportType}:{$userId}:{$paramsString}";
    }

    /**
     * Cache a report result.
     */
    protected function cacheReport(string $key, callable $callback): mixed
    {
        return Cache::remember($key, $this->cacheDuration * 60, $callback);
    }

    /**
     * Format currency value.
     */
    protected function formatCurrency(float $amount): float
    {
        return round($amount, 2);
    }

    /**
     * Calculate percentage change.
     */
    protected function calculatePercentageChange(float $current, float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * Filter query by branch IDs if provided.
     */
    protected function applyBranchFilter($query, ?string $branchId = null)
    {
        if ($branchId) {
            $accessibleBranchIds = $this->getAccessibleBranches();

            // Verify the requested branch is accessible
            if ($accessibleBranchIds->contains($branchId)) {
                return $query->where('branch_id', $branchId);
            }

            // If not accessible, return empty result
            return $query->whereRaw('1 = 0');
        }

        // If no specific branch, filter by all accessible branches
        $branchIds = $this->getAccessibleBranches();

        if ($branchIds->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('branch_id', $branchIds);
    }

    /**
     * Get period comparison dates.
     */
    protected function getComparisonDates(Carbon $start, Carbon $end): array
    {
        $diff = $start->diffInDays($end);

        $previousStart = $start->copy()->subDays($diff + 1);
        $previousEnd = $end->copy()->subDays($diff + 1);

        return [$previousStart, $previousEnd];
    }

    /**
     * Format report data for API response.
     */
    protected function formatReportData(array $data): array
    {
        return [
            'success' => true,
            'data' => $data,
            'generated_at' => now()->toIso8601String(),
        ];
    }
}
