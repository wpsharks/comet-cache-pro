<?php
/*[pro exclude-file-from="lite"]*/
/*[pro strip-from="lite"]*/
namespace WebSharks\CometCache\Pro\Traits\Shared;

use BrowscapPHP\Browscap;
use BrowscapPHP\BrowscapUpdater;
use WurflCache\Adapter\File as WurflCache;
use BrowscapPHP\Helper\IniLoader as BrowscapIniLoader;
use UserAgentParser\Provider\BrowscapLite as BrowscapLiteParser;
use WebSharks\CometCache\Pro\Classes;

trait UaUtils
{
    /**
     * UA info directory.
     *
     * @since 161221 Mobile-adaptive salt.
     *
     * @param string $rel_path Relative path inside directory.
     *
     * @throws \Exception If unable to get info directory.
     *
     * @return string Absolute path to info directory.
     */
    public function uaInfoDir($rel_path = '')
    {
        $rel_path = (string) $rel_path;

        if ($this->isAdvancedCache() && defined('COMET_CACHE_UA_INFO_DIR')) {
            $info_dir = COMET_CACHE_UA_INFO_DIR;
        } elseif ($this->isPlugin() && !empty($this->ua_info_sub_dir)) {
            $info_dir = $this->wpContentBaseDirTo($this->ua_info_sub_dir);
        }
        if (empty($info_dir)) {
            throw new \Exception(__('Unable to determine UA info directory location.', SLUG_TD));
        }
        return rtrim($info_dir, '/').($rel_path ? '/'.ltrim($rel_path) : '');
    }

    /**
     * Gets UA info.
     *
     * @since 161221 Mobile-adaptive salt.
     *
     * @param string|null $ua                         User-Agent (optional).
     * @param bool        $throw_exception_on_failure Throw on failure?
     *
     * @throws \Exception On failure to parse the UA.
     *
     * @return array User-Agent info.
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
    public function getUaInfo($ua = null, $throw_exception_on_failure = false)
    {
        if (!isset($ua)) { // Default = current UA.
            if (!empty($_SERVER['HTTP_USER_AGENT'])) {
                $ua = (string) $_SERVER['HTTP_USER_AGENT'];
            } // Force string value in case of server misconfig.
        }
        if (!$ua) { // Missing UA to check?
            return $info = []; // Not possible.
        } elseif (is_array($info = &$this->staticKey(__FUNCTION__, $ua))) {
            return $info; // Cached already.
        } elseif (!version_compare(PHP_VERSION, '5.6', '>=')) {
            return $info = []; // Not possible.
        } elseif (!is_dir($browscap_dir = $this->uaInfoDir('/browscap'))) {
            return $info = []; // Not possible.
        }
        try { // e.g., If unable to parse UA.
            $Browscap           = new Browscap();
            $Browscap->setCache(new WurflCache([WurflCache::DIR => $browscap_dir]));
            $BrowscapLiteParser = new BrowscapLiteParser($Browscap);
            $UserAgent          = $BrowscapLiteParser->parse($_SERVER['HTTP_USER_AGENT']);

            return $info = [ // `token` => `value` (array|bool|string).
                'os.name' => (string) $UserAgent->getOperatingSystem()->getName(),

                'device.type'      => (string) $UserAgent->getDevice()->getType(),
                'device.is_mobile' => (bool) $UserAgent->getDevice()->getIsMobile(),

                'browser.name'          => (string) $UserAgent->getBrowser()->getName(),
                'browser.version.major' => (string) $UserAgent->getBrowser()->getVersion()->getMajor(),
                'browser.version'       => trim($UserAgent->getBrowser()->getVersion()->getMajor().'.'.$UserAgent->getBrowser()->getVersion()->getMinor(), '.'),
            ];
        } catch (\Exception $Exception) {
            if ($throw_exception_on_failure) {
                throw $Exception;
            } // Else use soft failure.
            return $info = []; // Not possible.
        }
    }

    /**
     * UA is mobile?
     *
     * @since 161221 Mobile-adaptive salt.
     *
     * @param string|null $ua                         User-Agent (optional).
     * @param bool        $throw_exception_on_failure Throw on failure?
     *
     * @throws \Exception On failure to parse the UA.
     *
     * @return bool True if UA is mobile.
     *
     * @note Use of this utility requires PHP 5.6+ (7.0+ suggested).
     */
    public function uaIsMobile($ua = null, $throw_exception_on_failure = false)
    {
        $info      = $this->getUaInfo($ua, $throw_exception_on_failure);
        return $is = !empty($info['device.is_mobile']);
    }

