<?php
/*[pro strip-from="lite"]*/
namespace WebSharks\ZenCache\Pro;

/*
 * Runs the auto-cache engine via CRON job.
 *
 * @since 150422 Rewrite.
 *
 * @attaches-to `_cron_'.__GLOBAL_NS__.'_auto_cache`
 */
$self->autoCache = function () use ($self) {
    if (!$self->options['enable']) {
        return; // Nothing to do.
    }
    if (!$self->options['auto_cache_enable']) {
        return; // Nothing to do.
    }
    if (!$self->options['auto_cache_sitemap_url']) {
        if (!$self->options['auto_cache_other_urls']) {
            return; // Nothing to do.
        }
    }
    new AutoCache();
};
/*[/pro]*/
