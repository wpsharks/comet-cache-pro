<?php
namespace WebSharks\ZenCache\Pro;

/**
 * Actions.
 *
 * @since 150422 Rewrite.
 */
class Actions extends AbsBase
{
    /**
     * Allowed actions.
     *
     * @since 150422 Rewrite.
     */
    protected $allowed_actions = array(
        'wipeCache',
        'clearCache',

        /*[pro strip-from="lite"]*/
        'ajaxWipeCache',
        'ajaxClearCache',
        /*[/pro]*/

        'saveOptions',
        'restoreDefaultOptions',
        /*[pro strip-from="lite"]*/
        'exportOptions',
        /*[/pro]*/

        /*[pro strip-from="lite"]*/
        'proUpdate',
        /*[/pro]*/

        'dismissNotice',
        'dismissError',
    );

    /**
     * Class constructor.
     *
     * @since 150422 Rewrite.
     */
    public function __construct()
    {
        parent::__construct();

        if (empty($_REQUEST[GLOBAL_NS])) {
            return; // Not applicable.
        }
        foreach ((array) $_REQUEST[GLOBAL_NS] as $_action => $_args) {
            if (is_string($_action) && method_exists($this, $_action)) {
                if (in_array($_action, $this->allowed_actions, true)) {
                    $this->{$_action}($_args);
                }
            }
        }
        unset($_action, $_args); // Housekeeping.
    }

    /**
     * Action handler.
     *
     * @since 150422 Rewrite.
     *
     * @param mixed Input action argument(s).
     */
    protected function wipeCache($args)
    {
        if (!current_user_can($this->plugin->network_cap)) {
            return; // Nothing to do.
        }
        if (empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'])) {
            return; // Unauthenticated POST data.
        }
        $counter = $this->plugin->wipeCache(true);

        /*[pro strip-from="lite"]*/
        if ($this->plugin->options['cache_clear_s2clean_enable']) {
            if ($this->plugin->functionIsPossible('s2clean')) {
                $s2clean_counter = s2clean()->md_cache_clear();
            }
        }
        /*[/pro]*/

        /*[pro strip-from="lite"]*/
        if ($this->plugin->options['cache_clear_eval_code']) {
            ob_start(); // Buffer output from PHP code.
            eval('?>'.$this->plugin->options['cache_clear_eval_code'].'<?php ');
            $eval_output = ob_get_clean();
        }
        /*[/pro]*/

        $redirect_to = self_admin_url('/admin.php');
        $query_args  = array('page' => GLOBAL_NS, GLOBAL_NS.'_cache_wiped' => '1');
        $redirect_to = add_query_arg(urlencode_deep($query_args), $redirect_to);

        wp_redirect($redirect_to).exit();
    }

    /**
     * Action handler.
     *
     * @since 150422 Rewrite.
     *
     * @param mixed Input action argument(s).
     */
    protected function clearCache($args)
    {
        if (!current_user_can($this->plugin->cap)) {
            return; // Nothing to do.
        }
        if (empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'])) {
            return; // Unauthenticated POST data.
        }
        $counter = $this->plugin->clearCache(true);

        /*[pro strip-from="lite"]*/
        if ($this->plugin->options['cache_clear_s2clean_enable']) {
            if ($this->plugin->functionIsPossible('s2clean')) {
                $s2clean_counter = s2clean()->md_cache_clear();
            }
        }
        /*[/pro]*/

        /*[pro strip-from="lite"]*/
        if ($this->plugin->options['cache_clear_eval_code']) {
            ob_start(); // Buffer output from PHP code.
            eval('?>'.$this->plugin->options['cache_clear_eval_code'].'<?php ');
            $eval_output = ob_get_clean();
        }
        /*[/pro]*/

        $redirect_to = self_admin_url('/admin.php'); // Redirect preparations.
        $query_args  = array('page' => GLOBAL_NS, GLOBAL_NS.'_cache_cleared' => '1');
        $redirect_to = add_query_arg(urlencode_deep($query_args), $redirect_to);

        wp_redirect($redirect_to).exit();
    }

