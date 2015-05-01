<?php
/**
 * Example AC Plugin File.
 *
 * If implemented; this file should go in this special directory:
 *    `/wp-content/ac-plugins/my-ac-plugin.php`
 */
if (!defined('WPINC')) {
    exit('Do NOT access this file directly: '.basename(__FILE__));
}

function my_ac_plugin() // Example plugin.
{
    $ac = $GLOBALS['zencache__advanced_cache']; // Advanced cache instance.
    $ac->add_filter(get_class($ac).'__version_salt', 'my_ac_version_salt_shaker');
}

function my_ac_version_salt_shaker($version_salt)
{
    if (stripos($_SERVER['HTTP_USER_AGENT'], 'iphone') !== false) {
        $version_salt .= 'iphones'; // Give iPhones their own variation of the cache.
    } elseif (stripos($_SERVER['HTTP_USER_AGENT'], 'android') !== false) {
        $version_salt .= 'androids'; // Give Androids their own variation of the cache.
    } else {
        $version_salt .= 'other'; // A default group for all others.
    }
    return $version_salt;
}

my_ac_plugin(); // Run this plugin.
