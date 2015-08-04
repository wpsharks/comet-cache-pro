<?php
namespace WebSharks\ZenCache\Pro;

/*
 * Converts a URL into a relative `cache/path`; i.e. relative to the cache directory.
 *
 * @since 150422 Rewrite.
 *
 * @param string $url               The input URL to convert.
 * @param string $with_user_token   Optional user token (if applicable).
 * @param string $with_version_salt Optional version salt (if applicable).
 * @param int    $flags             Optional flags; a bitmask provided by `CACHE_PATH_*` constants.
 *
 * @return string The resulting relative `cache/path` based on the input `$url`; i.e. relative to the cache directory.
 */
$self->buildCachePath = function ($url, $with_user_token = '', $with_version_salt = '', $flags = CACHE_PATH_DEFAULT) use ($self) {
    $url               = trim((string) $url);
    $with_user_token   = trim((string) $with_user_token);
    $with_version_salt = trim((string) $with_version_salt);

    $is_multisite                = is_multisite();
    $is_advanced_cache           = $self->isAdvancedCache();
    $can_consider_domain_mapping = $self->canConsiderDomainMapping();

    $cache_path = ''; // Initialize cache path being built here.

    if ($flags & CACHE_PATH_CONSIDER_DOMAIN_MAPPING && $is_multisite && !$is_advanced_cache && $can_consider_domain_mapping) {
        if ($flags & CACHE_PATH_REVERSE_DOMAIN_MAPPING) {
            $url = $self->domainMappingReverseUrlFilter($url);
        } else {
            $url = $self->domainMappingUrlFilter($url);
        }
    }
    if (!$url || !($url = $self->parseUrl($url))) {
        return ($cache_path = ''); // Not possible.
    }
    if (empty($url['scheme']) || empty($url['host'])) {
        return ($cache_path = ''); // Not possible.
    }
    if (!($flags & CACHE_PATH_NO_SCHEME)) {
        if (!empty($url['scheme']) && $url['scheme'] !== '//') {
            $cache_path .= $url['scheme'].'/';
        } else {
            $cache_path .= $self->isSsl() ? 'https/' : 'http/';
        }
    }
    if (!($flags & CACHE_PATH_NO_HOST)) {
        if (!empty($url['host'])) {
            $cache_path .= $url['host'].'/';
        } elseif ($current_host) {
            $cache_path .= $current_host.'/';
        }
    }
    if (!($flags & CACHE_PATH_NO_PATH)) {
        if (isset($url['path'][201])) {
            $url['_path_tmp'] = '/'; // Initialize tmp path.
            foreach (explode('/', $url['path']) as $_path_component) {
                if (!isset($_path_component[0])) {
                    continue; // Empty.
                }
                if (isset($_path_component[201])) {
                    $_path_component = 'lpc-'.sha1($_path_component);
                }
                $url['_path_tmp'] .= $_path_component.'/';
            }
            $url['path'] = $url['_path_tmp']; // Shorter components.
            unset($_path_component, $url['_path_tmp']); // Housekeeping.

            if (isset($url['path'][2001])) {
                $url['path'] = '/lp-'.sha1($url['path']).'/';
            }
        }
        if (!empty($url['path']) && strlen($url['path'] = trim($url['path'], '\\/'." \t\n\r\0\x0B"))) {
            $cache_path .= $url['path'].'/'; // Add the path as it exists.

            // See: websharks/zencache#536 & `deleteFilesFromHostCacheDir()`
            // We should build an `index/` when this ends with a multisite root.
            //  e.g., `http/example-com[[/base]/child1]` instead of `http/example-com`
            if (!($flags & CACHE_PATH_NO_PATH_INDEX) && $is_multisite) { // Including a path index?
                if (($host_base_dir_tokens = $self->hostBaseDirTokens(false, $flags & CACHE_PATH_CONSIDER_DOMAIN_MAPPING, $url['path']))) {
                    if (strcasecmp(trim($host_base_dir_tokens, '/'), trim($url['path'], '/')) === 0) {
                        $cache_path .= 'index/';
                    }
                }
            }
        } elseif (!($flags & CACHE_PATH_NO_PATH_INDEX)) {
            $cache_path .= 'index/';
        }
    }
    if ($self->isExtensionLoaded('mbstring') && mb_check_encoding($cache_path, 'UTF-8')) {
        $cache_path = mb_strtolower($cache_path, 'UTF-8');
    }
    $cache_path = str_replace('.', '-', strtolower($cache_path));

    if (!($flags & CACHE_PATH_NO_QUV)) {
        if (!($flags & CACHE_PATH_NO_QUERY)) {
            if (isset($url['query']) && $url['query'] !== '') {
                $cache_path = rtrim($cache_path, '/').'.q/'.md5($url['query']).'/';
            }
        }
        if (!($flags & CACHE_PATH_NO_USER)) {
            if ($with_user_token !== '') {
                $cache_path = rtrim($cache_path, '/').'.u/'.str_replace(array('/', '\\'), '-', $with_user_token).'/';
            }
        }
        if (!($flags & CACHE_PATH_NO_VSALT)) {
            if ($with_version_salt !== '') {
                $cache_path = rtrim($cache_path, '/').'.v/'.str_replace(array('/', '\\'), '-', $with_version_salt).'/';
            }
        }
    }
    $cache_path = trim(preg_replace(array('/\/+/', '/\.+/'), array('/', '.'), $cache_path), '/');

    if ($flags & CACHE_PATH_ALLOW_WILDCARDS) {
        $cache_path = preg_replace('/[^a-z0-9\/.*]/i', '-', $cache_path);
    } else {
        $cache_path = preg_replace('/[^a-z0-9\/.]/i', '-', $cache_path);
    }
    if (!($flags & CACHE_PATH_NO_EXT)) {
        $cache_path .= '.html';
    }
    return $cache_path;
};

