<?php
/*[pro strip-from="lite"]*/
namespace WebSharks\ZenCache\Pro;
/*
 * Wipes out entire CDN cache.
 *
 * @since 15xxxx Implementing CDN cache wiping.
 *
 * @param bool $manually TRUE if the wiping is done manually by the site owner.
 *
 * @throws \Exception If a wipe failure occurs.
 *
 * @return boolean True if the CDN cache is wiped.
 */
$self->wipeCdnCache = function ($manually = false) use ($self) {
    if (!$self->options['cdn_enable']) {
        return false; // Nothing to do.
    }
    $self->updateOptions(array('cdn_invalidation_counter' => ++$self->options['cdn_invalidation_counter']));

    return true;
};

/*
 * Clears the CDN cache.
 *
 * @since 15xxxx Implementing CDN cache clearing.
 *
 * @param bool $manually TRUE if the clearing is done manually by the site owner.
 *
 * @throws \Exception If a clear failure occurs.
 *
 * @return boolean True if the CDN cache is cleared.
 */
$self->clearCdnCache = function ($manually = false) use ($self) {
    return $self->wipeCdnCache($manually);
};
/*[/pro]*/
