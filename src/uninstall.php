<?php
/**
 * ZenCache Uninstaller
 *
 * @since 150422 Rewrite of ZenCache
 * @copyright WebSharks, Inc. <http://www.websharks-inc.com>
 * @license GNU General Public License, version 3
 */
if(!defined('WPINC')) // MUST have WordPress.
	exit('Do NOT access this file directly: '.basename(__FILE__));

$GLOBALS['wp_php_rv'] = '5.3.2'; // Require PHP v5.3.2+.
if(require(dirname(__FILE__).'/includes/vendor/wp-php-rv/check.php'))
	require_once dirname(__FILE__).'/uninstall-load.php';
