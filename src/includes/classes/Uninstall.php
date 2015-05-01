<?php
namespace WebSharks\ZenCache\Pro;

/**
 * Uninstaller.
 *
 * @since 150422 Rewrite of ZenCache
 */
class Uninstall
{
    /**
     * Uninstall constructor.
     *
     * @since 150422 Rewrite of ZenCache
     */
    public function __construct()
    {
        $GLOBALS[__NAMESPACE__.'_uninstalling'] = true;
        $GLOBALS[__NAMESPACE__]                 =  new Plugin(false);
        $GLOBALS[__NAMESPACE__]->uninstall(); // Run uninstall routines.
    }
}
