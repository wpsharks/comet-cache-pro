<?php
namespace WebSharks\ZenCache\Pro;

/**
 * Utilities.
 *
 * @since 150422 Rewrite.
 */
class HtmlCUtils extends AbsBase
{
    /**
     * Adds marker for the HTML Compressor.
     *
     * @since 140422 First documented version.
     *
     * @attaches-to `wp_print_footer_scripts` hook (twice).
     */
    public function htmlc_footer_scripts()
    {
        if (!$this->options['enable']) {
            return; // Nothing to do.
        }
        echo "\n".'<!--footer-scripts-->'."\n";
    }
}
