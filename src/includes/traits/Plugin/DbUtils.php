<?php
namespace WebSharks\CometCache\Pro\Traits\Plugin;

use WebSharks\CometCache\Pro\Classes;

trait DbUtils
{
    /**
     * WordPress database instance.
     *
     * @since 150422 Rewrite.
     *
     * @return \wpdb Reference for IDEs.
     */
    public function wpdb()
    {
        return $GLOBALS['wpdb'];
    }
}
