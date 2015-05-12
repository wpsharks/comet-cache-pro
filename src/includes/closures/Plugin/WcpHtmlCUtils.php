<?php
namespace WebSharks\ZenCache\Pro;

/*[pro strip-from="lite"]*/
/*
 * Wipes out all HTML Compressor cache files.
 *
 * @since 150422 Rewrite.
 *
 * @param bool $manually Defaults to a `FALSE` value.
 *                       Pass as TRUE if the wiping is done manually by the site owner.
 *
 * @throws \Exception If a wipe failure occurs.
 *
 * @return int Total files wiped by this routine (if any).
 */
$self->wipeHtmlCCache = function ($manually = false) use ($self) {
    $counter = 0; // Initialize.

    if (!$manually && $self->disableAutoWipeCacheRoutines()) {
        return $counter; // Nothing to do.
    }
    @set_time_limit(1800); // @TODO Display a warning.

    $htmlc_cache_dirs[] = $self->wpContentBaseDirTo($self->htmlc_cache_sub_dir_public);
    $htmlc_cache_dirs[] = $self->wpContentBaseDirTo($self->htmlc_cache_sub_dir_private);

    foreach ($htmlc_cache_dirs as $_htmlc_cache_dir) {
        $counter += $self->deleteAllFilesDirsIn($_htmlc_cache_dir);
    }
    unset($_htmlc_cache_dir); // Just a little housekeeping.

    return $counter;
};

/*
 * Clear all HTML Compressor cache files for the current blog.
 *
 * @since 150422 Rewrite.
 *
 * @param bool $manually Defaults to a `FALSE` value.
 *                       Pass as TRUE if the clearing is done manually by the site owner.
 *
 * @throws \Exception If a clearing failure occurs.
 *
 * @return int Total files cleared by this routine (if any).
 */
$self->clearHtmlCCache = function ($manually = false) use ($self) {
    $counter = 0; // Initialize.

    if (!$manually && $self->disableAutoClearCacheRoutines()) {
        return $counter; // Nothing to do.
    }
    @set_time_limit(1800); // @TODO Display a warning.

    $host_token = $self->hostToken(true);
    if (($host_dir_token = $self->hostDirToken(true)) === '/') {
        $host_dir_token = ''; // Not necessary in this case.
    }
    $htmlc_cache_dirs[] = $self->wpContentBaseDirTo($self->htmlc_cache_sub_dir_public.$host_dir_token.'/'.$host_token);
    $htmlc_cache_dirs[] = $self->wpContentBaseDirTo($self->htmlc_cache_sub_dir_private.$host_dir_token.'/'.$host_token);

    foreach ($htmlc_cache_dirs as $_htmlc_cache_dir) {
        $counter += $self->deleteAllFilesDirsIn($_htmlc_cache_dir);
    }
    unset($_htmlc_cache_dir); // Just a little housekeeping.

    return $counter;
};
/*[/pro]*/
