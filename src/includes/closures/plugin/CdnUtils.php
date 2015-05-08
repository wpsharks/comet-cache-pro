<?php
namespace WebSharks\ZenCache\Pro;

/**
 * Utilities.
 *
 * @since 150422 Rewrite.
 */
class CdnUtils extends AbsBase
{
    /**
     * Bumps CDN invalidation counter.
     *
     * @since 140422 First documented version.
     */
    public function bump_cdn_invalidation_counter()
    {
        if (!$this->options['enable']) {
            return;
        } // Nothing to do.

        if (!$this->options['cdn_enable']) {
            return;
        } // Nothing to do.

        $this->options['cdn_invalidation_counter'] = // Bump!
            (string) ($this->options['cdn_invalidation_counter'] + 1);
        update_option(__NAMESPACE__.'_options', $this->options); // Blog-specific.
        if (is_multisite()) {
            update_site_option(__NAMESPACE__.'_options', $this->options);
        }
    }
}
