<?php
/*[pro strip-from="lite"]*/
namespace WebSharks\ZenCache\Pro;

/*
 * Bumps CDN invalidation counter.
 *
 * @since 150422 Rewrite.
 */
$self->bumpCdnInvalidationCounter = function () use ($self) {
    if (!$self->options['enable']) {
        return; // Nothing to do.
    }
    if (!$self->options['cdn_enable']) {
        return; // Nothing to do.
    }
    $self->updateOptions(array('cdn_invalidation_counter' => ++$self->options['cdn_invalidation_counter']));
};
/*[/pro]*/
