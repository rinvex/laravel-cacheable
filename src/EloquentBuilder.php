<?php

declare(strict_types=1);

namespace Rinvex\Cacheable;

use Illuminate\Database\Eloquent\Builder;

/**
 * @mixin \Illuminate\Database\Query\Builder
 */
class EloquentBuilder extends Builder
{
    /**
     * Execute the query as a "select" statement.
     *
     * @param array $columns
     *
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function get($columns = ['*'])
    {
        $builder = $this->applyScopes();

        $closure = function () use ($builder, $columns) {
            // If we actually found models we will also eager load any relationships that
            // have been specified as needing to be eager loaded, which will solve the
            // n+1 query issue for the developers to avoid running a lot of queries.
            if (count($models = $builder->getModels($columns)) > 0) {
                $models = $builder->eagerLoadRelations($models);
            }

            return $builder->model->newCollection($models);
        };

        // Check if cache is enabled
        if ($builder->model->getCacheLifetime()) {
            return $builder->model->cacheQuery($builder, $columns, $closure);
        }

        // Cache disabled, just execute query & return result
        $results = call_user_func($closure);

        // We're done, let's clean up!
        $builder->model->resetCacheConfig();

        return $results;
    }
}
