<?php

declare(strict_types=1);

namespace Modules\Core\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Searchable
{
    /**
     * Get the columns that are searchable.
     * Override this method in your model to define searchable columns.
     *
     * @return array
     */
    abstract public function getSearchableColumns(): array;

    /**
     * Scope a query to search across searchable columns.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $columns = $this->getSearchableColumns();

        return $query->where(function (Builder $query) use ($term, $columns) {
            foreach ($columns as $column) {
                if (str_contains($column, '.')) {
                    // Relación: 'customer.name'
                    [$relation, $relationColumn] = explode('.', $column, 2);
                    $query->orWhereHas($relation, function (Builder $q) use ($relationColumn, $term) {
                        $q->where($relationColumn, 'like', "%{$term}%");
                    });
                } else {
                    // Columna directa
                    $query->orWhere($column, 'like', "%{$term}%");
                }
            }
        });
    }

    /**
     * Scope a query to search with exact match.
     */
    public function scopeSearchExact(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $columns = $this->getSearchableColumns();

        return $query->where(function (Builder $query) use ($term, $columns) {
            foreach ($columns as $column) {
                if (str_contains($column, '.')) {
                    [$relation, $relationColumn] = explode('.', $column, 2);
                    $query->orWhereHas($relation, function (Builder $q) use ($relationColumn, $term) {
                        $q->where($relationColumn, $term);
                    });
                } else {
                    $query->orWhere($column, $term);
                }
            }
        });
    }
}
