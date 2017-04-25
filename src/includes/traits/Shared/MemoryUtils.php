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
    protected $memcached;

    /**
     * Memcached instance.
     *
     * @since 17xxxx Memory utils.
     *
     * @return Memcached|false
     */
    protected function memInstance()
    {
        if (isset($this->memcached)) {
            return $this->memcached;
        }
        if ($this->isAdvancedCache()) {
            if (COMET_CACHE_MEMCACHED_ENABLE) {
                $this->memcached = new Memcached(COMET_CACHE_MEMCACHED_SERVERS);
            }
        } elseif ($this->isPlugin()) {
            if ($this->options['memcached_enable']) {
                $this->memcached = new Memcached($this->options['memcached_servers']);
            }
        }
        return $this->memcached = isset($this->memcached) ? $this->memcached : false;
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
        return (bool) $this->memInstance();
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

        if ($instance instanceof Memcached && $instance->enabled) {
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

        if ($instance instanceof Memcached && $instance->enabled) {
            return $instance->set($primary_key, $sub_key, $value, $expires_in);
        }
        return false; // Not possible.
    }
}
/*[/pro]*/
