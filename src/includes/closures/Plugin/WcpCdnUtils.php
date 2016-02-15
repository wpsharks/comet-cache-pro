<?php
/*[pro strip-from="lite"]*/
namespace WebSharks\ZenCache\Pro;

/*
 * Wipes out entire CDN cache.
 *
 * @since 151002 Implementing CDN cache wiping.
 *
 * @param bool $manually True if wiping is done manually.
 * @param boolean $maybe Defaults to a true value.
 *
 * @throws \Exception If a wipe failure occurs.
 *
 * @return integer CDN invalidation counter after wiping.
 *  Zero, or a negative integer if wiping did not take place.
 */
$self->wipeCdnCache = function ($manually = false, $maybe = true) use ($self) {
    if (!$self->options['cdn_enable']) {
        return -(integer) $self->options['cdn_invalidation_counter'];
    }
    if ($maybe && !$self->options['cache_clear_cdn_enable']) {
        return -(integer) $self->options['cdn_invalidation_counter'];
    }
    $self->updateOptions(['cdn_invalidation_counter' => ++$self->options['cdn_invalidation_counter']]);

    return (integer) $self->options['cdn_invalidation_counter'];
};

/*
 * Clears the CDN cache.
 *
 * @since 151002 Implementing CDN cache clearing.
 *
 * @param bool $manually True if clearing is done manually.
 * @param boolean $maybe Defaults to a true value.
 *
 * @throws \Exception If a clear failure occurs.
 *
 * @return integer CDN invalidation counter after clearing.
 *  Zero, or a negative integer if clearing did not take place.
 */
$self->clearCdnCache = function ($manually = false, $maybe = true) use ($self) {
    return $self->wipeCdnCache($manually, $maybe);
};
/*[/pro]*/
