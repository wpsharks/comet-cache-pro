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
if (!defined(__NAMESPACE__.'\\SLUG_TD')) {
    define(__NAMESPACE__.'\\SLUG_TD', 'zencache');
}
if (!defined(__NAMESPACE__.'\\VERSION')) {
    ${__FILE__}['version'] = '150510'; //version//
    define(__NAMESPACE__.'\\VERSION', ${__FILE__}['version']);
}
if (!defined(__NAMESPACE__.'\\PLUGIN_FILE')) {
    ${__FILE__}['plugin'] = dirname(dirname(dirname(__FILE__))).'/plugin.php';
    define(__NAMESPACE__.'\\PLUGIN_FILE', ${__FILE__}['plugin']);
}
if (!defined(__NAMESPACE__.'\\IS_PRO')) {
    ${__FILE__}['ns_path'] = str_replace('\\', '/', __NAMESPACE__);
    ${__FILE__}['is_pro']  = strtolower(basename(${__FILE__}['ns_path'])) === 'pro';
    define(__NAMESPACE__.'\\IS_PRO', ${__FILE__}['is_pro']);
}
if (!isset($GLOBALS[GLOBAL_NS])) {
    $GLOBALS[GLOBAL_NS] = null;
}
unset(${__FILE__}); // Housekeeping.
