<?php

/*
 * NOTICE OF LICENSE
 *
 * Part of the Rinvex Cacheable Package.
 *
 * This source file is subject to The MIT License (MIT)
 * that is bundled with this package in the LICENSE file.
 *
 * Package: Rinvex Cacheable Package
 * License: The MIT License (MIT)
 * Link:    https://rinvex.com
 */

namespace Rinvex\Cacheable;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Container\Container;

trait CacheableEloquent
{
    /**
     * The IoC container instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * The methods to clear cache on.
     *
     * @var array
     */
    protected $cacheClearOn = [
        'create',
        'update',
        'delete',
    ];

    /**
     * The model cache driver.
     *
     * @var string
     */
    protected $cacheDriver;

    /**
     * The model cache lifetime.
     *
     * @var float|int
     */
    protected $cacheLifetime = -1;

    /**
     * Indicate if the model cache clear is enabled.
     *
     * @var bool
     */
    protected $cacheClearEnabled = true;

    /**
     * Fire the given event for the model.
     *
     * @param  string  $event
     * @param  bool  $halt
     *
     * @return mixed
     */
    abstract protected function fireModelEvent($event, $halt = true);

    /**
     * Register an updated model event with the dispatcher.
     *
     * @param  \Closure|string $callback
     * @param  int             $priority
     *
     * @return void
     */
    abstract public static function updated($callback, $priority = 0);

    /**
     * Register a created model event with the dispatcher.
     *
     * @param \Closure|string $callback
     * @param int             $priority
     *
     * @return void
     */
    abstract public static function created($callback, $priority = 0);

    /**
     * Register a deleted model event with the dispatcher.
     *
     * @param  \Closure|string $callback
     * @param  int             $priority
     *
     * @return void
     */
    abstract public static function deleted($callback, $priority = 0);

    /**
     * Forget model cache on create/update/delete.
     *
     * @return void
     */
    public static function bootAbstractCacheable()
    {
        static::updated(function (Model $cachedModel) {
            if ($cachedModel->isCacheClearEnabled() && in_array('update', $cachedModel->cacheClearOn)) {
                $cachedModel->forgetCache();
            }
        });

        static::created(function (Model $cachedModel) {
            if ($cachedModel->isCacheClearEnabled() && in_array('create', $cachedModel->cacheClearOn)) {
                $cachedModel->forgetCache();
            }
        });

        static::deleted(function (Model $cachedModel) {
            if ($cachedModel->isCacheClearEnabled() && in_array('delete', $cachedModel->cacheClearOn)) {
                $cachedModel->forgetCache();
            }
        });
    }

    /**
     * Set the IoC container instance.
     *
     * @param \Illuminate\Contracts\Container\Container $container
     *
     * @return $this
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Get the IoC container instance or any of its services.
     *
     * @param string|null $service
     *
     * @return mixed
     */
    public function getContainer($service = null)
    {
        return is_null($service) ? ($this->container ?: app()) : ($this->container[$service] ?: app($service));
    }

