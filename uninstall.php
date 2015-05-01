<?php
/**
 * ZenCache Uninstaller.
 *
 * @since 150422 Rewrite of ZenCache
 */
if (!defined('WPINC')) {
    exit('Do NOT access this file directly: '.basename(__FILE__));
}
$GLOBALS['wp_php_rv'] = '5.3.2';
if (require(dirname(__FILE__).'/src/vendor/websharks/wp-php-rv/src/check.php')) {
    require_once dirname(__FILE__).'/uninstall.php';
}
