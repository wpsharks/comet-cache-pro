<?php
if (!defined('WPINC')) {
    exit('Do NOT access this file directly: '.basename(__FILE__));
}
$GLOBALS['wp_php_rv'] = '5.3.2'; //php-required-version//

if (require(dirname(__FILE__).'/src/vendor/websharks/wp-php-rv/src/includes/check.php')) {
    if (!empty($_REQUEST['zencache_apc_warning_bypass']) && is_admin()) {
        update_site_option('zencache_apc_warning_bypass', time());
    }
    if (extension_loaded('apc') && filter_var(ini_get('apc.enabled'), FILTER_VALIDATE_BOOLEAN) && filter_var(ini_get('apc.cache_by_default'), FILTER_VALIDATE_BOOLEAN) && stripos((string) ini_get('apc.filters'), 'zencache') === false && !get_site_option('zencache_apc_warning_bypass')) {
        if (empty($_REQUEST['zencache_apc_warning_bypass']) && is_admin()) {
            ${__FILE__}['apc_warning'] = '<h3 style="margin:.5em 0 .25em 0;">'.__('<strong>APC EXTENSION WARNING</strong></h3>', 'zencache');
            ${__FILE__}['apc_warning'] .= '<p style="margin-top:0;">'.sprintf(__('<strong>ZenCache says...</strong> It appears that you\'re currently running PHP v%1$s with APC enabled. APC is <a href="http://zencache.com/r/apc-compatibility/" target="_blank">known to contain bugs</a>.', 'zencache'), esc_html(PHP_VERSION)).'</p>';

            ${__FILE__}['apc_warning'] .= __('<h4 style="margin:0 0 .5em 0; font-size:1.25em;"><span class="dashicons dashicons-lightbulb"></span> Options Available (Action Required):</h4>', 'zencache');
            ${__FILE__}['apc_warning'] .= '<ul style="margin-left:2em; list-style:disc;">';
            ${__FILE__}['apc_warning'] .= '  <li>'.__('Please add <code>ini_set(\'apc.cache_by_default\', false);</code> to the top of your <code>/wp-config.php</code> file. That will get rid of this message and allow ZenCache to run without issue.', 'zencache').'</li>';
            ${__FILE__}['apc_warning'] .= '  <li>'.__('Or, contact your web hosting provider and ask about upgrading to PHP v5.5+; which includes the new <a href="http://zencache.com/r/php-opcache-extension/" target="_blank">Opcache extension for PHP</a>. The new Opcache extension replaces APC in modern versions of PHP.', 'zencache').'</li>';
            ${__FILE__}['apc_warning'] .= '  <li>'.__('Or, you may <a href="'.esc_attr(add_query_arg('zencache_apc_warning_bypass', '1')).'" onclick="if(!confirm(\'Are you sure? Press OK to continue, or Cancel to stop and read carefully.\')) return false;">click here to ignore this warning</a> and continue running ZenCache together with APC. Not recommended!', 'zencache').'</li>';
            ${__FILE__}['apc_warning'] .= '</ul>';

            ${__FILE__}['apc_warning'] .= '<p style="margin-bottom:.5em;">'.__('If you\'d like to learn more about APC compatibility issues, please read <a href="http://zencache.com/r/apc-compatibility/" target="_blank">this article</a>.', 'zencache').'</p>';

            add_action('all_admin_notices', create_function('', 'if(!current_user_can(\'activate_plugins\'))'.
                                                           '   return;'."\n".// User missing capability.

                                                           'echo \''.// Wrap `$notice` inside a WordPress error.

                                                           '<div class="error">'.
                                                           '      '.str_replace("'", "\\'", ${__FILE__}['apc_warning']).
                                                           '</div>'.

                                                           '\';'));
        }
        unset(${__FILE__}); // Housekeeping.
    } else {
        require_once dirname(__FILE__).'/src/includes/plugin.php';
    }
} else {
    wp_php_rv_notice('ZenCache');
}
