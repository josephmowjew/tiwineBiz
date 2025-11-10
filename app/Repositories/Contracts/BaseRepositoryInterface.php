<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface BaseRepositoryInterface
{
    /**
     * Get all records with optional filtering.
     */
    public function all(array $filters = []): Collection;

    /**
     * Get paginated records using offset pagination (for web).
     */
    public function paginate(?int $perPage = null, array $filters = []): LengthAwarePaginator;

    /**
     * Get paginated records using cursor pagination (for mobile).
     */
    public function cursorPaginate(?int $perPage = null, array $filters = []): CursorPaginator;

    /**
     * Auto-detect device and use appropriate pagination.
     */
    public function autoPaginate(?int $perPage = null, array $filters = []): LengthAwarePaginator|CursorPaginator;

    /**
     * Find a record by ID.
     */
    public function find(string $id): ?Model;

    /**
     * Find a record by ID or fail.
     */
    public function findOrFail(string $id): Model;

    /**
     * Find records by a specific column value.
     */
    public function findBy(string $column, mixed $value): Collection;

    /**
     * Find first record by a specific column value.
     */
    public function findFirstBy(string $column, mixed $value): ?Model;

    /**
     * Create a new record.
     */
    public function create(array $data): Model;

    /**
     * Update a record by ID.
     */
    public function update(string $id, array $data): Model;

    /**
     * Delete a record by ID.
     */
    public function delete(string $id): bool;

    /**
     * Count total records with optional filtering.
     */
    public function count(array $filters = []): int;

    /**
     * Check if a record exists.
     */
    public function exists(string $id): bool;
}
