<?php
/**
 * ZenCache Uninstaller.
 *
 * @since 150422 Rewrite of ZenCache
 */
namespace WebSharks\ZenCache\Pro;

if (!defined('WPINC')) {
    exit('Do NOT access this file directly: '.basename(__FILE__));
}
const DIRECTORY = __DIR__; // Plugin file directory.
const GLOBAL_NS = 'zencache'; // Global namespace.

require_once dirname(__FILE__).'/vendor/autoload.php';
$GLOBALS[GLOBAL_NS.'_uninstalling'] = true;
$GLOBALS[GLOBAL_NS]                 = null;
$GLOBALS[GLOBAL_NS]                 =  new Plugin(false);
$GLOBALS[GLOBAL_NS]->uninstall(); // Run uninstall routines.
