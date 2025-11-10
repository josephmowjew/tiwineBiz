<?php

namespace App\Repositories;

use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository implements BaseRepositoryInterface
{
    protected Model $model;

    protected ?string $deviceType = null;

    /**
     * BaseRepository constructor.
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->detectDevice();
    }

    /**
     * Detect device type from request.
     */
    protected function detectDevice(): void
    {
        if (! config('pagination.detection.enabled')) {
            $this->deviceType = 'web';

            return;
        }

        $userAgent = request()->header(config('pagination.detection.header', 'User-Agent'), '');

        // Check for tablet first (more specific)
        foreach (config('pagination.detection.tablet_patterns', []) as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                $this->deviceType = 'tablet';

                return;
            }
        }

        // Check for mobile
        foreach (config('pagination.detection.mobile_patterns', []) as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                $this->deviceType = 'mobile';

                return;
            }
        }

        $this->deviceType = 'web';
    }

    /**
     * Get device type.
     */
    public function getDeviceType(): string
    {
        return $this->deviceType ?? 'web';
    }

    /**
     * Get query builder instance.
     */
    protected function query(): Builder
    {
        return $this->model->newQuery();
    }

    /**
     * Apply filters to query.
     */
    protected function applyFilters(Builder $query, array $filters): Builder
    {
        // This method should be overridden in child repositories
        // for model-specific filtering logic
        return $query;
    }

    /**
     * Get pagination configuration for current device.
     */
    protected function getPaginationConfig(): array
    {
        $deviceType = $this->getDeviceType();

        return config("pagination.{$deviceType}", config('pagination.web'));
    }

    /**
     * Get per page value with device-specific defaults.
     */
    protected function getPerPage(?int $perPage = null): int
    {
        $config = $this->getPaginationConfig();

        if ($perPage === null) {
            return $config['default_per_page'];
        }

        // Enforce max per page limit
        return min($perPage, $config['max_per_page']);
    }

    /**
     * {@inheritdoc}
     */
    public function all(array $filters = []): Collection
    {
        $query = $this->query();
        $query = $this->applyFilters($query, $filters);

        return $query->get();
    }

    /**
     * {@inheritdoc}
     */
    public function paginate(?int $perPage = null, array $filters = []): LengthAwarePaginator
    {
        $query = $this->query();
        $query = $this->applyFilters($query, $filters);

        return $query->paginate($this->getPerPage($perPage));
    }

    /**
     * {@inheritdoc}
     */
    public function cursorPaginate(?int $perPage = null, array $filters = []): CursorPaginator
    {
        $query = $this->query();
        $query = $this->applyFilters($query, $filters);

        return $query->cursorPaginate($this->getPerPage($perPage));
    }

    /**
     * {@inheritdoc}
     */
    public function autoPaginate(?int $perPage = null, array $filters = []): LengthAwarePaginator|CursorPaginator
    {
        $config = $this->getPaginationConfig();

        if ($config['use_cursor']) {
            return $this->cursorPaginate($perPage, $filters);
        }

        return $this->paginate($perPage, $filters);
    }

    /**
     * {@inheritdoc}
     */
    public function find(string $id): ?Model
    {
        return $this->query()->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function findOrFail(string $id): Model
    {
        return $this->query()->findOrFail($id);
    }

    /**
     * {@inheritdoc}
     */
    public function findBy(string $column, mixed $value): Collection
    {
        return $this->query()->where($column, $value)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function findFirstBy(string $column, mixed $value): ?Model
    {
        return $this->query()->where($column, $value)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function update(string $id, array $data): Model
    {
        $model = $this->findOrFail($id);
        $model->update($data);

        return $model;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $id): bool
    {
        $model = $this->findOrFail($id);

        return $model->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function count(array $filters = []): int
    {
        $query = $this->query();
        $query = $this->applyFilters($query, $filters);

        return $query->count();
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $id): bool
    {
        return $this->query()->where($this->model->getKeyName(), $id)->exists();
    }
}
