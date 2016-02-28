<?php
/*[pro strip-from="lite"]*/
namespace WebSharks\CometCache\Pro\Traits\Plugin;

trait CdnUtils {

    /*
     * Bumps CDN invalidation counter.
     *
     * @since 150422 Rewrite.
     */
    public function bumpCdnInvalidationCounter()
    {
        if (!$this->options['enable']) {
            return; // Nothing to do.
        }
        if (!$this->options['cdn_enable']) {
            return; // Nothing to do.
        }
        $this->updateOptions(['cdn_invalidation_counter' => ++$this->options['cdn_invalidation_counter']]);
    }
    /*[/pro]*/
}
