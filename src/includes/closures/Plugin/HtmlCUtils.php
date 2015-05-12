<?php
namespace WebSharks\ZenCache\Pro;

/*[pro strip-from="lite"]*/
/*
 * Adds marker for the HTML Compressor.
 *
 * @since 150422 Rewrite.
 *
 * @attaches-to `wp_print_footer_scripts` hook (twice).
 */
$self->htmlCFooterScripts = function () use ($self) {
    if (!$self->options['enable']) {
        return; // Nothing to do.
    }
    echo "\n".'<!--footer-scripts-->'."\n";
};
/*[/pro]*/
