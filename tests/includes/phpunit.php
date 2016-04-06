<?php
/*
 * Bootstrap.
 */
namespace WebSharks\CometCache\Pro;

error_reporting(-1);
ini_set('display_errors', true);

require_once $_SERVER['CI_NFO_WWW_DIR'].'/wp-load.php';

error_reporting(-1);
ini_set('display_errors', true);

$GLOBALS[GLOBAL_NS]->addWpHtaccess();
$GLOBALS[GLOBAL_NS]->addWpCacheToWpConfig();
$GLOBALS[GLOBAL_NS]->addAdvancedCache();
$GLOBALS[GLOBAL_NS]->updateBlogPaths();

$GLOBALS[GLOBAL_NS]->updateOptions(['enable' => true]);
