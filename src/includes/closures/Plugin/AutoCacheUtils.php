<?php
/*[pro strip-from="lite"]*/
namespace WebSharks\ZenCache\Pro;

/*
 * Runs the auto-cache engine.
 *
 * @since 150422 Rewrite.
 *
 * @attaches-to `_cron_zencache_auto_cache` hook.
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