    /*[pro strip-from="lite"]*/
    /**
     * Action handler.
     *
     * @since 150422 Rewrite.
     *
     * @param mixed Input action argument(s).
     */
    protected function ajaxWipeCache($args)
    {
        if (!current_user_can($this->plugin->network_cap)) {
            return; // Nothing to do.
        }
        if (empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'])) {
            return; // Unauthenticated POST data.
        }
        $counter = $this->plugin->wipeCache(true);

        if ($this->plugin->options['cache_clear_s2clean_enable']) {
            if ($this->plugin->functionIsPossible('s2clean')) {
                $s2clean_counter = s2clean()->md_cache_clear();
            }
        }
        if ($this->plugin->options['cache_clear_eval_code']) {
            ob_start(); // Buffer output from PHP code.
            eval('?>'.$this->plugin->options['cache_clear_eval_code'].'<?php ');
            $eval_output = ob_get_clean();
        }
        $response = sprintf(__('<p>Wiped a total of <code>%2$s</code> cache files.</p>', SLUG_TD), esc_html(NAME), esc_html($counter));
        $response .= __('<p>Cache wiped for all sites; recreation will occur automatically over time.</p>', SLUG_TD);
        if (isset($s2clean_counter)) {
            $response .= sprintf(__('<p><strong>Also wiped <code>%1$s</code> s2Clean cache files.</strong></p>', SLUG_TD), $s2clean_counter);
        }
        if (!empty($eval_output)) {
            $response .= $eval_output; // Custom output (perhaps even multiple messages).
        }
        exit($response); // JavaScript will take it from here.
    }
    /*[/pro]*/

    /*[pro strip-from="lite"]*/
    /**
     * Action handler.
     *
     * @since 150422 Rewrite.
     *
     * @param mixed Input action argument(s).
     */
    protected function ajaxClearCache($args)
    {
        if (!current_user_can($this->plugin->cap)) {
            return; // Nothing to do.
        }
        if (empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'])) {
            return; // Unauthenticated POST data.
        }
        $counter = $this->plugin->clearCache(true);

        if ($this->plugin->options['cache_clear_s2clean_enable']) {
            if ($this->plugin->functionIsPossible('s2clean')) {
                $s2clean_counter = s2clean()->md_cache_clear();
            }
        }
        if ($this->plugin->options['cache_clear_eval_code']) {
            ob_start(); // Buffer output from PHP code.
            eval('?>'.$this->plugin->options['cache_clear_eval_code'].'<?php ');
            $eval_output = ob_get_clean();
        }
        $response = sprintf(__('<p>Cleared a total of <code>%2$s</code> cache files.</p>', SLUG_TD), esc_html(NAME), esc_html($counter));
        $response .= __('<p>Cache cleared for this site; recreation will occur automatically over time.</p>', SLUG_TD);
        if (isset($s2clean_counter)) {
            $response .= sprintf(__('<p><strong>Also cleared <code>%1$s</code> s2Clean cache files.</strong></p>', SLUG_TD), $s2clean_counter);
        }
        if (!empty($eval_output)) {
            $response .= $eval_output; // Custom output (perhaps even multiple messages).
        }
        exit($response); // JavaScript will take it from here.
    }
    /*[/pro]*/

    /**
     * Action handler.
     *
     * @since 150422 Rewrite.
     *
     * @param mixed Input action argument(s).
     */
    protected function saveOptions($args)
    {
        if (!current_user_can($this->plugin->cap)) {
            return; // Nothing to do.
        }
        if (empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'])) {
            return; // Unauthenticated POST data.
        }
        if (!empty($_FILES[GLOBAL_NS]['tmp_name']['import_options'])) {
            $import_file_contents = file_get_contents($_FILES[GLOBAL_NS]['tmp_name']['import_options']);
            unlink($_FILES[GLOBAL_NS]['tmp_name']['import_options']);
            $args = wp_slash(json_decode($import_file_contents, true));
            unset($args['crons_setup']); // Unset; CANNOT be imported.
        }
        $args = array_map('trim', stripslashes_deep((array) $args));

        if (!IS_PRO) { // Do not save lite option keys.
            $args = array_diff_key($args, $this->plugin->pro_only_option_keys);
        }
        if (isset($args['base_dir'])) {
            $args['base_dir'] = trim($args['base_dir'], '\\/'." \t\n\r\0\x0B");
        }
        $this->plugin->options = array_merge($this->plugin->default_options, $this->plugin->options, $args);
        $this->plugin->options = array_intersect_key($this->plugin->options, $this->plugin->default_options);

        if (!trim($this->plugin->options['base_dir'], '\\/'." \t\n\r\0\x0B") || strpos(basename($this->plugin->options['base_dir']), 'wp-') === 0) {
            $this->plugin->options['base_dir'] = $this->plugin->default_options['base_dir'];
        }
        update_option(GLOBAL_NS.'_options', $this->plugin->options);
        if (is_multisite()) {
            update_site_option(GLOBAL_NS.'_options', $this->plugin->options);
        }
        $redirect_to = self_admin_url('/admin.php'); // Redirect preparations.
        $query_args  = array('page' => GLOBAL_NS, GLOBAL_NS.'_updated' => '1');

        $this->plugin->autoWipeCache(); // May produce a notice.

        if ($this->plugin->options['enable']) {
            if (!($add_wp_cache_to_wp_config = $this->plugin->addWpCacheToWpConfig())) {
                $query_args[GLOBAL_NS.'_wp_config_wp_cache_add_failure'] = '1';
            }
            if (!($add_advanced_cache = $this->plugin->addAdvancedCache())) {
                $query_args[GLOBAL_NS.'_advanced_cache_add_failure'] = $add_advanced_cache === null ? 'zc-advanced-cache' : '1';
            }
            $this->plugin->updateBlogPaths();
        } else {
            if (!($remove_wp_cache_from_wp_config = $this->plugin->removeWpCacheFromWpConfig())) {
                $query_args[GLOBAL_NS.'_wp_config_wp_cache_remove_failure'] = '1';
            }
            if (!($remove_advanced_cache = $this->plugin->removeAdvancedCache())) {
                $query_args[GLOBAL_NS.'_advanced_cache_remove_failure'] = '1';
            }
        }
        $redirect_to = add_query_arg(urlencode_deep($query_args), $redirect_to);

        wp_redirect($redirect_to).exit();
    }

