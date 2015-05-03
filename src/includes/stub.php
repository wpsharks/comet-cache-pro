<?php
/**
 * Stub.
 *
 * @since 150422 Rewrite
 */
namespace WebSharks\ZenCache\Pro {

    if (!defined('WPINC')) {
        exit('Do NOT access this file directly: '.basename(__FILE__));
    }
    require_once dirname(dirname(__FILE__)).'/vendor/autoload.php';

    const DIRECTORY = __DIR__; // Plugin file directory.
    const GLOBAL_NS = 'zencache'; // Global namespace.
}
