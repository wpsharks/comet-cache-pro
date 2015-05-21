<?php
namespace WebSharks\ZenCache\Pro;

/*
 * Ignores user aborts; when/if the Auto-Cache Engine is running.
 *
 * @since 150422 Rewrite.
 */
$self->maybeIgnoreUserAbort = function () use ($self) {
    /*[pro strip-from="lite"]*/
    if ($self->isAutoCacheEngine()) {
        ignore_user_abort(true);
    }
    /*[/pro]*/
};
