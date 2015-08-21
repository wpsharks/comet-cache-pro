<?php
namespace WebSharks\ZenCache\Pro;

/*
 * Runs cleanup routine via CRON job.
 *
 * @since 15xxxx While working on directory stats.
 *
 * @attaches-to `'_cron_'.__GLOBAL_NS__.'_cleanup'`
 *
 * @TODO Disable automatically when load average is high.
 *  See: <https://github.com/websharks/zencache/issues/347>
 *  Note: this is impact the AdvancedCache expiration check also.
 */
$self->cleanupCache = function () use ($self) {
    if (!$self->options['enable']) {
        return; // Nothing to do.
    }
    /*[pro strip-from="lite"]*/
    if ($self->options['stats_enable']) {
        $dir_stats = DirStats::instance();
        $dir_stats->forCache(true);
    }
    /*[/pro]*/
    $self->wurgeCache(); // Purge now.
};
