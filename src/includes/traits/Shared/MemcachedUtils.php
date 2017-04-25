<?php
namespace WebSharks\CometCache\Pro\Traits\Shared;

use WebSharks\CometCache\Pro\Classes;

trait MemcachedUtils
{
    /**
     * Enabled?
     *
     * @since 17xxxx
     */
    public function memcachedEnabled();

    /**
     * Get status/info.
     *
     * @since 17xxxx
     */
    public function memcachedInfo();

    /**
     * Set cache value.
     *
     * @since 17xxxx
     */
    public function memcachedSet();

    /**
     * Get cache value.
     *
     * @since 17xxxx
     */
    public function memcachedGet();

    /**
     * Touch cache value.
     *
     * @since 17xxxx
     */
    public function memcachedTouch();

    /**
     * Clear cache.
     *
     * @since 17xxxx
     */
    public function memcachedClear();
}
