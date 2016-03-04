<?php
namespace WebSharks\CometCache\Pro\Traits\Plugin;

use WebSharks\CometCache\Pro\Classes;

trait UrlUtils
{
    /**
     * URL to a Comet Cache plugin file.
     *
     * @since 150422 Rewrite.
     *
     * @param string $file   Optional file path; relative to plugin directory.
     * @param string $scheme Optional URL scheme; defaults to the current scheme.
     *
     * @return string URL to plugin directory; or to the specified `$file` if applicable.
     */
    public function url($file = '', $scheme = '')
    {
        $url = rtrim(plugin_dir_url(PLUGIN_FILE), '/');
        $url .= (string) $file;

        if ($scheme) {
            $url = set_url_scheme($url, (string) $scheme);
        }
        return $url;
    }
}
