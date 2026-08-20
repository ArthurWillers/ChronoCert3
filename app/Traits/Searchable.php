<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Searchable
{
    /**
     * Scope a query to search for a term across one or more columns.
     *
     * Uses PostgreSQL's unaccent() extension for accent-insensitive matching
     * and pg_trgm's similarity() function to order results by relevance.
     */
    public function scopeSearch(Builder $query, ?string $term, array|string $columns): Builder
    {
        if (blank($term) || blank($columns)) {
            return $query;
        }

        $columns = (array) $columns;
        $wrappedColumns = array_map(
            fn (string $column): string => $query->getQuery()->getGrammar()->wrap($this->qualifyColumn($column)),
            $columns,
        );

        $query->where(function (Builder $query) use ($wrappedColumns, $term): void {
            foreach ($wrappedColumns as $column) {
                $query->orWhereRaw("unaccent({$column}) ILIKE unaccent(?)", ["%{$term}%"]);
            }
        });

        $similarityExpressions = array_map(
            fn (string $column): string => "similarity(unaccent({$column}), unaccent(?))",
            $wrappedColumns,
        );

        return $query->orderByRaw(
            count($similarityExpressions) > 1
                ? 'GREATEST('.implode(', ', $similarityExpressions).') DESC'
                : $similarityExpressions[0].' DESC',
            array_fill(0, count($similarityExpressions), $term),
        );
    }
}