/*
 * Regex pattern for a call to `deleteFilesFromCacheDir()`.
 *
 * @since 150422 Rewrite. Updated 15xxxx w/ multisite compat. improvements.
 *
 * @param string $regex_frag A regex fragment. This CAN be left empty when necessary.
 *  If empty, the final regex pattern will be `/^'.$regex_suffix_frag.'/i`.
 *  If empty, it's a good idea to start `$regex_suffix_frag` with `.*?`.
 *
 * @param string $regex_suffix_frag Regex fragment to come after the `$regex_frag`.
 *  Defaults to: `(?:\/index)?(?:\.|\/(?:page\/[0-9]+|comment\-page\-[0-9]+)[.\/])`.
 *  Note: this should NOT have delimiters; i.e. do NOT start or end with `/`.
 *  See also: {@link CACHE_PATH_REGEX_DEFAULT_SUFFIX_FRAG}.
 *
 * @return string Regex pattern for a call to `deleteFilesFromCacheDir()`.
 */
$self->buildCachePathRegex = function ($regex_frag, $regex_suffix_frag = CACHE_PATH_REGEX_DEFAULT_SUFFIX_FRAG) use ($self) {
    $regex_frag        = (string) $regex_frag;
    $regex_suffix_frag = (string) $regex_suffix_frag;

    return '/^'.$regex_frag.$regex_suffix_frag.'/i';
};

/*
 * Regex pattern for a call to `deleteFilesFromHostCacheDir()`.
 *
 * This converts a URL into a relative `cache/path`; i.e. relative to the current host|blog directory,
 *    and then converts that into a regex pattern w/ an optional custom `$regex_suffix_frag`.
 *
 * @since 150422 Rewrite.
 *
 * @param string $url               The input URL to convert. This CAN be left empty when necessary.
 *                                  If empty, the final regex pattern will be `/^'.$regex_suffix_frag.'/i`.
 *                                  If empty, it's a good idea to start `$regex_suffix_frag` with `.*?`.
 *
 * @param string $regex_suffix_frag Regex fragment to come after the relative cache/path.
 *                                  Defaults to: `(?:\/index)?(?:\.|\/(?:page\/[0-9]+|comment\-page\-[0-9]+)[.\/])`.
 *                                  Note: this should NOT have delimiters; i.e. do NOT start or end with `/`.
 *                                  See also: {@link CACHE_PATH_REGEX_DEFAULT_SUFFIX_FRAG}.
 *
 * @return string Regex pattern for a call to `deleteFilesFromHostCacheDir()`.
 *
 * @note This variation of {@link build_cache_path()} automatically forces the following flags.
 *
 *       - {@link CACHE_PATH_NO_SCHEME}
 *       - {@link CACHE_PATH_NO_HOST}
 *       - {@link CACHE_PATH_NO_PATH_INDEX}
 *       - {@link CACHE_PATH_NO_QUV}
 *       - {@link CACHE_PATH_NO_EXT}
 */ // @TODO review for domain mapping compat and take advantage of recent improvements.
