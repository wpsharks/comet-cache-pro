<?php
/*[pro strip-from="lite"]*/
namespace WebSharks\ZenCache\Pro;

/*
 * Cache directory stats.
 *
 * @since 15xxxx Adding cache directory statistics.
 *
 * @param boolean $include_paths Include array of all scanned file paths?
 *
 * @return array Cache directory stats.
 */
$self->statsForCacheDir = function ($include_paths = false) use ($self) {
    return $self->getDirRegexStats($self->cacheDir(), '', $include_paths);
};

/*
 * HTML compressor cache directory stats.
 *
 * @since 15xxxx Adding cache directory statistics.
 *
 * @param boolean $include_paths Include array of all scanned file paths?
 *
 * @return array HTML compressor cache directory stats.
 */
$self->statsForHtmlCCacheDirs = function ($include_paths = false) use ($self) {
    $stats = array(); // Initialize the stats array.

    $htmlc_cache_dirs   = array(); // Initialize array directories.
    $htmlc_cache_dirs[] = $self->wpContentBaseDirTo($self->htmlc_cache_sub_dir_public);
    $htmlc_cache_dirs[] = $self->wpContentBaseDirTo($self->htmlc_cache_sub_dir_private);

    foreach (array_unique($htmlc_cache_dirs) as $_htmlc_cache_dir) {
        $_check_disk_stats = $stats ? false : true;

        foreach ($self->getDirRegexStats($_htmlc_cache_dir, '', $include_paths, $_check_disk_stats) as $_key => $_value) {
            $stats[$_key] = isset($stats[$_key]) ? $stats[$_key] + $_value : $_value;
        }
        unset($_key, $_value); // Housekeeping.
    }
    unset($_htmlc_cache_dir); // Final housekeeping.

    return $stats;
};

/*
 * Cache directory stats for the current host.
 *
 * @since 15xxxx Adding cache directory statistics.
 *
 * @param boolean $___considering_domain_mapping For internal use only.
 * @param boolean $___consider_domain_mapping_host_token For internal use only.
 * @param boolean $___consider_domain_mapping_host_base_dir_tokens For internal use only.
 *
 * @return array Cache directory stats for the current host.
 */
$self->statsForHostCacheDir = function ($___considering_domain_mapping = false,
                                        $___consider_domain_mapping_host_token = null,
                                        $___consider_domain_mapping_host_base_dir_tokens = null) use ($self) {

    $cache_dir            = $self->nDirSeps($cache_dir); // Normalize.
    $host_token           = $current_host_token           = $self->hostToken();
    $host_base_dir_tokens = $current_host_base_dir_tokens = $self->hostBaseDirTokens();

    if ($___considering_domain_mapping && isset($___consider_domain_mapping_host_token, $___consider_domain_mapping_host_base_dir_tokens)) {
        $host_token           = (string) $___consider_domain_mapping_host_token;
        $host_base_dir_tokens = (string) $___consider_domain_mapping_host_base_dir_tokens;
    }
    if (!$host_token) { // Must have a host in the sub-routine below.
        throw new \Exception(__('Invalid argument; host token empty!', SLUG_TD));
    }
    $stats = array(); // Initialize the stats array.

    foreach (array('http', 'https') as $_host_scheme) {
        $_host_url              = $_host_scheme.'://'.$host_token.$host_base_dir_tokens;
        $_host_cache_path_flags = CACHE_PATH_NO_PATH_INDEX | CACHE_PATH_NO_QUV | CACHE_PATH_NO_EXT;
        $_host_cache_path       = $self->buildCachePath($_host_url, '', '', $_host_cache_path_flags);
        $_host_cache_dir        = $self->nDirSeps($cache_dir.'/'.$_host_cache_path); // Normalize.
        $_check_disk_stats      = $stats || $___considering_domain_mapping ? false : true;

        foreach ($self->getDirRegexStats($_host_cache_dir, '', false, $_check_disk_stats) as $_key => $_value) {
            $stats[$_key] = isset($stats[$_key]) ? $stats[$_key] + $_value : $_value;
        }
        unset($_key, $_value); // Housekeeping.
    }
    unset($_host_scheme, $_host_url, $_host_cache_path_flags, $_host_cache_path, $_host_cache_dir, $_check_disk_stats);

    if (!$___considering_domain_mapping && is_multisite() && $self->canConsiderDomainMapping()) {
        $domain_mapping_variations = array(); // Initialize array of domain variations.

        if (($_host_token_for_blog = $self->hostTokenForBlog())) {
            $_host_base_dir_tokens_for_blog = $self->hostBaseDirTokensForBlog();
            $domain_mapping_variations[]    = array('host_token' => $_host_token_for_blog, 'host_base_dir_tokens' => $_host_base_dir_tokens_for_blog);
        } // The original blog host; i.e., without domain mapping.
        unset($_host_token_for_blog, $_host_base_dir_tokens_for_blog); // Housekeeping.

        foreach ($self->domainMappingBlogDomains() as $_domain_mapping_blog_domain) {
            if (($_domain_host_token_for_blog = $self->hostTokenForBlog(false, true, $_domain_mapping_blog_domain))) {
                $_domain_host_base_dir_tokens_for_blog = $self->hostBaseDirTokensForBlog(false, true); // This is only a formality.
                $domain_mapping_variations[]           = array('host_token' => $_domain_host_token_for_blog, 'host_base_dir_tokens' => $_domain_host_base_dir_tokens_for_blog);
            }
        } // This includes all of the domain mappings configured for the current blog ID.
        unset($_domain_mapping_blog_domain, $_domain_host_token_for_blog, $_domain_host_base_dir_tokens_for_blog); // Housekeeping.

        foreach ($domain_mapping_variations as $_domain_mapping_variation) {
            if ($_domain_mapping_variation['host_token'] === $current_host_token && $_domain_mapping_variation['host_base_dir_tokens'] === $current_host_base_dir_tokens) {
                continue; // Exclude current tokens. They were already iterated above.
            }
            foreach ($self->statsForHostCacheDir(true, $_domain_mapping_variation['host_token'], $_domain_mapping_variation['host_base_dir_tokens']) as $_key => $_value) {
                $stats[$_key] += $_value; // Increment stats for each domain mapping variation.
            }
            unset($_key, $_value); // Housekeeping.
        }
        unset($_domain_mapping_variation); // Housekeeping.
    }
    return $stats;
};

/*
 * HTML compressor cache directory stats for the current host.
 *
 * @since 15xxxx Adding cache directory statistics.
 *
 * @return array HTML compressor cache directory stats for the current host.
 */
$self->statsForHtmlCHostCacheDirs = function () use ($self) {
    $stats = array(); // Initialize the stats array.

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
        $_check_disk_stats = $stats ? false : true;

        foreach ($self->getDirRegexStats($_htmlc_cache_dir, '', false, $_check_disk_stats) as $_key => $_value) {
            $stats[$_key] = isset($stats[$_key]) ? $stats[$_key] + $_value : $_value;
        }
        unset($_key, $_value); // Housekeeping.
    }
    unset($_htmlc_cache_dir); // Just a little housekeeping.

    return $stats;
};
/*[/pro]*/
