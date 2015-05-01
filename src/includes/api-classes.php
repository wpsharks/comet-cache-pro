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
class_alias('WebSharks\\ZenCache\\Pro\\ApiBase', GLOBAL_NS);

if (!class_exists('quick_cache')) {
    class_alias('WebSharks\\ZenCache\\Pro\\ApiBase', 'quick_cache');
}
