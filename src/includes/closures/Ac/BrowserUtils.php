<?php
namespace WebSharks\ZenCache\Pro;

/*
 * Sends no-cache headers (if applicable).
 *
 * @since 150422 Rewrite.
 */
$self->maybeStopBrowserCaching = function () use ($self) {
    if (ZENCACHE_ALLOW_BROWSER_CACHE) {
        return; // Allow in this case.
    }
    if (!empty($_GET[SHORT_NAME.'ABC'])) {
        if (filter_var($_GET[SHORT_NAME.'ABC'], FILTER_VALIDATE_BOOLEAN)) {
            return; // The query var says it's OK here.
        }
    }
    header_remove('Last-Modified');
    header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
    header('Cache-Control: no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
};
