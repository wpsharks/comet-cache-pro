<?php
namespace WebSharks\ZenCache\Pro;

/**
 * Utilities.
 *
 * @since 150422 Rewrite.
 */
class DbUtils extends AbsBase
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
