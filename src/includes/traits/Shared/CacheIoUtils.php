<?php
namespace WebSharks\CometCache\Pro\Traits\Shared;

use WebSharks\CometCache\Pro\Classes;

trait CacheIoUtils
{
    /**
     * Cache read.
     *
     * @since 17xxxx IO utils.
     *
     * @param string $file    Absolute file path.
     * @param int    $max_age Maximum age.
     *
     * @return string Cache file contents, else empty string.
     */
    public function cacheRead($file, $max_age = 0)
    {
        if (!$file) {
            return '';
        } // Not possible.

        /*[pro strip-from="lite"]*/
        if ($this->memEnabled() && ($cache = $this->memGet('cache', md5($file)))) {
            return (string) $cache;
        } /*[/pro]*/

        if (is_file($file) && (!$max_age || filemtime($file) >= $max_age)) {
            return (string) file_get_contents($file);
        }
        return ''; // Not possible.
    }

    /**
     * Cache write.
     *
     * @since 17xxxx IO utils.
     *
     * @param string $file     Absolute file path.
     * @param string $contents Cache file contents.
     * @param int    $max_age  Maximum age.
     * @param bool   $is_404   A 404 error?
     *
     * @return bool True on success.
     */
    public function cacheWrite($file, $contents, $max_age = 0, $is_404 = false)
    {
        // @TODO

        if (!$file) {
            return false;
        } elseif (!$contents) {
            return false;
        } // Not possible.

        $lock = $this->cacheLock();
        $tmp  = $this->addTmpSuffix($file);

        if (!is_dir(COMET_CACHE_DIR) && mkdir(COMET_CACHE_DIR, 0775, true) && !is_file(COMET_CACHE_DIR.'/.htaccess')) {
            file_put_contents(COMET_CACHE_DIR.'/.htaccess', $this->htaccess_deny);
        }
        if (!is_dir($dir = dirname($file))) {
            $dir_writable = mkdir($dir, 0775, true);
        }
        if (empty($dir_writable) && !is_writable($dir)) {
            throw new \Exception(sprintf(__('Cache directory not writable. %1$s needs this directory please: `%2$s`. Set permissions to `755` or higher; `777` might be needed in some cases.', SLUG_TD), NAME, $dir));
        }
        if (is_file($file) && (!$max_age || filemtime($file) >= $max_age)) {
            return (string) file_get_contents($file);
        }
        if ($is_404 && is_file($this->cache_file_404)) {
            if (!(symlink($this->cache_file_404, $cache_file_tmp) && rename($cache_file_tmp, $this->cache_file))) {
                throw new \Exception(sprintf(__('Unable to create symlink: `%1$s` » `%2$s`. Possible permissions issue (or race condition), please check your cache directory: `%3$s`.', SLUG_TD), $this->cache_file, $this->cache_file_404, COMET_CACHE_DIR));
            }
            $this->cacheUnlock($cache_lock); // Release.
            return (bool) $this->maybeSetDebugInfo($this::NC_DEBUG_1ST_TIME_404_SYMLINK);
        }
        /*[pro strip-from="lite"]*/
        if ($this->memEnabled()) {
            $this->memSet('cache', md5($file), $contents, $max_age);
        } /*[/pro]*/

        return true;
    }
}
