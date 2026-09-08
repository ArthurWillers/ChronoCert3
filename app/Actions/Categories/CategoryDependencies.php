<?php

namespace App\Actions\Categories;

use App\Models\AccCategory;

class CategoryDependencies
{
    /**
     * Inspect incoming domain FKs, including those introduced by future modules.
     * Activity Log deliberately has no subject FK and does not block deletion.
     *
     * @return array<string, int>
     */
    public function execute(AccCategory $category): array
    {
        $connection = $category->getConnection();
        $schema = $connection->getSchemaBuilder();
        $dependencies = [];

        foreach ($schema->getTableListing() as $table) {
            foreach ($schema->getForeignKeys($table) as $foreignKey) {
                if ($foreignKey['foreign_table'] !== $category->getTable()) {
                    continue;
                }

                $query = $connection->table($table);
                foreach ($foreignKey['columns'] as $index => $column) {
                    $query->where($column, $category->getAttribute($foreignKey['foreign_columns'][$index]));
                }

                $count = $query->count();
                if ($count > 0) {
                    $dependencies[$table.'.'.$foreignKey['name']] = $count;
                }
            }
        }

        return $dependencies;
    }
}
