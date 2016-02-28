<?php
namespace WebSharks\CometCache\Pro\Traits\Plugin;

use WebSharks\CometCache\Pro\Classes;

trait ActionUtils {
    /*
     * Plugin action handler.
     *
     * @since 150422 Rewrite.
     *
     * @attaches-to `wp_loaded` hook.
     */
    public function actions()
    {
        if (!empty($_REQUEST[GLOBAL_NS])) {
            new Classes\Actions();
        }
        /*[pro strip-from="lite"]*/
        if (!empty($_REQUEST[GLOBAL_NS.'_auto_cache_cron'])
            // Back compat. Allow for the older `__` variation also.
            || !empty($_REQUEST[GLOBAL_NS.'__auto_cache_cron'])
        ) {
            $this->autoCache();
            exit();
        }
        /*[/pro]*/
    }
}
