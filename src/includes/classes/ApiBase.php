<?php
namespace WebSharks\CometCache\Pro\Classes;

/**
 * API Base Class.
 *
 * @since 150422 Rewrite.
 */
class ApiBase
{
    /**
     * Current CC plugin instance.
     *
     * @since 150422 Rewrite.
     *
     * @return \comet_cache\plugin instance.
     */
    public static function plugin()
    {
        return $GLOBALS[GLOBAL_NS];
    }

    /**
     * Gives you the current version string.
     *
     * @since 150422 Rewrite.
     *
     * @return string Current version string.
     */
    public static function version()
    {
        return VERSION; // Via constant.
    }

    /**
     * Gives you the current array of configured options.
     *
     * @since 150422 Rewrite.
     *
     * @return array Current array of options.
     */
    public static function options()
    {
        return $GLOBALS[GLOBAL_NS]->options;
    }

    /*[pro strip-from="lite"]*/

    /**
     * UA (User-Agent) info.
     *
     * @since 161221 Mobile-adaptive salt.
     *
     * @param string|null $ua User-Agent (optional).
     * @note Defaults to `$_SERVER['HTTP_USER_AGENT']`.
     *
     * @return array UA info (or empty array on failure).
     *
     * The array will contain the following keys:
     *
     * - `os.name` = iOS, Android, WinPhone10, WinPhone8.1, etc.
     *
     * - `device.type` = Tablet, Mobile Device, Mobile Phone, etc.
     * - `device.is_mobile` = True if a mobile device (e.g., tablet|phone).
     *
     * - `browser.name` = Safari, Mobile Safari UIWebView, Chrome, Android WebView, Firefox, Edge Mobile, IEMobile, IE, Coast, etc.
     * - `browser.version.major` = 55, 1, 9383242, etc. Only the major version number.
     * - `browser.version` = 55.0, 1.3, 9383242.2392, etc. Major & minor versions.
     *
     * @note Use of this utility requires PHP 5.6+ (7.0+ suggested).
     */
    public static function uaInfo($ua = null)
    {
        return $GLOBALS[GLOBAL_NS]->getUaInfo($ua);
    }

    /**
     * UA (User-Agent) is mobile?
     *
     * @param string|null $ua User-Agent (optional).
     * @note Defaults to `$_SERVER['HTTP_USER_AGENT']`.
     *
     * @since 161221 Mobile-adaptive salt.
     *
     * @return true True if is mobile.
     *
     * @note Requires PHP 5.6+ (7.0+ suggested).
     */
    public static function uaIsMobile($ua = null)
    {
        return $GLOBALS[GLOBAL_NS]->uaIsMobile($ua);
    }

    /*[/pro]*/

    /**
     * Purges expired cache files, leaving all others intact.
     *
     * @since 150422 Rewrite.
     *
     * @note This occurs automatically over time via WP Cron;
     *    but this will force an immediate purge if you so desire.
     *
     * @return int Total files purged (if any).
     */
    public static function purge()
    {
        return $GLOBALS[GLOBAL_NS]->purgeCache();
    }

    /**
     * This erases the entire cache for the current blog.
     *
     * @since 150422 Rewrite.
     *
     * @note In a multisite network this impacts only the current blog,
     *    it does not clear the cache for other child blogs.
     *
     * @return int Total files cleared (if any).
     */
    public static function clear()
    {
        return $GLOBALS[GLOBAL_NS]->clearCache();
    }

    /**
     * This erases the cache for a specific post ID.
     *
     * @since 150626 Adding support for new API methods.
     *
     * @param int $post_id Post ID.
     *
     * @return int Total files cleared (if any).
     */
    public static function clearPost($post_id)
    {
        return $GLOBALS[GLOBAL_NS]->autoClearPostCache($post_id);
    }

    /**
     * This clears the cache for a specific URL.
     *
     * @since 151114 Adding support for custom URLs.
     *
     * @param string $url Input URL to clear.
     *
     * @return int Total files cleared (if any).
     */
    public static function clearUrl($url)
    {
        $regex = $GLOBALS[GLOBAL_NS]->buildCachePathRegexFromWcUrl($url);

        return $GLOBALS[GLOBAL_NS]->deleteFilesFromCacheDir($regex);
    }

    /*[pro strip-from="lite"]*/

    /**
     * This erases the cache for a specific user ID.
     *
     * @since 150626 Adding support for new API methods.
     *
     * @param int $user_id User ID.
     *
     * @return int Total files cleared (if any).
     */
    public static function clearUser($user_id)
    {
        return $GLOBALS[GLOBAL_NS]->autoClearUserCache($user_id);
    }

    /**
     * This erases the cache for the current user.
     *
     * @since 150626 Adding support for new API methods.
     *
     * @return int Total files cleared (if any).
     */
    public static function clearCurrentUser()
    {
        return $GLOBALS[GLOBAL_NS]->autoClearUserCacheCur();
    }

    /*[/pro]*/

    /**
     * This wipes out the entire cache.
     *
     * @since 150422 Rewrite.
     *
     * @note On a standard WP installation this is the same as comet_cache::clear();
     *    but on a multisite installation it impacts the entire network
     *    (i.e. wipes the cache for all blogs in the network).
     *
     * @return int Total files wiped (if any).
     */
    public static function wipe()
    {
        return $GLOBALS[GLOBAL_NS]->wipeCache();
    }
}
