<?php
/*[pro exclude-file-from="lite"]*/
/*[pro strip-from="lite"]*/
namespace WebSharks\CometCache\Pro\Traits\Plugin;

use WebSharks\CometCache\Pro\Classes;

trait HtmlCUtils
{
    /**
     * Adds marker for the HTML Compressor.
     *
     * @since 150422 Rewrite.
     *
     * @attaches-to `wp_print_footer_scripts` hook (twice).
     */
    public function htmlCFooterScripts()
    {
        if (!$this->options['enable']) {
            return; // Nothing to do.
        }
        echo "\n".'<!--footer-scripts-->'."\n";
    }
}
/*[/pro]*/
