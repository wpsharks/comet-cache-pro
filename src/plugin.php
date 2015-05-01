<?php
/**
 * ZenCache Plugin.
 *
 * @since 150422 Rewrite of ZenCache
 */
namespace WebSharks\ZenCache\Pro;

if (!defined('WPINC')) {
    exit('Do NOT access this file directly: '.basename(__FILE__));
}
require_once dirname(__FILE__).'/vendor/autoload.php';
$GLOBALS['zencache'] = new Plugin();
