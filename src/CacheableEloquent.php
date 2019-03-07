<?php

declare(strict_types=1);

namespace Rinvex\Cacheable;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

trait CacheableEloquent
{
    /**
     * Register an updated model event with the dispatcher.
     *
     * @param \Closure|string $callback
     *
     * @return void
     */
    abstract public static function updated($callback);

    /**
     * Register a created model event with the dispatcher.
     *
     * @param \Closure|string $callback
     *
     * @return void
     */
    abstract public static function created($callback);

    /**
     * Register a deleted model event with the dispatcher.
     *
     * @param \Closure|string $callback
     *
     * @return void
     */
    abstract public static function deleted($callback);

    /**
     * Boot the cacheable eloquent trait for a model.
     *
     * @return void
     */
    public static function bootCacheableEloquent(): void
    {
        static::updated(function (Model $cachedModel) {
            ! $cachedModel->isCacheClearEnabled() || $cachedModel::forgetCache();
        });

        static::created(function (Model $cachedModel) {
            ! $cachedModel->isCacheClearEnabled() || $cachedModel::forgetCache();
        });

        static::deleted(function (Model $cachedModel) {
            ! $cachedModel->isCacheClearEnabled() || $cachedModel::forgetCache();
        });
    }

    /**
     * Store the given cache key for the given model by mimicking cache tags.
     *
     * @param string $modelName
     * @param string $cacheKey
     *
     * @return void
     */
    protected static function storeCacheKey(string $modelName, string $cacheKey): void
    {
        $keysFile = storage_path('framework/cache/data/rinvex.cacheable.json');
        $cacheKeys = static::getCacheKeys($keysFile);

        if (! isset($cacheKeys[$modelName]) || ! in_array($cacheKey, $cacheKeys[$modelName])) {
            $cacheKeys[$modelName][] = $cacheKey;
            file_put_contents($keysFile, json_encode($cacheKeys));
        }
    }

    /**
     * Get cache keys from the given file.
     *
     * @param string $file
     *
     * @return array
     */
    protected static function getCacheKeys($file): array
    {
        if (! file_exists($file)) {
            $dir = dirname($file);
            is_dir($dir) || mkdir($dir);
            file_put_contents($file, null);
        }

        return json_decode(file_get_contents($file), true) ?: [];
    }

    /**
     * Flush cache keys of the given model by mimicking cache tags.
     *
     * @param string $modelName
     *
     * @return array
     */
    protected static function flushCacheKeys(string $modelName): array
    {
        $flushedKeys = [];
        $keysFile = storage_path('framework/cache/data/rinvex.cacheable.json');
        $cacheKeys = static::getCacheKeys($keysFile);

        if (isset($cacheKeys[$modelName])) {
            $flushedKeys = $cacheKeys[$modelName];

            unset($cacheKeys[$modelName]);

            file_put_contents($keysFile, json_encode($cacheKeys));
        }

        return $flushedKeys;
    }

    /**
     * Set the model cache lifetime.
     *
     * @param int $cacheLifetime
     *
     * @return $this
     */
    public function setCacheLifetime(int $cacheLifetime)
    {
        $this->cacheLifetime = $cacheLifetime;

        return $this;
    }

    /**
     * Get the model cache lifetime.
     *
     * @return int
     */
    public function getCacheLifetime(): int
    {
        return $this->cacheLifetime ?? -1;
    }

    /**
     * Set the model cache driver.
     *
     * @param string $cacheDriver
     *
     * @return $this
     */
    public function setCacheDriver($cacheDriver)
    {
        $this->cacheDriver = $cacheDriver;

        return $this;
    }

    /**
     * Get the model cache driver.
     *
     * @return string|null
     */
    public function getCacheDriver(): ?string
    {
        return $this->cacheDriver ?? null;
    }

    /**
     * Determine if model cache clear is enabled.
     *
     * @return bool
     */
    public function isCacheClearEnabled(): bool
    {
        return $this->cacheClearEnabled ?? true;
    }

