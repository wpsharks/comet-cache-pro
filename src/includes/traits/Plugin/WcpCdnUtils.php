<?php
/*[pro exclude-file-from="lite"]*/
/*[pro strip-from="lite"]*/
namespace WebSharks\CometCache\Pro\Traits\Plugin;

use WebSharks\CometCache\Pro\Classes;

trait WcpCdnUtils
{
    /**
     * Wipes out entire CDN cache.
     *
     * @since 151002 Implementing CDN cache wiping.
     *
     * @param bool $manually True if wiping is done manually.
     * @param bool $maybe    Defaults to a true value.
     *
     * @throws \Exception If a wipe failure occurs.
     *
     * @return int CDN invalidation counter after wiping.
     *             Zero, or a negative integer if wiping did not take place.
     */
    public function wipeCdnCache($manually = false, $maybe = true)
    {
        if (!$this->options['cdn_enable']) {
            return -(integer) $this->options['cdn_invalidation_counter'];
        }
        if ($maybe && !$this->options['cache_clear_cdn_enable']) {
            return -(integer) $this->options['cdn_invalidation_counter'];
        }
        $this->updateOptions(['cdn_invalidation_counter' => ++$this->options['cdn_invalidation_counter']]);

        return (integer) $this->options['cdn_invalidation_counter'];
    }

    /**
     * Clears the CDN cache.
     *
     * @since 151002 Implementing CDN cache clearing.
     *
     * @param bool $manually True if clearing is done manually.
     * @param bool $maybe    Defaults to a true value.
     *
     * @throws \Exception If a clear failure occurs.
     *
     * @return int CDN invalidation counter after clearing.
     *             Zero, or a negative integer if clearing did not take place.
     */
    public function clearCdnCache($manually = false, $maybe = true)
    {
        return $this->wipeCdnCache($manually, $maybe);
    }
}
/*[/pro]*/
