<?php
namespace WebSharks\CometCache\Pro\Classes;

use WebSharks\CometCache\Pro\Traits;

use WebSharks\CometCache\Pro\Interfaces;

/**
 * Advanced cache.
 *
 * @since 150422 Rewrite.
 */
class AdvancedCache extends AbsBaseAp implements Interfaces\Shared\NcDebugConsts
{
    use Traits\Shared\BlogUtils;
    use Traits\Shared\CacheDirUtils;
    use Traits\Shared\CacheLockUtils;
    use Traits\Shared\CachePathUtils;
    use Traits\Shared\ConditionalUtils;
    use Traits\Shared\DomainMappingUtils;
    use Traits\Shared\EscapeUtils;
    use Traits\Shared\FsUtils;
    use Traits\Shared\HookUtils;
    use Traits\Shared\HttpUtils;
    use Traits\Shared\I18nUtils;
    use Traits\Shared\IpAddrUtils;
    use Traits\Shared\PatternUtils;
    use Traits\Shared\ReplaceUtils;
    use Traits\Shared\ServerUtils;
    use Traits\Shared\StringUtils;
    use Traits\Shared\SysUtils;
    use Traits\Shared\TokenUtils;
    use Traits\Shared\TrimUtils;
    use Traits\Shared\UrlUtils;

    use Traits\Ac\AbortUtils;
    use Traits\Ac\AcPluginUtils;
    use Traits\Ac\BrowserUtils;
    use Traits\Ac\HtmlCUtils;
    use Traits\Ac\NcDebugUtils;
    use Traits\Ac\ObUtils;
    use Traits\Ac\PostloadUtils;
    use Traits\Ac\ShutdownUtils;

    /**
     * Flagged as `TRUE` if running.
     *
     * @since 150422 Rewrite.
     *
     * @type bool `TRUE` if running.
     */
    public $is_running = false;

    /**
     * Microtime; for debugging.
     *
     * @since 150422 Rewrite.
     *
     * @type float Microtime; for debugging.
     */
    public $timer = 0;

    /**
     * Class constructor/cache handler.
     *
     * @since 150422 Rewrite.
     */
    public function __construct()
    {
        parent::__construct();

        $closures_dir = dirname(dirname(__FILE__)).'/closures/Ac';
        $self         = $this; // Reference for closures.

        foreach (scandir($closures_dir) as $_closure) {
            if (substr($_closure, -4) === '.php') {
                require $closures_dir.'/'.$_closure;
            }
        }
        unset($_closure); // Housekeeping.

        if (!defined('WP_CACHE') || !WP_CACHE || !COMET_CACHE_ENABLE) {
            return; // Not enabled.
        }
        if (defined('WP_INSTALLING') || defined('RELOCATE')) {
            return; // N/A; installing|relocating.
        }
        $this->is_running = true;
        $this->timer      = microtime(true);

        $this->loadAcPlugins();
        $this->registerShutdownFlag();
        $this->maybeIgnoreUserAbort();
        $this->maybeStopBrowserCaching();
        /*[pro strip-from="lite"]*/
        $this->maybePostloadInvalidateWhenLoggedIn();
        /*[/pro]*/
        $this->maybeStartOutputBuffering();
    }
}
