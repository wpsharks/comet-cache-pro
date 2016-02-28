<?php
namespace WebSharks\CometCache\Pro\Traits\Plugin;

/*
 * WordPress database instance.
 *
 * @since 150422 Rewrite.
 *
 * @return \wpdb Reference for IDEs.
 */
$self->wpdb = function () use ($self) {
    return $GLOBALS['wpdb'];
};