    /**
     * Forget the model cache.
     *
     * @return void
     */
    public static function forgetCache()
    {
        static::fireCacheFlushEvent('cache.flushing');

        // Flush cache tags
        if (method_exists(app('cache')->getStore(), 'tags')) {
            app('cache')->tags(static::class)->flush();
        } else {
            // Flush cache keys, then forget actual cache
            foreach (static::flushCacheKeys(static::class) as $cacheKey) {
                app('cache')->forget($cacheKey);
            }
        }

        static::fireCacheFlushEvent('cache.flushed', false);
    }

    /**
     * Fire the given event for the model.
     *
     * @param string $event
     * @param bool   $halt
     *
     * @return mixed
     */
    protected static function fireCacheFlushEvent($event, $halt = true)
    {
        if (! isset(static::$dispatcher)) {
            return true;
        }

        // We will append the names of the class to the event to distinguish it from
        // other model events that are fired, allowing us to listen on each model
        // event set individually instead of catching event for all the models.
        $event = "eloquent.{$event}: ".static::class;

        $method = $halt ? 'until' : 'dispatch';

        return static::$dispatcher->{$method}($event, static::class);
    }

    /**
     * Reset cached model to its defaults.
     *
     * @return $this
     */
    public function resetCacheConfig()
    {
        ! $this->cacheDriver || $this->cacheDriver = null;
        ! $this->cacheLifetime || $this->cacheLifetime = -1;

        return $this;
    }

    /**
     * Generate unique cache key.
     *
     * @param \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder $builder
     * @param array                                                                    $columns
     *
     * @return string
     */
    protected function generateCacheKey($builder, array $columns): string
    {
        $query = $builder instanceof Builder ? $builder->getQuery() : $builder;
        $vars = [
            'aggregate' => $query->aggregate,
            'columns' => $query->columns,
            'distinct' => $query->distinct,
            'from' => $query->from,
            'joins' => $query->joins,
            'wheres' => $query->wheres,
            'groups' => $query->groups,
            'havings' => $query->havings,
            'orders' => $query->orders,
            'limit' => $query->limit,
            'offset' => $query->offset,
            'unions' => $query->unions,
            'unionLimit' => $query->unionLimit,
            'unionOffset' => $query->unionOffset,
            'unionOrders' => $query->unionOrders,
            'lock' => $query->lock,
        ];

        return md5(json_encode([
            $vars,
            $columns,
            static::class,
            $this->getCacheDriver(),
            $this->getCacheLifetime(),
            $builder instanceof Builder ? $builder->getEagerLoads() : null,
            $builder->getBindings(),
            $builder->toSql(),
        ]));
    }

    /**
     * Cache given callback.
     *
     * @param \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder $builder
     * @param array                                                                    $columns
     * @param \Closure                                                                 $closure
     *
     * @return mixed
     */
    public function cacheQuery($builder, array $columns, Closure $closure)
    {
        $modelName = $this->getMorphClass();
        $lifetime = $this->getCacheLifetime();
        $cacheKey = $this->generateCacheKey($builder, $columns);

        // Switch cache driver on runtime
        if ($driver = $this->getCacheDriver()) {
            app('cache')->setDefaultDriver($driver);
        }

        // We need cache tags, check if default driver supports it
        if (method_exists(app('cache')->getStore(), 'tags')) {
            $result = $lifetime === -1 ? app('cache')->tags($modelName)->rememberForever($cacheKey, $closure) : app('cache')->tags($modelName)->remember($cacheKey, $lifetime, $closure);

            return $result;
        }

        $result = $lifetime === -1 ? app('cache')->rememberForever($cacheKey, $closure) : app('cache')->remember($cacheKey, $lifetime, $closure);

        // Default cache driver doesn't support tags, let's do it manually
        static::storeCacheKey($modelName, $cacheKey);

        // We're done, let's clean up!
        $this->resetCacheConfig();

        return $result;
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param \Illuminate\Database\Query\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new EloquentBuilder($query);
    }
}
