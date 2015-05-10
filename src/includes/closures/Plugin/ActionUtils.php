<?php
namespace WebSharks\ZenCache\Pro;

/*
 * Plugin action handler.
 *
 * @since 150422 Rewrite.
 *
 * @attaches-to `wp_loaded` hook.
 */
$self->actions = function () use ($self) {
    if (!empty($_REQUEST[GLOBAL_NS])) {
        new Actions();
    }
    if (!empty($_REQUEST[GLOBAL_NS.'_auto_cache_cron'])
    // Back compat. Allow for the older `__` variation also.
        || !empty($_REQUEST[GLOBAL_NS.'__auto_cache_cron'])) {
        $self->autoCache();
        exit();
    }
};
