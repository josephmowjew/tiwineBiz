<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Filterable
{
    /**
     * Apply equality filter.
     */
    protected function filterEqual(Builder $query, string $column, mixed $value): Builder
    {
        return $query->where($column, $value);
    }

    /**
     * Apply LIKE filter.
     */
    protected function filterLike(Builder $query, string $column, string $value): Builder
    {
        return $query->where($column, 'like', "%{$value}%");
    }

    /**
     * Apply IN filter.
     */
    protected function filterIn(Builder $query, string $column, array $values): Builder
    {
        return $query->whereIn($column, $values);
    }

    /**
     * Apply date range filter.
     */
    protected function filterDateRange(Builder $query, string $column, ?string $fromDate, ?string $toDate): Builder
    {
        if ($fromDate) {
            $query->whereDate($column, '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate($column, '<=', $toDate);
        }

        return $query;
    }

    /**
     * Apply date between filter.
     */
    protected function filterDateBetween(Builder $query, string $column, string $start, string $end): Builder
    {
        return $query->whereBetween($column, [$start, $end]);
    }

    /**
     * Apply greater than filter.
     */
    protected function filterGreaterThan(Builder $query, string $column, mixed $value): Builder
    {
        return $query->where($column, '>', $value);
    }

    /**
     * Apply less than filter.
     */
    protected function filterLessThan(Builder $query, string $column, mixed $value): Builder
    {
        return $query->where($column, '<', $value);
    }

    /**
     * Apply boolean filter.
     */
    protected function filterBoolean(Builder $query, string $column, bool $value): Builder
    {
        return $query->where($column, $value);
    }

    /**
     * Apply null/not null filter.
     */
    protected function filterNull(Builder $query, string $column, bool $isNull = true): Builder
    {
        return $isNull ? $query->whereNull($column) : $query->whereNotNull($column);
    }

    /**
     * Apply custom where clause filter.
     */
    protected function filterCustom(Builder $query, callable $callback): Builder
    {
        return $callback($query);
    }

    /**
     * Apply sorting.
     */
    protected function applySort(Builder $query, string $column, string $direction = 'asc'): Builder
    {
        return $query->orderBy($column, $direction);
    }

    /**
     * Apply multiple sorts.
     */
    protected function applySorts(Builder $query, array $sorts): Builder
    {
        foreach ($sorts as $column => $direction) {
            $query->orderBy($column, $direction);
        }

        return $query;
    }

    /**
     * Apply eager loading.
     */
    protected function applyWith(Builder $query, array $relations): Builder
    {
        return $query->with($relations);
    }
}
