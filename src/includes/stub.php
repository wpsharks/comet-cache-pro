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

const SHORT_NAME = 'ZC';
const NAME       = 'ZenCache';
const DOMAIN     = 'zencache.com';
const GLOBAL_NS  = 'zencache';
const VERSION    = '150422';

$GLOBALS[GLOBAL_NS] = null;
