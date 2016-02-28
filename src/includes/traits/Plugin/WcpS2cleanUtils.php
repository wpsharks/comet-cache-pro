<?php
/*[pro strip-from="lite"]*/
namespace WebSharks\CometCache\Pro\Traits\Plugin;

/*
 * Wipe (i.e., reset) s2Clean cache.
 *
 * @since 151002 While adding OPCache support.
 *
 * @param bool $manually True if wiping is done manually.
 * @param boolean $maybe Defaults to a true value.
 *
 * @return integer Total files wiped in s2Clean.
 */
$self->wipeS2CleanCache = function ($manually = false, $maybe = true) use ($self) {
    $counter = 0; // Initialize counter.

    if ($maybe && !$self->options['cache_clear_s2clean_enable']) {
        return $counter; // Not enabled at this time.
    }
    if (!$self->functionIsPossible('s2clean')) {
        return $counter; // Not possible.
    }
    $counter += s2clean()->md_cache_clear();

    return $counter;
};

/*
 * Clear (i.e., reset) s2Clean cache.
 *
 * @since 151002 While adding OPCache support.
 *
 * @param bool $manually True if clearing is done manually.
 * @param boolean $maybe Defaults to a true value.
 *
 * @return integer Total files cleared in s2Clean.
 */
$self->clearS2CleanCache = function ($manually = false, $maybe = true) use ($self) {
    if (!is_multisite() || is_main_site() || current_user_can($self->network_cap)) {
        return $self->wipeS2CleanCache($manually, $maybe);
    }
    return 0; // Not applicable.
};
/*[/pro]*/
