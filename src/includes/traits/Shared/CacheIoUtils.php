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
     * @param mixed $file
     * @param mixed $contents
     * @param mixed $max_age
     *
     * @return bool True on success.
     */
    public function cacheWrite($file, $contents, $max_age = 0)
    {
        return false; // @TODO
    }
}
