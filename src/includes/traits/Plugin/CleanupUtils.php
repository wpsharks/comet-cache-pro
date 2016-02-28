<?php
namespace WebSharks\CometCache\Pro\Traits\Plugin;

use WebSharks\CometCache\Pro\Classes;

trait CleanupUtils {
    /**
     * Runs cleanup routine via CRON job.
     *
     * @since 151002 While working on directory stats.
     *
     * @attaches-to `'_cron_'.__GLOBAL_NS__.'_cleanup'`
     */
    public function cleanupCache()
    {
        if (!$this->options['enable']) {
            return; // Nothing to do.
        }
        /*[pro strip-from="lite"]*/
        if ($this->options['cache_max_age_disable_if_load_average_is_gte'] && ($load_averages = $this->sysLoadAverages())) {
            if (max($load_averages) >= $this->options['cache_max_age_disable_if_load_average_is_gte']) {
                return; // Don't expire the cache when load average is high.
            }
        }
        /*[/pro]*/

        /*[pro strip-from="lite"]*/
        if ($this->options['stats_enable']) {
            $dir_stats = Classes\DirStats::instance();
            $dir_stats->forCache(true);
        }
        /*[/pro]*/
        $this->wurgeCache(); // Purge now.
    }
}
