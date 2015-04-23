<?php
/**
 * ZenCache Plugin
 *
 * @since 150422 Rewrite of ZenCache
 * @copyright WebSharks, Inc. <http://www.websharks-inc.com>
 * @license GNU General Public License, version 3
 */
/*
Version: 150409
Text Domain: zencache
Plugin Name: ZenCache Pro
Network: true

Author: WebSharks, Inc.
Author URI: http://www.websharks-inc.com/

Plugin URI: http://zencache.com/
Description: ZenCache is an advanced WordPress caching plugin inspired by simplicity.
*/
if(!defined('WPINC')) // MUST have WordPress.
	exit('Do NOT access this file directly: '.basename(__FILE__));

$GLOBALS['wp_php_rv'] = '5.3.2'; // Require PHP v5.3.2+.
if(require(dirname(__FILE__).'/includes/vendor/wp-php-rv/check.php'))
	require_once dirname(__FILE__).'/plugin-load.php';
else wp_php_rv_notice('ZenCache Pro');
