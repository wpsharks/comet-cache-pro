<?php
/**
 * Stub.
 *
 * @since 150422 Rewrite.
 */
namespace WebSharks\ZenCache\Pro;

if (!defined('WPINC')) {
    exit('Do NOT access this file directly: '.basename(__FILE__));
}
require_once dirname(dirname(__FILE__)).'/vendor/autoload.php';
require_once dirname(__FILE__).'/functions/i18n-utils.php';

if (!defined(__NAMESPACE__.'\\SHORT_NAME')) {
    define(__NAMESPACE__.'\\SHORT_NAME', 'ZC');
}
if (!defined(__NAMESPACE__.'\\NAME')) {
    define(__NAMESPACE__.'\\NAME', 'ZenCache');
}
if (!defined(__NAMESPACE__.'\\DOMAIN')) {
    define(__NAMESPACE__.'\\DOMAIN', 'zencache.com');
}
if (!defined(__NAMESPACE__.'\\GLOBAL_NS')) {
    define(__NAMESPACE__.'\\GLOBAL_NS', 'zencache');
}
if (!defined(__NAMESPACE__.'\\VERSION')) {
    ${__FILE__}['version'] = '150510'; //version//
    define(__NAMESPACE__.'\\VERSION', ${__FILE__}['version']);
}
if (!isset($GLOBALS[GLOBAL_NS])) {
    $GLOBALS[GLOBAL_NS] = null;
}
