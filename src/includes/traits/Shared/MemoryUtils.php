<?php
/*[pro exclude-file-from="lite"]*/
/*[pro strip-from="lite"]*/
namespace WebSharks\CometCache\Pro\Traits\Shared;

use WebSharks\CometCache\Pro\Classes;

trait MemoryUtils
{
    /**
     * Memcached.
     *
     * @since 17xxxx
     *
     * @type Memcached
     */
    protected static $memcached;

    /**
     * Memcached instance.
     *
     * @since 17xxxx Memory utils.
     *
     * @return Classes\Memcached|false
     */
    protected function memInstance()
    {
        if (isset(static::$memcached)) {
            return static::$memcached;
        }
        if ($this->isAdvancedCache()) {
            if (COMET_CACHE_MEMCACHED_ENABLE) {
                static::$memcached = new Classes\Memcached(COMET_CACHE_MEMCACHED_SERVERS);
            }
        } elseif ($this->isPlugin()) {
            if ($this->options['memcached_enable']) {
                static::$memcached = new Classes\Memcached($this->options['memcached_servers']);
            }
        }
        return static::$memcached = isset(static::$memcached) ? static::$memcached : false;
    }

    /**
     * Memory enabled?
     *
     * @since 17xxxx Memory utils.
     *
     * @return bool True if enabled.
     */
    public function memEnabled()
    {
        $instance       = $this->memInstance();
        return $enabled = $instance instanceof Classes\Memcached && $instance->enabled();
    }

    /**
     * Get cache value by key.
     *
     * @since 17xxxx Memory utils.
     *
     * @param string     $primary_key Primary key.
     * @param string|int $sub_key     Sub-key to get.
     *
     * @return mixed|null Null if missing, or on failure.
     */
    public function memGet($primary_key, $sub_key)
    {
        $instance = $this->memInstance();

        if ($instance instanceof Classes\Memcached && $instance->enabled()) {
            return $instance->get($primary_key, $sub_key);
        }
        return null; // Not possible.
    }

    /**
     * Set|update cache key.
     *
     * @since 17xxxx Memory utils.
     *
     * @param string     $primary_key Primary key.
     * @param string|int $sub_key     Sub-key to set.
     * @param string     $value       Value to cache (1MB max).
     * @param int        $expires_in  Expires (in seconds).
     *
     * @return bool True on success.
     */
    public function memSet($primary_key, $sub_key, $value, $expires_in = 0)
    {
        $instance = $this->memInstance();

        if ($instance instanceof Classes\Memcached && $instance->enabled()) {
            return $instance->set($primary_key, $sub_key, $value, $expires_in);
        }
        return false; // Not possible.
    }

    /**
     * Clear the cache.
     *
     * @since 17xxxx Memory utils.
     *
     * @param string          $primary_key Primary key.
     * @param string|int|null $sub_key     Sub-key (optional).
     * @param int             $delay       Delay (in seconds).
     *
     * @return bool True on success.
     */
    public function memClear($primary_key, $sub_key = null, $delay = 0)
    {
        $instance = $this->memInstance();

        if ($instance instanceof Classes\Memcached && $instance->enabled()) {
            return $instance->clear($primary_key, $sub_key, $delay);
        }
        return false; // Not possible.
    }
}
/*[/pro]*/
