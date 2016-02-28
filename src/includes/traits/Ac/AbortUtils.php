<?php
namespace WebSharks\CometCache\Pro\Traits\Ac;

use WebSharks\CometCache\Pro\Classes;

trait AbortUtils {
    /*
     * Ignores user aborts; when/if the Auto-Cache Engine is running.
     *
     * @since 150422 Rewrite.
     */
    public function maybeIgnoreUserAbort()
    {
        /*[pro strip-from="lite"]*/
        if ($this->isAutoCacheEngine()) {
            ignore_user_abort(true);
        }
        /*[/pro]*/
    }
}
