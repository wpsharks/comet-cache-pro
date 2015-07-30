<?php
namespace WebSharks\ZenCache\Pro;

if (defined(__NAMESPACE__.'\\CACHE_PATH_DEFAULT')) {
    return; // Already defined these.
}
/**
 * Default cache path flags.
 *
 * @since 150422 Rewrite.
 *
 * @type int A bitmask.
 */
const CACHE_PATH_DEFAULT = 0;

/**
 * Allow a domain-mapped cache path.
 *
 * @since 15xxxx Improving multisite compat.
 *
 * @type int Part of a bitmask.
 */
const CACHE_PATH_NO_DOMAIN_MAPPING = 1;

/**
 * Exclude scheme from cache path.
 *
 * @since 150422 Rewrite.
 *
 * @type int Part of a bitmask.
 */
const CACHE_PATH_NO_SCHEME = 2;

/**
 * Exclude host (i.e. domain name) from cache path.
 *
 * @since 150422 Rewrite.
 *
 * @type int Part of a bitmask.
 */
const CACHE_PATH_NO_HOST = 4;

/**
 * Exclude path from cache path.
 *
 * @since 150422 Rewrite.
 *
 * @type int Part of a bitmask.
 */
const CACHE_PATH_NO_PATH = 8;

/**
 * Exclude path index (i.e. no default `index`) from cache path.
 *
 * @since 150422 Rewrite.
 *
 * @type int Part of a bitmask.
 */
const CACHE_PATH_NO_PATH_INDEX = 16;

/**
 * Exclude query, user & version salt from cache path.
 *
 * @since 150422 Rewrite.
 *
 * @type int Part of a bitmask.
 */
const CACHE_PATH_NO_QUV = 32;

/**
 * Exclude query string from cache path.
 *
 * @since 150422 Rewrite.
 *
 * @type int Part of a bitmask.
 */
const CACHE_PATH_NO_QUERY = 64;

/**
 * Exclude user token from cache path.
 *
 * @since 150422 Rewrite.
 *
 * @type int Part of a bitmask.
 */
const CACHE_PATH_NO_USER = 128;

/**
 * Exclude version salt from cache path.
 *
 * @since 150422 Rewrite.
 *
 * @type int Part of a bitmask.
 */
const CACHE_PATH_NO_VSALT = 256;

/**
 * Exclude extension from cache path.
 *
 * @since 150422 Rewrite.
 *
 * @type int Part of a bitmask.
 */
const CACHE_PATH_NO_EXT = 512;

/**
 * Allow wildcards in the cache path.
 *
 * @since 150422 Rewrite.
 *
 * @type int Part of a bitmask.
 */
const CACHE_PATH_ALLOW_WILDCARDS = 1024;

/**
 * Default cache path regex suffix frag.
 *
 * @since 150422 Rewrite.
 *
 * @type string Default regex suffix frag used in cache path patterns.
 */
const CACHE_PATH_REGEX_DEFAULT_SUFFIX_FRAG = '(?:\/index)?(?:\.|\/(?:page\/[0-9]+|comment\-page\-[0-9]+)[.\/])';
