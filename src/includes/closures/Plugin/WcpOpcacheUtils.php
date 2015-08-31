<?php
/*[pro strip-from="lite"]*/
namespace WebSharks\ZenCache\Pro;

/*
 * Wipe (i.e., reset) OPCache.
 *
 * @since 15xxxx Adding OPCache support.
 *
 * @param boolean $maybe Defaults to a true value.
 *
 * @return integer Total keys wiped.
 */
$self->wipeOpcache = function ($maybe = true) use ($self) {
    $counter = 0; // Initialize counter.

    if ($maybe && !$self->options['cache_clear_opcache_enable']) {
        return $counter; // Not enabled at this time.
    }
    if (!$self->functionIsPossible('opcache_reset')) {
        return $counter; // Not possible.
    }
    if (!($status = $self->sysOpcacheStatus())) {
        return $counter; // Not possible.
    }
    if (empty($status->opcache_enabled)) {
        return $counter; // Not necessary.
    }
    if (!isset($status->opcache_statistics->num_cached_keys)) {
        return $counter; // Not possible.
    }
    if (opcache_reset()) { // True if a reset occurs.
        $counter += $status->opcache_statistics->num_cached_keys;
    }
    return $counter;
};

/*
 * Clear (i.e., reset) OPCache.
 *
 * @since 15xxxx Adding OPCache support.
 *
 * @param boolean $maybe Defaults to a true value.
 *
 * @return integer Total keys cleared.
 */
$self->clearOpcache = function ($maybe = true) use ($self) {
    if (!is_multisite() || is_main_site() || current_user_can($self->network_cap)) {
        return $self->wipeOpcache($maybe);
    }
    return 0; // Not applicable.
};
/*[/pro]*/
