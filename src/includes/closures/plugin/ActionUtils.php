<?php
namespace WebSharks\ZenCache\Pro;

/**
 * Utilities.
 *
 * @since 150422 Rewrite.
 */
class ActionUtils extends AbsBase
{
    /**
     * Plugin action handler.
     *
     * @since 150422 Rewrite.
     *
     * @attaches-to `wp_loaded` hook.
     */
    public function actions()
    {
        if (!empty($_REQUEST[GLOBAL_NS])) {
            new Actions();
        }
        if (!empty($_REQUEST[GLOBAL_NS.'__auto_cache_cron'])) {
            $this->plugin->auto_cache_utils->auto_cache().exit();
        }
    }
}
