<?php
namespace WebSharks\ZenCache\Pro;

/**
 * Utilities.
 *
 * @since 150422 Rewrite.
 */
class UrlUtils extends AbsBase
{
    /**
     * URL to a ZenCache plugin file.
     *
     * @since 140422 First documented version.
     *
     * @param string $file   Optional file path; relative to plugin directory.
     * @param string $scheme Optional URL scheme; defaults to the current scheme.
     *
     * @return string URL to plugin directory; or to the specified `$file` if applicable.
     */
    public function url($file = '', $scheme = '')
    {
        if (!isset(static::$static[__FUNCTION__]['plugin_dir'])) {
            static::$static[__FUNCTION__]['plugin_dir'] = rtrim(plugin_dir_url($this->file), '/');
        }
        $plugin_dir = &static::$static[__FUNCTION__]['plugin_dir'];

        $url = $plugin_dir.(string) $file;

        if ($scheme) {
            // A specific URL scheme?
            $url = set_url_scheme($url, (string) $scheme);
        }

        return $this->apply_wp_filters(__METHOD__, $url, get_defined_vars());
    }
}
