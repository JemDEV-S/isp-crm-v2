<?php

declare(strict_types=1);

namespace Modules\Core\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseRepository
{
    /**
     * The model instance.
     */
    protected Model $model;

    /**
     * Get the model instance.
     */
    abstract protected function model(): Model;

    /**
     * BaseRepository constructor.
     */
    public function __construct()
    {
        $this->model = $this->model();
    }

    /**
     * Get a new query builder instance.
     */
    protected function query(): Builder
    {
        return $this->model->newQuery();
    }

    /**
     * Find a record by ID.
     *
     * @throws ModelNotFoundException
     */
    public function findOrFail(int $id): Model
    {
        return $this->query()->findOrFail($id);
    }

    /**
     * Find a record by ID.
     */
    public function find(int $id): ?Model
    {
        return $this->query()->find($id);
    }

    /**
     * Find a record by UUID.
     *
     * @throws ModelNotFoundException
     */
    public function findByUuidOrFail(string $uuid): Model
    {
        return $this->query()->where('uuid', $uuid)->firstOrFail();
    }

    /**
     * Find a record by UUID.
     */
    public function findByUuid(string $uuid): ?Model
    {
        return $this->query()->where('uuid', $uuid)->first();
    }

    /**
     * Get all records.
     */
    public function all(): Collection
    {
        return $this->query()->get();
    }

    /**
     * Get paginated records.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()->paginate($perPage);
    }

    /**
     * Create a new record.
     */
    public function create(array $data): Model
    {
        return $this->query()->create($data);
    }

    /**
     * Update a record.
     */
    public function update(Model $model, array $data): Model
    {
        $model->update($data);
        return $model->fresh();
    }

    /**
     * Delete a record.
     */
    public function delete(Model $model): bool
    {
        return $model->delete();
    }

    /**
     * Get the count of records.
     */
    public function count(): int
    {
        return $this->query()->count();
    }

    /**
     * Check if a record exists.
     */
    public function exists(array $conditions): bool
    {
        return $this->query()->where($conditions)->exists();
    }

    /**
     * Find records by column value.
     */
    public function findBy(string $column, mixed $value): Collection
    {
        return $this->query()->where($column, $value)->get();
    }

    /**
     * Find first record by column value.
     */
    public function findFirstBy(string $column, mixed $value): ?Model
    {
        return $this->query()->where($column, $value)->first();
    }

    /**
     * Find records with conditions.
     */
    public function findWhere(array $conditions): Collection
    {
        return $this->query()->where($conditions)->get();
    }
}
