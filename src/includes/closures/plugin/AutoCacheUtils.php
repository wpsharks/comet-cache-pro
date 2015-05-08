<?php
namespace WebSharks\ZenCache\Pro;

/**
 * Utilities.
 *
 * @since 150422 Rewrite.
 */
class AutoCacheUtils extends AbsBase
{
    /**
     * Runs the auto-cache engine.
     *
     * @since 150422 Rewrite.
     *
     * @attaches-to `_cron_zencache_auto_cache` hook.
     */
    public function auto_cache()
    {
        if (!$this->plugin->options['enable']) {
            return; // Nothing to do.
        }
        if (!$this->plugin->options['auto_cache_enable']) {
            return; // Nothing to do.
        }
        if (!$this->plugin->options['auto_cache_sitemap_url']) {
            if (!$this->plugin->options['auto_cache_other_urls']) {
                return; // Nothing to do.
            }
        }
        new AutoCache();
    }
}
