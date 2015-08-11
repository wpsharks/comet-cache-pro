<?php
/*[pro strip-from="lite"]*/
namespace WebSharks\ZenCache\Pro;

// @TODO Cache these statistics.

/*
 * Cache directory stats.
 *
 * @since 15xxxx Adding cache directory statistics.
 *
 * @param boolean $no_cache Do not read or write a cache entry?
 * @param boolean $include_paths Include array of all scanned file paths?
 *
 * @return array Cache directory stats.
 *
 * @TODO Optimize this for multisite networks w/ a LOT of child blogs.
 * @TODO Optimize this for extremely large sites. A LOT of files here could slow things down.
 *  See also: <https://codex.wordpress.org/Function_Reference/wp_is_large_network>
 */
$self->statsForCacheDir = function ($no_cache = false, $include_paths = false) use ($self) {
    if (!$no_cache) { // Allow a cached set of stats?
        $site_option_cache_key = GLOBAL_NS.'_dir_stats_'; // Identifying prefix.
        $site_option_cache_key .= md5('statsForCacheDir'.(integer) $include_paths);

        if (is_array($cached = get_site_option($site_option_cache_key)) // Cache exists?
                && isset($cached['stats'], $cached['time']) && is_array($cached['stats'])
                && $cached['time'] >= strtotime('-'.$self->options['dir_stats_refresh_time'])) {
            return $cached['stats']; // Return cached stats.
        }
    } // Otherwise, we need to pull a fresh set of stats.
    $stats = $self->getDirRegexStats($self->cacheDir(), '', $include_paths, true, true);

    if (!$no_cache && !empty($site_option_cache_key)) { // Cache these stats?
        update_site_option($site_option_cache_key, array('stats' => $stats, 'time' => time()));
    }
    return $stats;
};

/*
 * HTML compressor cache directory stats.
 *
 * @since 15xxxx Adding cache directory statistics.
 *
 * @param boolean $no_cache Do not read or write a cache entry?
 * @param boolean $include_paths Include array of all scanned file paths?
 *
 * @return array HTML compressor cache directory stats.
 *
 * @TODO Optimize this for multisite networks w/ a LOT of child blogs.
 * @TODO Optimize this for extremely large sites. A LOT of files here could slow things down.
 *  See also: <https://codex.wordpress.org/Function_Reference/wp_is_large_network>
 */
$self->statsForHtmlCCacheDirs = function ($no_cache = false, $include_paths = false) use ($self) {
    if (!$no_cache) { // Allow a cached set of stats?
        $site_option_cache_key = GLOBAL_NS.'_dir_stats_'; // Identifying prefix.
        $site_option_cache_key .= md5('statsForHtmlCCacheDirs'.(integer) $include_paths);

        if (is_array($cached = get_site_option($site_option_cache_key)) // Cache exists?
                && isset($cached['stats'], $cached['time']) && is_array($cached['stats'])
                && $cached['time'] >= strtotime('-'.$self->options['dir_stats_refresh_time'])) {
            return $cached['stats']; // Return cached stats.
        }
    } // Otherwise, we need to pull a fresh set of stats.

    $stats = array(); // Initialize stats array.

    $htmlc_cache_dirs   = array(); // Initialize array directories.
    $htmlc_cache_dirs[] = $self->wpContentBaseDirTo($self->htmlc_cache_sub_dir_public);
    $htmlc_cache_dirs[] = $self->wpContentBaseDirTo($self->htmlc_cache_sub_dir_private);

    foreach (array_unique($htmlc_cache_dirs) as $_htmlc_cache_dir) {
        $_check_disk_stats = $stats ? false : true;

        foreach ($self->getDirRegexStats($_htmlc_cache_dir, '', $include_paths, $_check_disk_stats, true) as $_key => $_value) {
            $stats[$_key] = isset($stats[$_key]) ? $stats[$_key] + $_value : $_value;
        }
        unset($_key, $_value); // Housekeeping.
    }
    unset($_htmlc_cache_dir); // Final housekeeping.

    if (!$no_cache && !empty($site_option_cache_key)) { // Cache these stats?
        update_site_option($site_option_cache_key, array('stats' => $stats, 'time' => time()));
    }
    return $stats;
};

/*
 * Cache directory stats for the current host.
 *
 * @since 15xxxx Adding cache directory statistics.
 *
 * @param boolean $no_cache Do not read or write a cache entry?
 *
 * @param boolean $___considering_domain_mapping For internal use only.
 * @param boolean $___consider_domain_mapping_host_token For internal use only.
 * @param boolean $___consider_domain_mapping_host_base_dir_tokens For internal use only.
 *
 * @return array Cache directory stats for the current host.
 */