    /**
     * Action handler.
     *
     * @since 150422 Rewrite.
     *
     * @param mixed Input action argument(s).
     */
    protected function restoreDefaultOptions($args)
    {
        if (!current_user_can($this->plugin->cap)) {
            return; // Nothing to do.
        }
        if (empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'])) {
            return; // Unauthenticated POST data.
        }
        delete_option(GLOBAL_NS.'_options');
        if (is_multisite()) {
            delete_site_option(GLOBAL_NS.'_options');
        }
        $this->plugin->options = $this->plugin->default_options;

        $redirect_to = self_admin_url('/admin.php'); // Redirect preparations.
        $query_args  = array('page' => GLOBAL_NS, GLOBAL_NS.'_restored' => '1');

        $this->plugin->autoWipeCache(); // May produce a notice.

        if ($this->plugin->options['enable']) {
            if (!($add_wp_cache_to_wp_config = $this->plugin->addWpCacheToWpConfig())) {
                $query_args[GLOBAL_NS.'_wp_config_wp_cache_add_failure'] = '1';
            }
            if (!($add_advanced_cache = $this->plugin->addAdvancedCache())) {
                $query_args[GLOBAL_NS.'_advanced_cache_add_failure'] = $add_advanced_cache === null ? 'zc-advanced-cache' : '1';
            }
            $this->plugin->updateBlogPaths();
        } else {
            if (!($remove_wp_cache_from_wp_config = $this->plugin->removeWpCacheFromWpConfig())) {
                $query_args[GLOBAL_NS.'_wp_config_wp_cache_remove_failure'] = '1';
            }
            if (!($remove_advanced_cache = $this->plugin->removeAdvancedCache())) {
                $query_args[GLOBAL_NS.'_advanced_cache_remove_failure'] = '1';
            }
        }
        $redirect_to = add_query_arg(urlencode_deep($query_args), $redirect_to);

        wp_redirect($redirect_to).exit();
    }

    /*[pro strip-from="lite"]*/
    /**
     * Action handler.
     *
     * @since 150422 Rewrite.
     *
     * @param mixed Input action argument(s).
     */
    protected function exportOptions($args)
    {
        if (!current_user_can($this->plugin->cap)) {
            return; // Nothing to do.
        }
        if (empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'])) {
            return; // Unauthenticated POST data.
        }
        ini_set('zlib.output_compression', false);
        if ($this->plugin->functionIsPossible('apache_setenv')) {
            apache_setenv('no-gzip', '1');
        }
        while (@ob_end_clean()) {
            // Cleans output buffers.
        };
        $export    = json_encode($this->plugin->options);
        $file_name = GLOBAL_NS.'-options.json';

        nocache_headers();

        header('Accept-Ranges: none');
        header('Content-Encoding: none');
        header('Content-Length: '.strlen($export));
        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="'.$file_name.'"');

        exit($export); // Deliver the export file.
    }
    /*[/pro]*/

