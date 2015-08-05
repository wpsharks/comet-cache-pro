<?php
/*[pro strip-from="lite"]*/
namespace WebSharks\ZenCache\Pro;

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
 * @param boolean $___without_domain_mapping For internal use only.
 *
 * @throws \Exception If a clearing failure occurs.
 *
 * @return int Total files cleared by this routine (if any).
 */
$self->clearHtmlCCache = function ($manually = false, $___without_domain_mapping = false) use ($self) {
    $counter = 0; // Initialize.

    if (!$manually && $self->disableAutoClearCacheRoutines()) {
        return $counter; // Nothing to do.
    }
    @set_time_limit(1800); // @TODO Display a warning.

    // Deals with multisite base & sub-directory installs.
    // e.g. `htmlc/cache/public/www-example-com` (standard WP installation)
    // e.g. `htmlc/cache/public/[[/base]/child1]/www-example-com` (multisite network)
    // Note that `www-example-com` (current host slug) is appended by the HTML compressor.

    $host_token           = $self->hostToken(true, !$___without_domain_mapping);
    $host_base_dir_tokens = $_host_base_dir_tokens = rtrim($self->hostBaseDirTokens(true), '/');

    if (!$___without_domain_mapping && $self->isDomainMapping()) {
        $host_base_dir_tokens = ''; // Not applicable w/ domain mapping.
    }
    $htmlc_cache_dirs[] = $self->wpContentBaseDirTo($self->htmlc_cache_sub_dir_public.rtrim($host_base_dir_tokens, '/').'/'.$host_token);
    $htmlc_cache_dirs[] = $self->wpContentBaseDirTo($self->htmlc_cache_sub_dir_private.rtrim($host_base_dir_tokens, '/').'/'.$host_token);

    foreach ($htmlc_cache_dirs as $_htmlc_cache_dir) {
        $counter += $self->deleteAllFilesDirsIn($_htmlc_cache_dir);
    }
    unset($_htmlc_cache_dir); // Just a little housekeeping.

    // This runs one additional deletion scan for the unmapped variation.
    if (!$___without_domain_mapping && is_multisite() && $self->canConsiderDomainMapping()) {
        $counter += $self->clearHtmlCCache($manually, true);
    } // @TODO iteration over all mapped domains.
    return $counter;
};
/*[/pro]*/
