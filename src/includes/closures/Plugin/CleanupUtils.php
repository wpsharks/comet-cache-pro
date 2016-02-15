<?php
namespace WebSharks\CometCache\Pro;

/*
 * Runs cleanup routine via CRON job.
 *
 * @since 151002 While working on directory stats.
 *
 * @attaches-to `'_cron_'.__GLOBAL_NS__.'_cleanup'`
 */
$self->cleanupCache = function () use ($self) {
    if (!$self->options['enable']) {
        return; // Nothing to do.
    }
    /*[pro strip-from="lite"]*/
    if ($self->options['cache_max_age_disable_if_load_average_is_gte'] && ($load_averages = $self->sysLoadAverages())) {
        if (max($load_averages) >= $self->options['cache_max_age_disable_if_load_average_is_gte']) {
            return; // Don't expire the cache when load average is high.
        }
    }
    /*[/pro]*/

    /*[pro strip-from="lite"]*/
    if ($self->options['stats_enable']) {
        $dir_stats = DirStats::instance();
        $dir_stats->forCache(true);
    }
    /*[/pro]*/
    $self->wurgeCache(); // Purge now.
};
