<?php

namespace App\Policies;

use Illuminate\Support\Facades\Cache;

class CachedPolicy
{
    /**
     * Time to store the cached values
     *
     * @var int
     */
    const TIME = 3600;

    /**
     * The cache instance to use for caching policies.
     *
     * @var Cache
     */
    protected $cache;

    /**
     * Create a new CachedPolicy
     */
    public function __construct()
    {
        $this->cache = Cache::store('redis');
    }

    /**
     * @param $key
     * @param $callback
     * @return mixed
     */
    public function remember($key, $callback)
    {
        return call_user_func_array([$this->cache, 'remember'], [$key, now()->addMinutes(self::TIME), $callback]);
    }
}