$self->statsForHostCacheDir = function (
    $no_cache = false,
    $___considering_domain_mapping = false,
    $___consider_domain_mapping_host_token = null,
    $___consider_domain_mapping_host_base_dir_tokens = null
) use ($self) {

    if (!$no_cache) { // Allow a cached set of stats?
        if (is_multisite()) {
            $site_option_cache_key = GLOBAL_NS.'_dir_stats_'; // Identifying prefix.
        } else {
            $site_option_cache_key = GLOBAL_NS.'_dir_stats_'; // Identifying prefix.
        }
        $site_option_cache_key .= md5('statsForHostCacheDir'.(integer) $include_paths);

        if (is_array($cached = get_site_option($site_option_cache_key)) // Cache exists?
                && isset($cached['stats'], $cached['time']) && is_array($cached['stats'])
                && $cached['time'] >= strtotime('-'.$self->options['dir_stats_refresh_time'])) {
            return $cached['stats']; // Return cached stats.
        }
    } // Otherwise, we need to pull a fresh set of stats.

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

        foreach ($self->getDirRegexStats($_host_cache_dir, '', false, $_check_disk_stats, $no_cache) as $_key => $_value) {
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
            foreach ($self->statsForHostCacheDir($no_cache, true, $_domain_mapping_variation['host_token'], $_domain_mapping_variation['host_base_dir_tokens']) as $_key => $_value) {
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
 * @param boolean $no_cache Do not read or write a cache entry?
 *
 * @return array HTML compressor cache directory stats for the current host.
 */
$self->statsForHtmlCHostCacheDirs = function ($no_cache = false) use ($self) {
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

        foreach ($self->getDirRegexStats($_htmlc_cache_dir, '', false, $_check_disk_stats, $no_cache) as $_key => $_value) {
            $stats[$_key] = isset($stats[$_key]) ? $stats[$_key] + $_value : $_value;
        }
        unset($_key, $_value); // Housekeeping.
    }
    unset($_htmlc_cache_dir); // Just a little housekeeping.

    return $stats;
};

/*
 * Directory stats.
 *
 * @since 15xxxx Adding a few statistics.
 *
 * @param string $dir An absolute server directory path.
 * @param string $regex A regex pattern; compares to each full file path.
 * @param boolean $include_paths Include array of all scanned file paths?
 * @param boolean $check_disk Also check disk statistics?
 * @param boolean $no_cache Do not read/write cache?
 *
 * @return array Directory stats.
 */
$self->getDirRegexStats = function ($dir, $regex = '', $include_paths = false, $check_disk = true, $no_cache = false) use ($self) {
    $dir        = (string) $dir; // Force string.
    $cache_keys = array($dir, $regex, $include_paths, $check_disk);
    if (!$no_cache && !is_null($stats = &$self->staticKey('getDirRegexStats', $cache_keys))) {
        return $stats; // Already cached this.
    }
    $stats = array(
        'dir'        => $dir,
        'total_size' => 0,

        'total_links'   => 0,
        'link_subpaths' => array(),

        'total_files'   => 0,
        'file_subpaths' => array(),

        'total_dirs'   => 0,
        'dir_subpaths' => array(),

        'disk_free_space'  => 0,
        'disk_total_space' => 0,
    );
    if (!$dir || !is_dir($dir) || !$self->options['dir_stats_enable']) {
        return $stats; // Not possible.
    }
    foreach ($self->dirRegexIteration($dir, $regex) as $_resource) {
        switch ($_resource->getType()) { // `link`, `file`, `dir`.
            case 'link':
                if ($include_paths) {
                    $stats['link_subpaths'][] = $_resource->getSubpathname();
                }
                ++$stats['total_links']; // Counter.

                break; // Break switch handler.

            case 'file':
                if ($include_paths) {
                    $stats['file_subpaths'][] = $_resource->getSubpathname();
                }
                $stats['total_size'] += $_resource->getSize();
                ++$stats['total_files']; // Counter.

                break; // Break switch.

            case 'dir':
                if ($include_paths) {
                    $stats['dir_subpaths'][] = $_resource->getSubpathname();
                }
                ++$stats['total_dirs']; // Counter.

                break; // Break switch.
        }
    }
    unset($_resource); // Housekeeping.

    if ($check_disk) { // Check disk also?
        $stats['disk_free_space']  = disk_free_space($dir);
        $stats['disk_total_space'] = disk_total_space($dir);
    }
    return $stats;
};
/*[/pro]*/
