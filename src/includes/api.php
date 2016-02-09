<?php
/**
 * API Classes.
 *
 * @since 150422 Rewrite.
 */
namespace WebSharks\Comet_Cache\Pro;

if (!defined('WPINC')) {
    exit('Do NOT access this file directly: '.basename(__FILE__));
}
class_alias(__NAMESPACE__.'\\ApiBase', GLOBAL_NS);

if (!class_exists('zencache')) {
    class_alias(__NAMESPACE__.'\\ApiBase', 'zencache');
}
