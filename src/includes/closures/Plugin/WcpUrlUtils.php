<?php
/*[pro strip-from="lite"]*/
namespace WebSharks\ZenCache\Pro;

/*
* Automatically clears cache files for a list of custom URLs.
*
* @since 151114 Adding support for a custom list of URLs.
*
* @throws \Exception If a clear failure occurs.
*
* @return int Total files cleared by this routine (if any).
*
* @note Unlike many of the other `auto_` methods, this one is NOT currently
*    attached to any hooks. However, it is called upon by other routines attached to hooks.
*/
$self->autoClearUrlsCache = function () use ($self) {
    $counter = 0; // Initialize.

    if (!is_null($done = &$self->cacheKey('autoClearUrlsCache'))) {
        return $counter; // Already did this.
    }
    $done = true; // Flag as having been done.

    if (!$self->options['enable']) {
        return $counter; // Nothing to do.
    }
    if (!$self->options['cache_clear_urls']) {
        return $counter; // Nothing to do.
    }
    if (!is_dir($cache_dir = $self->cacheDir())) {
        return $counter; // Nothing to do.
    }
    foreach (preg_split('/['."\r\n".']+/', $self->options['cache_clear_urls'], -1, PREG_SPLIT_NO_EMPTY) as $_url) {
        if (stripos($_url, 'http') === 0) {
            $_regex = $self->buildCachePathRegexFromWcUrl($_url);
            $counter += $self->deleteFilesFromCacheDir($_regex);
        }
    } unset($_url, $_regex); // Housekeeping.

    if ($counter && is_admin() && (!IS_PRO || $self->options['change_notifications_enable'])) {
        $self->enqueueNotice('<img src="'.esc_attr($self->url('/src/client-s/images/clear.png')).'" style="float:left; margin:0 10px 0 0; border:0;" />'.
                              sprintf(__('<strong>%1$s:</strong> detected changes. Found %2$s in the cache matching a custom list of URLs; auto-clearing.', SLUG_TD), esc_html(NAME), esc_html($self->i18nFiles($counter))));
    }
    return $counter;
};
/*[/pro]*/
