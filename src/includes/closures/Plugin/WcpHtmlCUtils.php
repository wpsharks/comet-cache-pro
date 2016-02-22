<?php
/*[pro strip-from="lite"]*/
namespace WebSharks\CometCache\Pro;

/*
 * Wipes out all HTML Compressor cache files.
 *
 * @since 150422 Rewrite. Updated 151002 w/ multisite compat. improvements.
 *
 * @param bool $manually TRUE if the wiping is done manually by the site owner.
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

    $htmlc_cache_dirs   = array(); // Initialize directories.
    $htmlc_cache_dirs[] = $self->wpContentBaseDirTo($self->htmlc_cache_sub_dir_public);
    $htmlc_cache_dirs[] = $self->wpContentBaseDirTo($self->htmlc_cache_sub_dir_private);

    foreach (array_unique($htmlc_cache_dirs) as $_htmlc_cache_dir) {
        $counter += $self->deleteAllFilesDirsIn($_htmlc_cache_dir);
    }
    unset($_htmlc_cache_dir); // Just a little housekeeping.

    return $counter;
};

/*
 * Clear all HTML Compressor cache files for the current blog.
 *
 * @since 150422 Rewrite. Updated 151002 w/ multisite compat. improvements.
 *
 * @param bool $manually TRUE if the clearing is done manually by the site owner.
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

    // Deals with multisite base & sub-directory installs.
    // e.g. `htmlc/cache/public/www-example-com` (standard WP installation).
    // e.g. `htmlc/cache/public/[[/base]/child1]/www-example-com` (multisite network).
    // Note that `www-example-com` (current host slug) is appended by the HTML compressor.

    $host_token           = $self->hostToken(true); // Dashify.
    $host_base_dir_tokens = $self->hostBaseDirTokens(true); // Dashify.

    $htmlc_cache_dirs   = array(); // Initialize array of all HTML Compressor directories to clear.
    $htmlc_cache_dirs[] = $self->wpContentBaseDirTo($self->htmlc_cache_sub_dir_public.rtrim($host_base_dir_tokens, '/').'/'.$host_token);
    $htmlc_cache_dirs[] = $self->wpContentBaseDirTo($self->htmlc_cache_sub_dir_private.rtrim($host_base_dir_tokens, '/').'/'.$host_token);

    if (is_multisite() && $self->canConsiderDomainMapping()) {
        if (($_host_token_for_blog = $self->hostTokenForBlog(true))) { // Dashify.
            $_host_base_dir_tokens_for_blog = $self->hostBaseDirTokensForBlog(true); // Dashify.
            $htmlc_cache_dirs[]             = $self->wpContentBaseDirTo($self->htmlc_cache_sub_dir_public.rtrim($_host_base_dir_tokens_for_blog, '/').'/'.$_host_token_for_blog);
            $htmlc_cache_dirs[]             = $self->wpContentBaseDirTo($self->htmlc_cache_sub_dir_private.rtrim($_host_base_dir_tokens_for_blog, '/').'/'.$_host_token_for_blog);
        }
        unset($_host_token_for_blog, $_host_base_dir_tokens_for_blog); // Housekeeping.

        foreach ($self->domainMappingBlogDomains() as $_domain_mapping_blog_domain) {
            if (($_domain_host_token_for_blog = $self->hostTokenForBlog(true, true, $_domain_mapping_blog_domain))) { // Dashify.
                $_domain_host_base_dir_tokens_for_blog = $self->hostBaseDirTokensForBlog(true, true); // Dashify. This is only a formality.
                $htmlc_cache_dirs[]                    = $self->wpContentBaseDirTo($self->htmlc_cache_sub_dir_public.rtrim($_domain_host_base_dir_tokens_for_blog, '/').'/'.$_domain_host_token_for_blog);
                $htmlc_cache_dirs[]                    = $self->wpContentBaseDirTo($self->htmlc_cache_sub_dir_private.rtrim($_domain_host_base_dir_tokens_for_blog, '/').'/'.$_domain_host_token_for_blog);
            }
        }
        unset($_domain_mapping_blog_domain, $_domain_host_token_for_blog, $_domain_host_base_dir_tokens_for_blog); // Housekeeping.
    }
    foreach (array_unique($htmlc_cache_dirs) as $_htmlc_cache_dir) {
        $counter += $self->deleteAllFilesDirsIn($_htmlc_cache_dir);
    }
    unset($_htmlc_cache_dir); // Just a little housekeeping.

    return $counter;
};
/*[/pro]*/