    /*[pro strip-from="lite"]*/
    /**
     * Action handler.
     *
     * @since 150422 Rewrite.
     *
     * @param mixed Input action argument(s).
     */
    protected function proUpdate($args)
    {
        if (!current_user_can($this->plugin->update_cap)) {
            return; // Nothing to do.
        }
        if (empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'])) {
            return; // Unauthenticated POST data.
        }
        $args = array_map('trim', stripslashes_deep((array) $args));

        if (!isset($args['check'])) {
            $args['check'] = $this->plugin->options['pro_update_check'];
        }
        if (empty($args['username'])) {
            $args['username'] = $this->plugin->options['pro_update_username'];
        }
        if (empty($args['password'])) {
            $args['password'] = $this->plugin->options['pro_update_password'];
        }
        $product_api_url        = 'https://'.urlencode(DOMAIN).'/';
        $product_api_input_vars = array(
            'product_api' => array(
                'action'   => 'latest_pro_update',
                'username' => $args['username'],
                'password' => $args['password'],
            ),
        );
        $product_api_response = wp_remote_post($product_api_url, array('body' => $product_api_input_vars));
        $product_api_response = json_decode(wp_remote_retrieve_body($product_api_response), true);

        if (!is_array($product_api_response) || !empty($product_api_response['error'])
           || empty($product_api_response['pro_version']) || empty($product_api_response['pro_zip'])
        ) {
            if (!empty($product_api_response['error'])) {
                $error = (string) $product_api_response['error'];
            } else {
                $error = __('Unknown error. Please wait 15 minutes and try again.', SLUG_TD);
            }
            $redirect_to = self_admin_url('/admin.php'); // Redirect preparations.
            $query_args  = array('page' => GLOBAL_NS.'-pro-updater', GLOBAL_NS.'__error' => $error);
            $redirect_to = add_query_arg(urlencode_deep($query_args), $redirect_to);

            wp_redirect($redirect_to).exit();
        }
        $this->plugin->options['last_pro_update_check'] = (string) time();
        $this->plugin->options['pro_update_check']      = (string) $args['check'];
        $this->plugin->options['pro_update_username']   = (string) $args['username'];
        $this->plugin->options['pro_update_password']   = (string) $args['password'];

        update_option(GLOBAL_NS.'_options', $this->plugin->options);
        if (is_multisite()) {
            update_site_option(GLOBAL_NS.'_options', $this->plugin->options);
        }
        $notices = is_array($notices = get_option(GLOBAL_NS.'_notices')) ? $notices : array();
        unset($notices['persistent-new-pro-version-available']); // Dismiss this notice.
        update_option(GLOBAL_NS.'_notices', $notices); // Update notices.

        $redirect_to = self_admin_url('/update.php');
        $query_args  = array(
            'action'                         => 'upgrade-plugin',
            'plugin'                         => plugin_basename(PLUGIN_FILE),
            '_wpnonce'                       => wp_create_nonce('upgrade-plugin_'.plugin_basename(PLUGIN_FILE)),
            GLOBAL_NS.'__update_pro_version' => $product_api_response['pro_version'],
            GLOBAL_NS.'__update_pro_zip'     => base64_encode($product_api_response['pro_zip']),
        );
        $redirect_to = add_query_arg(urlencode_deep($query_args), $redirect_to);

        wp_redirect($redirect_to).exit();
    }
    /*[/pro]*/

    /**
     * Action handler.
     *
     * @since 150422 Rewrite.
     *
     * @param mixed Input action argument(s).
     */
    protected function dismissNotice($args)
    {
        if (!current_user_can($this->plugin->cap)) {
            return; // Nothing to do.
        }
        if (empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'])) {
            return; // Unauthenticated POST data.
        }
        $args = array_map('trim', stripslashes_deep((array) $args));
        if (empty($args['key'])) {
            return; // Nothing to dismiss.
        }
        $notices = is_array($notices = get_option(GLOBAL_NS.'_notices')) ? $notices : array();
        unset($notices[$args['key']]); // Dismiss this notice.
        update_option(GLOBAL_NS.'_notices', $notices);

        wp_redirect(remove_query_arg(GLOBAL_NS)).exit();
    }

    /**
     * Action handler.
     *
     * @since 150422 Rewrite.
     *
     * @param mixed Input action argument(s).
     */
    protected function dismissError($args)
    {
        if (!current_user_can($this->plugin->cap)) {
            return; // Nothing to do.
        }
        if (empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'])) {
            return; // Unauthenticated POST data.
        }
        $args = array_map('trim', stripslashes_deep((array) $args));
        if (empty($args['key'])) {
            return; // Nothing to dismiss.
        }
        $errors = is_array($errors = get_option(GLOBAL_NS.'_errors')) ? $errors : array();
        unset($errors[$args['key']]); // Dismiss this error.
        update_option(GLOBAL_NS.'_errors', $errors);

        wp_redirect(remove_query_arg(GLOBAL_NS)).exit();
    }
}
