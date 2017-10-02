<?php

declare(strict_types=1);

namespace Rinvex\Cacheable;

use Illuminate\Database\Eloquent\Relations\BelongsToMany as BaseBelongsToMany;

class BelongsToMany extends BaseBelongsToMany
{
    /**
     * Execute the query as a "select" statement.
     *
     * @param array $columns
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function get($columns = ['*'])
    {
        $builder = $this->query->applyScopes();

        $closure = function () use ($builder, $columns) {
            // First we'll add the proper select columns onto the query so it is run with
            // the proper columns. Then, we will get the results and hydrate out pivot
            // models with the result of those columns as a separate model relation.
            $columns = $this->query->getQuery()->columns ? [] : $columns;

            $models = $builder->addSelect(
                $this->shouldSelect($columns)
            )->getModels();

            $this->hydratePivotRelation($models);

            // If we actually found models we will also eager load any relationships that
            // have been specified as needing to be eager loaded. This will solve the
            // n + 1 query problem for the developer and also increase performance.
            if (count($models) > 0) {
                $models = $builder->eagerLoadRelations($models);
            }

            return $this->related->newCollection($models);
        };

        // Check if cache is enabled
        if ($builder->getModel()->getCacheLifetime()) {
            return $builder->getModel()->cacheQuery($builder, $columns, $closure);
        }

        // Cache disabled, just execute query & return result
        $results = call_user_func($closure);

        // We're done, let's clean up!
        $builder->getModel()->resetCacheConfig();

        return $results;
    }
}
