<?php
/**
 * ZenCache Plugin
 *
 * @since 150422 Rewrite of ZenCache
 * @copyright WebSharks, Inc. <http://www.websharks-inc.com>
 * @license GNU General Public License, version 3
 */
namespace WebSharks\ZenCache\Pro;

if(!defined('WPINC')) // MUST have WordPress.
	exit('Do NOT access this file directly: '.basename(__FILE__));

require_once dirname(__FILE__).'/includes/vendor/autoload.php';
$GLOBALS['zencache'] = new Plugin();