$self->buildHostCachePathRegex = function ($url, $regex_suffix_frag = CACHE_PATH_REGEX_DEFAULT_SUFFIX_FRAG) use ($self) {
    $url                           = trim((string) $url);
    $regex_suffix_frag             = (string) $regex_suffix_frag;
    $abs_relative_cache_path_regex = ''; // Initialize.

    if ($url) {
        $flags = CACHE_PATH_NO_SCHEME | CACHE_PATH_NO_HOST | CACHE_PATH_NO_PATH_INDEX | CACHE_PATH_NO_QUV | CACHE_PATH_NO_EXT;

        $host                 = $self->hostToken();
        $host_base_dir_tokens = $self->hostBaseDirTokens();
        $host_url             = rtrim('http://'.$host.$host_base_dir_tokens, '/');
        $host_cache_path      = $self->buildCachePath($host_url, '', '', $flags);

        $cache_path                    = $self->buildCachePath($url, '', '', $flags);
        $relative_cache_path           = preg_replace('/^'.preg_quote($host_cache_path, '/').'(?:\/|$)/i', '', $cache_path);
        $abs_relative_cache_path       = isset($relative_cache_path[0]) ? '/'.$relative_cache_path : '';
        $abs_relative_cache_path_regex = preg_quote($abs_relative_cache_path, '/');
    }
    return '/^'.$abs_relative_cache_path_regex.$regex_suffix_frag.'/i';
};

/*
 * Variation of {@link build_cache_path()} for relative regex fragments.
 *
 * This converts URIs into relative `cache/paths`; i.e. relative to the current host|blog directory,
 *    and then converts those into `(?:regex|fragments)` with piped `|` alternatives.
 *
 * @since 150422 Rewrite.
 *
 * @param string $uris              A line-delimited list of URIs. These may contain `*` wildcards also.
 *
 * @param string $regex_suffix_frag Regex fragment to come after each relative cache/path.
 *                                  Defaults to: `(?:\/index)?(?:\.|\/(?:page\/[0-9]+|comment\-page\-[0-9]+)[.\/])`.
 *                                  Note: this should NOT have delimiters; i.e. do NOT start or end with `/`.
 *                                  See also: {@link CACHE_PATH_REGEX_DEFAULT_SUFFIX_FRAG}.
 *
 * @return string The resulting `cache/paths` based on the input `$uris`; converted to `(?:regex|fragments)`.
 *
 * @note This variation of {@link build_cache_path()} automatically forces the following flags.
 *
 *       - {@link CACHE_PATH_ALLOW_WILDCARDS}
 *       - {@link CACHE_PATH_NO_SCHEME}
 *       - {@link CACHE_PATH_NO_HOST}
 *       - {@link CACHE_PATH_NO_PATH_INDEX}
 *       - {@link CACHE_PATH_NO_QUV}
 *       - {@link CACHE_PATH_NO_EXT}
 */// @TODO review for domain mapping compat and take advantage of recent improvements.
$self->buildHostCachePathRegexFragsFromWcUris = function ($uris, $regex_suffix_frag = CACHE_PATH_REGEX_DEFAULT_SUFFIX_FRAG) use ($self) {
    if (!($uris = trim((string) $uris))) {
        return ''; // Nothing to do.
    }
    $_self             = $self; // Reference for the closure below.
    $regex_suffix_frag = (string) $regex_suffix_frag; // Force a string value.
    $flags             = CACHE_PATH_ALLOW_WILDCARDS | CACHE_PATH_NO_SCHEME | CACHE_PATH_NO_HOST | CACHE_PATH_NO_PATH_INDEX | CACHE_PATH_NO_QUV | CACHE_PATH_NO_EXT;

    $host                 = $self->hostToken();
    $host_base_dir_tokens = $self->hostBaseDirTokens();
    $host_url             = rtrim('http://'.$host.$host_base_dir_tokens, '/');
    $host_cache_path      = $self->buildCachePath($host_url, '', '', $flags);

    return '(?:'.implode('|', array_map(function ($pattern) use ($_self, $regex_suffix_frag, $flags, $host_url, $host_cache_path) {
        $cache_path = $_self->buildCachePath($host_url.'/'.trim($pattern, '/'), '', '', $flags);
        $relative_cache_path = preg_replace('/^'.preg_quote($host_cache_path, '/').'(?:\/|$)/i', '', $cache_path);
        return preg_replace('/\\\\\*/', '.*?', preg_quote($relative_cache_path, '/')).$regex_suffix_frag;
    }, preg_split('/['."\r\n".']+/', $uris, null, PREG_SPLIT_NO_EMPTY))).')';
};