    /**
     * Fills UA string tokens.
     *
     * @since 161221 Mobile-adaptive salt.
     *
     * @param string      $tokens                     UA info tokens.
     * @param bool        $as_path                    As path component?
     * @param string|null $ua                         User-Agent (optional).
     * @param bool        $throw_exception_on_failure Throw on failure?
     *
     * @throws \Exception On failure to parse the UA.
     *
     * @return string The `$tokens` having been filled in.
     *
     * @note Use of this utility requires PHP 5.6+ (7.0+ suggested).
     */
    public function fillUaTokens($tokens, $as_path = true, $ua = null, $throw_exception_on_failure = false)
    {
        if (!($tokens = (string) $tokens)) {
            return $tokens; // Nothing to do.
        }
        foreach ($this->getUaInfo($ua, $throw_exception_on_failure) as $_token => $_value) {
            if ($as_path) { // Cache directory.
                if ($_token === 'device.is_mobile') {
                    $tokens = str_replace($_token, $_value ? 'mobile' : '', $tokens);
                } else {
                    $tokens = str_replace($_token, preg_replace('/[^a-z0-9\-]/ui', '-', mb_strtolower($_value)), $tokens);
                }
            } else { // Human readable.
                if ($_token === 'device.is_mobile') {
                    $tokens = str_replace($_token, $_value ? 'Mobile' : '', $tokens);
                } else {
                    $tokens = str_replace($_token, $_value, $tokens);
                }
            }
        } // unset($_token, $_value); // Housekeeping.

        if ($as_path) { // Disallow special chars.
            $tokens        = preg_replace('/\s+/u', '', $tokens);
            $tokens        = preg_replace('/[^a-z0-9+\-]/ui', '-', $tokens);
            // Important NOT to trim `+` separators away in this scenario.
            // Doing so would break the overall logic behind cache locations.
            return $tokens = trim($tokens, " \r\n\t\0\x0B-");
        } else {
            return $tokens = trim($tokens, " \r\n\t\0\x0B-+");
        }
    }

    /**
     * Creates/populates UA info directory.
     *
     * @since 161221 Mobile-adaptive salt.
     *
     * @param bool $throw_exception_on_failure Throw on failure?
     *
     * @throws \Exception If unable to get info directory.
     *
     * @note This downloads, compiles, and caches the latest:
     * <https://browscap.org/stream?q=Lite_PHP_BrowsCapINI>
     *
     * @note Use of this utility requires PHP 5.6+ (7.0+ suggested).
     */
    public function populateUaInfoDirectory($throw_exception_on_failure = false)
    {
        if (!version_compare(PHP_VERSION, '5.6', '>=')) {
            return; // Not possible.
        }
        $cache_lock   = $this->cacheLock();
        $ua_info_dir  = $this->uaInfoDir();
        $browscap_dir = $ua_info_dir.'/browscap';

        clearstatcache(); // Clear `stat()` cache.

        if (!file_exists($browscap_dir)) {
            mkdir($browscap_dir, 0775, true);
        }
        try { // e.g., If unable to parse UA.
            $BrowscapUpdater = new BrowscapUpdater();
            $BrowscapUpdater->setCache(new WurflCache([WurflCache::DIR => $browscap_dir]));
            $BrowscapUpdater->update(BrowscapIniLoader::PHP_INI_LITE);
        } catch (\Exception $Exception) {
            $this->cacheUnlock($cache_lock);
            if ($throw_exception_on_failure) {
                throw $Exception;
            } // Else use soft failure.
            return; // Not possible.
        }
        if (is_writable($ua_info_dir) && !is_file($ua_info_dir.'/.htaccess')) {
            file_put_contents($ua_info_dir.'/.htaccess', $this->htaccess_deny);
        }
        if (!is_dir($browscap_dir) || !is_writable($browscap_dir) || !is_file($ua_info_dir.'/.htaccess')) {
            $this->cacheUnlock($cache_lock);
            if ($throw_exception_on_failure) {
                throw new \Exception('UA info directory population failure.');
            } // Else use soft failure.
            return; // Not possible.
        }
        $this->cacheUnlock($cache_lock);
    }
}
/*[/pro]*/
