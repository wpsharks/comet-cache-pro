<?php
if (!defined('WPINC')) {
    exit('Do NOT access this file directly: '.basename(__FILE__));
}
$GLOBALS['wp_php_rv'] = '5.3.2'; //php-required-version//

if (require(dirname(__FILE__).'/src/vendor/websharks/wp-php-rv/src/includes/check.php')) {

    // See: <https://github.com/websharks/zencache/issues/511#issuecomment-116172179>
    if (version_compare(PHP_VERSION, '5.4', '<') && extension_loaded('apc') && filter_var(ini_get('apc.enabled'), FILTER_VALIDATE_BOOLEAN)) {
        ${__FILE__}['notice'] = sprintf(__('ZenCache says... <strong>ACTION REQUIRED (PHP 5.4+ Needed)</strong><br />It appears that you are currently running <code>PHP v%1$s</code> with <a href="http://php.net/manual/en/book.apc.php" target="_blank">APC</a> enabled. It\'s fine to run the APC extension for PHP. However, the <a href="https://github.com/websharks/zencache/issues/511" target="_blank">specific combination of PHP v%1$s + APC</a> that you\'re running triggers a <a href="http://zencache.com/r/php-bug-52144-apc-mystery/" target="_blank">known PHP bug</a> that will negatively impact ZenCache and potentially other plugins for WordPress that use modern design patterns. Please upgrade to PHP v5.4+ to get rid of this message and allow ZenCache to speed up your site.', 'zencache'), esc_html(PHP_VERSION));
        add_action('all_admin_notices', create_function('', 'if(!current_user_can(\'activate_plugins\'))'.
                                                       '   return;'."\n".// User missing capability.

                                                       'echo \''.// Wrap `$notice` inside a WordPress error.

                                                       '<div class="error">'.
                                                       '   <p>'.
                                                       '      '.str_replace("'", "\\'", ${__FILE__}['notice']).
                                                       '   </p>'.
                                                       '</div>'.

                                                       '\';'));
    } else {
        require_once dirname(__FILE__).'/src/includes/plugin.php';
    }
} else {
    wp_php_rv_notice('ZenCache');
}
