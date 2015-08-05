<?php
namespace WebSharks\ZenCache\Pro;

/*
 * Converts a URL into a `cache/path` based on input `$flags`.
 *
 * @since 150422 Rewrite. Updated 15xxxx w/ multisite compat. improvements.
 *
 * @param string $url               The input URL to convert.
 * @param string $with_user_token   Optional user token (if applicable).
 * @param string $with_version_salt Optional version salt (if applicable).
 * @param int    $flags             Optional flags. A bitmask via `CACHE_PATH_*` constants.
 *
 * @return string The resulting `cache/path` based on the input `$url` & `$flags`.
 */
$self->buildCachePath = function ($url, $with_user_token = '', $with_version_salt = '', $flags = CACHE_PATH_DEFAULT) use ($self) {
    $url               = trim((string) $url);
    $with_user_token   = trim((string) $with_user_token);
    $with_version_salt = trim((string) $with_version_salt);

    $is_multisite                = is_multisite();
    $can_consider_domain_mapping = $is_multisite && $self->canConsiderDomainMapping();
    $cache_path                  = ''; // Initialize cache path being built here.

    if ($flags & CACHE_PATH_CONSIDER_DOMAIN_MAPPING && $is_multisite && $can_consider_domain_mapping) {
        if ($flags & CACHE_PATH_REVERSE_DOMAIN_MAPPING) {
            $url = $self->domainMappingReverseUrlFilter($url);
        } else {
            $url = $self->domainMappingUrlFilter($url);
        }
    }
    if (!$url || !($url_parts = $self->parseUrl($url))) {
        return ($cache_path = ''); // Not possible.
    }
    if (empty($url_parts['scheme']) || $url_parts['scheme'] === '//') {
        return ($cache_path = ''); // Not possible.
    }
    if (empty($url_parts['host'])) {
        return ($cache_path = ''); // Not possible.
    }
    $url_parts['original_path'] = isset($url_parts['path']) ? $url_parts['path'] : '';

    if (!($flags & CACHE_PATH_NO_SCHEME)) {
        $cache_path .= $url_parts['scheme'].'/';
    }
    if (!($flags & CACHE_PATH_NO_HOST)) {
        $cache_path .= $url_parts['host'].'/';
    }
    if (!($flags & CACHE_PATH_NO_PATH)) {
        if (isset($url_parts['path'][201])) {
            $url_parts['_path_tmp'] = '/'; // Initialize tmp path.
            foreach (explode('/', $url_parts['path']) as $_path_component) {
                if (!isset($_path_component[0])) {
                    continue; // Empty.
                }
                if (isset($_path_component[201])) {
                    $_path_component = 'lpc-'.sha1($_path_component);
                }
                $url_parts['_path_tmp'] .= $_path_component.'/';
            }
            $url_parts['path'] = $url_parts['_path_tmp']; // Shorter components.
            unset($_path_component, $url_parts['_path_tmp']); // Housekeeping.

            if (isset($url_parts['path'][2001])) {
                $url_parts['path'] = '/lp-'.sha1($url_parts['path']).'/';
            }
        } // Now add the path and check for a possible root `index/` suffix.
        if (!empty($url_parts['path']) && strlen($url_parts['path'] = trim($url_parts['path'], '\\/'." \t\n\r\0\x0B"))) {
            $cache_path .= $url_parts['path'].'/'; // Add the path as it exists.

            if (!($flags & CACHE_PATH_NO_PATH_INDEX) && $is_multisite) {
                // We should build an `index/` when this ends with a multisite root.
                //  e.g., `http/example-com[[/base]/child1]` instead of `http/example-com`
                // We use `$url_parts['original_path']` here, since it could have been shortened above.
                $is_url_domain_mapped = $can_consider_domain_mapping && $self->domainMappingBlogId($url);
                if (!$is_url_domain_mapped && ($host_base_dir_tokens = $self->hostBaseDirTokens(false, false, $url_parts['original_path']))) {
                    if (strcasecmp(trim($host_base_dir_tokens, '/'), trim($url_parts['original_path'], '/')) === 0) {
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
            if (isset($url_parts['query']) && $url_parts['query'] !== '') {
                $cache_path = rtrim($cache_path, '/').'.q/'.md5($url_parts['query']).'/';
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
 * @since 150422 Rewrite. Updated 15xxxx w/ multisite compat. improvements.
 *
 * @param string $url The input URL to convert. This CAN be left empty when necessary.
 *   If empty, the final regex pattern will be `/^'.$regex_suffix_frag.'/i`.
 *   If empty, it's a good idea to start `$regex_suffix_frag` with `.*?`.
 *
 * @param string $regex_suffix_frag Regex fragment to come after the relative cache/path regex frag.
 *   Defaults to: `(?:\/index)?(?:\.|\/(?:page\/[0-9]+|comment\-page\-[0-9]+)[.\/])`.
 *   Note: this should NOT have delimiters; i.e. do NOT start or end with `/`.
 *   See also: {@link CACHE_PATH_REGEX_DEFAULT_SUFFIX_FRAG}.
 *
 * @return string Regex pattern for a call to `deleteFilesFromHostCacheDir()`.
 */
$self->buildHostCachePathRegex = function ($url, $regex_suffix_frag = CACHE_PATH_REGEX_DEFAULT_SUFFIX_FRAG) use ($self) {
    $url                           = trim((string) $url);
    $regex_suffix_frag             = (string) $regex_suffix_frag;
    $abs_relative_cache_path_regex = ''; // Initialize regex.
    $is_url_domain_mapped          = false; // Initialize.

    if ($url) {
        if (is_multisite() && $self->canConsiderDomainMapping()) {
            // Shortest possible URI; i.e., consider domain mapping.
            $url                  = $self->domainMappingUrlFilter($url);
            $is_url_domain_mapped = $url && $self->domainMappingBlogId($url);
        }
        if ($url && ($url_parts = $self->parseUrl($url)) && !empty($url_parts['host'])) {
            $flags = CACHE_PATH_NO_SCHEME | CACHE_PATH_NO_HOST | CACHE_PATH_NO_PATH_INDEX | CACHE_PATH_NO_QUV | CACHE_PATH_NO_EXT;

            $host_base_dir_tokens = $self->hostBaseDirTokens(false, $is_url_domain_mapped, !empty($url_parts['path']) ? $url_parts['path'] : '/');
            $host_url             = rtrim('http://'.$url_parts['host'].$host_base_dir_tokens, '/');
            $host_cache_path      = $self->buildCachePath($host_url, '', '', $flags);

            $cache_path          = $self->buildCachePath($url, '', '', $flags);
            $relative_cache_path = preg_replace('/^'.preg_quote($host_cache_path, '/').'(?:\/|$)/i', '', $cache_path);

            $abs_relative_cache_path       = isset($relative_cache_path[0]) ? '/'.$relative_cache_path : '';
            $abs_relative_cache_path_regex = isset($abs_relative_cache_path[0]) ? preg_quote($abs_relative_cache_path, '/') : '';
        }
    }
    return '/^'.$abs_relative_cache_path_regex.$regex_suffix_frag.'/i';
};

/*
 * Regex pattern for a call to `deleteFilesFromHostCacheDir()`.
 *
 * @since 150422 Rewrite. Updated 15xxxx w/ multisite compat. improvements.
 *
 * @param string $uris A line-delimited list of URIs. These may contain `*` wildcards also.
 *
 * @param string $regex_suffix_frag Regex fragment to come after each relative cache/path.
 *   Defaults to: `(?:\/index)?(?:\.|\/(?:page\/[0-9]+|comment\-page\-[0-9]+)[.\/])`.
 *   Note: this should NOT have delimiters; i.e. do NOT start or end with `/`.
 *   See also: {@link CACHE_PATH_REGEX_DEFAULT_SUFFIX_FRAG}.
 *
 * @return string Regex pattern for a call to `deleteFilesFromHostCacheDir()`.
 */
$self->buildHostCachePathRegexFragsFromWcUris = function ($uris, $regex_suffix_frag = CACHE_PATH_REGEX_DEFAULT_SUFFIX_FRAG) use ($self) {
    $uris              = trim((string) $uris);
    $regex_suffix_frag = (string) $regex_suffix_frag;

    $_self = $self; // Reference for the closure below.
    $flags = CACHE_PATH_ALLOW_WILDCARDS | CACHE_PATH_NO_SCHEME | CACHE_PATH_NO_HOST
        | CACHE_PATH_NO_PATH_INDEX | CACHE_PATH_NO_QUV | CACHE_PATH_NO_EXT;

    $host            = 'doesnt-matter.foo.bar';
    $host_url        = rtrim('http://'.$host, '/');
    $host_cache_path = $self->buildCachePath($host_url, '', '', $flags);
    $uri_patterns    = array_unique(preg_split('/['."\r\n".']+/', $uris, null, PREG_SPLIT_NO_EMPTY));

    foreach ($uri_patterns as $_key => &$_uri_pattern) {
        if (($_uri_pattern = trim($_uri_pattern))) {
            $_cache_path          = $_self->buildCachePath($host_url.'/'.trim($_uri_pattern, '/'), '', '', $flags);
            $_relative_cache_path = preg_replace('/^'.preg_quote($host_cache_path, '/').'(?:\/|$)/i', '', $_cache_path);
            $_uri_pattern         = preg_replace('/\\\\\*/', '.*?', preg_quote($_relative_cache_path, '/'));
        }
        if (!$_uri_pattern) {
            unset($uri_patterns[$_key]); // Remove it.
        }
    }
    unset($_key, $_uri_pattern, $_cache_path, $_relative_cache_path); // Housekeeping.

    return $uri_patterns ? '(?:'.implode('|', array_unique($uri_patterns)).')'.$regex_suffix_frag : '';
};