    /**
     * Store the given cache key for the given model by mimicking cache tags.
     *
     * @param string $model
     * @param string $cacheKey
     *
     * @return void
     */
    protected function storeCacheKey(string $model, string $cacheKey)
    {
        $keysFile = storage_path('framework/cache/rinvex.cacheable.json');
        $cacheKeys = $this->getCacheKeys($keysFile);

        if (! isset($cacheKeys[$model]) || ! in_array($cacheKey, $cacheKeys[$model])) {
            $cacheKeys[$model][] = $cacheKey;
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
    protected function getCacheKeys($file)
    {
        if (! file_exists($file)) {
            file_put_contents($file, null);
        }

        return json_decode(file_get_contents($file), true) ?: [];
    }

    /**
     * Flush cache keys of the given model by mimicking cache tags.
     *
     * @param string $model
     *
     * @return array
     */
    protected function flushCacheKeys(string $model): array
    {
        $flushedKeys = [];
        $keysFile = storage_path('framework/cache/rinvex.cacheable.json');
        $cacheKeys = $this->getCacheKeys($keysFile);

        if (isset($cacheKeys[$model])) {
            $flushedKeys = $cacheKeys[$model];

            unset($cacheKeys[$model]);

            file_put_contents($keysFile, json_encode($cacheKeys));
        }

        return $flushedKeys;
    }

    /**
     * Set the model cache lifetime.
     *
     * @param float|int $cacheLifetime
     *
     * @return $this
     */
    public function setCacheLifetime($cacheLifetime)
    {
        $this->cacheLifetime = $cacheLifetime;

        return $this;
    }

    /**
     * Get the model cache lifetime.
     *
     * @return float|int
     */
    public function getCacheLifetime()
    {
        return $this->cacheLifetime;
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
     * @return string
     */
    public function getCacheDriver()
    {
        return $this->cacheDriver;
    }

    /**
     * Enable model cache clear.
     *
     * @param bool $status
     *
     * @return $this
     */
    public function enableCacheClear($status = true)
    {
        $this->cacheClearEnabled = $status;

        return $this;
    }

    /**
     * Determine if model cache clear is enabled.
     *
     * @return bool
     */
    public function isCacheClearEnabled()
    {
        return $this->cacheClearEnabled;
    }

    /**
     * Forget the model cache.
     *
     * @return $this
     */
    public function forgetCache()
    {
        if ($this->getCacheLifetime()) {
            // Flush cache tags
            if (method_exists($this->getContainer('cache')->getStore(), 'tags')) {
                $this->getContainer('cache')->tags(static::class)->flush();
            } else {
                // Flush cache keys, then forget actual cache
                foreach ($this->flushCacheKeys(static::class) as $cacheKey) {
                    $this->getContainer('cache')->forget($cacheKey);
                }
            }

            $this->fireModelEvent('.cache.flushed', false);
        }

        return $this;
    }

    /**
     * Reset cached model to its defaults.
     *
     * @return $this
     */
    protected function resetCacheConfig()
    {
        $this->cacheDriver = null;
        $this->cacheLifetime = null;
        $this->cacheClearEnabled = null;

        return $this;
    }

    /**
     * Generate unique cache key.
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param array                                 $columns
     *
     * @return string
     */
    protected function generateCacheKey(Builder $builder, array $columns)
    {
        $query = $builder->getQuery();
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
            $this->getCacheDriver(),
            $this->getCacheLifetime(),
            get_class($builder->getModel()),
            $builder->getEagerLoads(),
            $builder->getBindings(),
            $builder->toSql(),
        ]));
    }

    /**
     * Cache given callback.
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param array                                 $columns
     * @param \Closure                              $closure
     *
     * @return mixed
     */
    public function cacheQuery(Builder $builder, array $columns, Closure $closure)
    {
        $lifetime = $this->getCacheLifetime();
        $model = get_class($builder->getModel());
        $cacheKey = $this->generateCacheKey($builder, $columns);

        // Switch cache driver on runtime
        if ($driver = $this->getCacheDriver()) {
            $this->getContainer('cache')->setDefaultDriver($driver);
        }

        // We need cache tags, check if default driver supports it
        if (method_exists($this->getContainer('cache')->getStore(), 'tags')) {
            $result = $lifetime === -1 ? $this->getContainer('cache')->tags($model)->rememberForever($cacheKey, $closure) : $this->getContainer('cache')->tags($model)->remember($cacheKey, $lifetime, $closure);

            return $result;
        }

        $result = $lifetime === -1 ? $this->getContainer('cache')->rememberForever($cacheKey, $closure) : $this->getContainer('cache')->remember($cacheKey, $lifetime, $closure);

        // Default cache driver doesn't support tags, let's do it manually
        $this->storeCacheKey($model, $cacheKey);

        // We're done, let's clean up!
        $this->resetCacheConfig();

        return $result;
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     *
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new EloquentBuilder($query);
    }
}
