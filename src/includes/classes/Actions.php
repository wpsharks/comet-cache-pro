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
        'ajaxStats',
        'ajaxDirStats',
        /*[/pro]*/

        /*[pro strip-from="lite"]*/
        'ajaxWipeCache',
        'ajaxClearCache',
        /*[/pro]*/

        /*[pro strip-from="lite"]*/
        'ajaxWipeCdnCache',
        'ajaxClearCdnCache',
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
                    $this->{$_action}($_args); // Do action!
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
        if (!is_multisite() || !current_user_can($this->plugin->network_cap)) {
            return; // Nothing to do.
        }
        if (empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'])) {
            return; // Unauthenticated POST data.
        }
        $counter = $this->plugin->wipeCache(true);

        /*[pro strip-from="lite"]*/
        $this->plugin->wipeS2CleanCache();
        $this->plugin->wipeEvalCode();
        $this->plugin->wipeOpcache();
        $this->plugin->wipeCdnCache(true);
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
        if (!$this->plugin->currentUserCanClearCache()) {
            return; // Not allowed to clear.
        }
        if (empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'])) {
            return; // Unauthenticated POST data.
        }
        $counter = $this->plugin->clearCache(true);

        /*[pro strip-from="lite"]*/
        $this->plugin->clearS2CleanCache();
        $this->plugin->clearEvalCode();
        $this->plugin->clearOpcache();
        $this->plugin->clearCdnCache(true);
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
        if (!is_multisite() || !current_user_can($this->plugin->network_cap)) {
            return; // Nothing to do.
        }
        if (empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'])) {
            return; // Unauthenticated POST data.
        }
        $counter         = $this->plugin->wipeCache(true);
        $s2clean_counter = $this->plugin->wipeS2CleanCache();
        $eval_output     = $this->plugin->wipeEvalCode();
        $opcache_counter = $this->plugin->wipeOpcache();
        $cdn_counter = $this->plugin->wipeCdnCache(true);

        $response = sprintf(__('<p>Wiped a total of <code>%2$s</code> cache files.</p>', SLUG_TD), esc_html(NAME), esc_html($counter));
        $response .= __('<p>Cache wiped for all sites. Recreation will occur automatically over time.</p>', SLUG_TD);

        if ($opcache_counter) {
            $response .= sprintf(__('<p><strong>Also wiped <code>%1$s</code> OPCache keys.</strong></p>', SLUG_TD), $opcache_counter);
        }
        if ($s2clean_counter) {
            $response .= sprintf(__('<p><strong>Also wiped <code>%1$s</code> s2Clean cache files.</strong></p>', SLUG_TD), $s2clean_counter);
        }
        if ($eval_output) {
            $response .= $eval_output; // Custom output (perhaps even multiple messages).
        }
        if ($cdn_counter > 0) {
            $response .= sprintf(__('<p><strong>Also wiped CDN cache. Invalidation counter is now <code>%1$s</code>.</strong></p>', SLUG_TD), $cdn_counter);
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
        if (!$this->plugin->currentUserCanClearCache()) {
            return; // Not allowed to clear.
        }
        if (empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'])) {
            return; // Unauthenticated POST data.
        }
        $counter         = $this->plugin->clearCache(true);
        $s2clean_counter = $this->plugin->clearS2CleanCache();
        $eval_output     = $this->plugin->clearEvalCode();
        $opcache_counter = $this->plugin->clearOpcache();
        $cdn_counter = $this->plugin->clearCdnCache(true);

        $response = sprintf(__('<p>Cleared a total of <code>%2$s</code> cache files.</p>', SLUG_TD), esc_html(NAME), esc_html($counter));
        if (is_multisite() && is_main_site()) {
            $response .= __('<p>Cache cleared for main site. Recreation will occur automatically over time.</p>', SLUG_TD);
        } else {
            $response .= __('<p>Cache cleared for this site. Recreation will occur automatically over time.</p>', SLUG_TD);
        }
        if ($opcache_counter) {
            $response .= sprintf(__('<p><strong>Also cleared <code>%1$s</code> OPCache keys.</strong></p>', SLUG_TD), $opcache_counter);
        }
        if ($s2clean_counter) {
            $response .= sprintf(__('<p><strong>Also cleared <code>%1$s</code> s2Clean cache files.</strong></p>', SLUG_TD), $s2clean_counter);
        }
        if ($eval_output) {
            $response .= $eval_output; // Custom output (perhaps even multiple messages).
        }
        if ($cdn_counter > 0) {
            $response .= sprintf(__('<p><strong>Also cleared CDN cache. Invalidation counter is now <code>%1$s</code>.</strong></p>', SLUG_TD), $cdn_counter);
        }
        exit($response); // JavaScript will take it from here.
    }
    /*[/pro]*/

    /*[pro strip-from="lite"]*/
    /**
     * Action handler.
     *
     * @since 15xxxx Adding CDN cache wipe handler.
     *
     * @param mixed Input action argument(s).
     */
    protected function ajaxWipeCdnCache($args)
    {
        if (!is_multisite() || !current_user_can($this->plugin->network_cap)) {
            return; // Nothing to do.
        }
        if (empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'])) {
            return; // Unauthenticated POST data.
        }
        $counter = abs($this->plugin->wipeCdnCache(true, false));

        $response = sprintf(__('<p>CDN cache successfully wiped.</p>', SLUG_TD), esc_html(NAME));
        $response .= sprintf(__('<p>The CDN cache invalidation counter is now: <code>%1$s</code></p>', SLUG_TD), esc_html($counter));

        exit($response); // JavaScript will take it from here.
    }
    /*[/pro]*/

    /*[pro strip-from="lite"]*/
    /**
     * Action handler.
     *
     * @since 15xxxx Adding CDN cache clear handler.
     *
     * @param mixed Input action argument(s).
     */
    protected function ajaxClearCdnCache($args)
    {
        if (!$this->plugin->currentUserCanClearCache()) {
            return; // Not allowed to clear.
        }
        if (empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'])) {
            return; // Unauthenticated POST data.
        }
        $counter = abs($this->plugin->clearCdnCache(true, false));

        $response = sprintf(__('<p>CDN cache successfully cleared.</p>', SLUG_TD), esc_html(NAME));
        $response .= sprintf(__('<p>The CDN cache invalidation counter is now: <code>%1$s</code></p>', SLUG_TD), esc_html($counter));

        exit($response); // JavaScript will take it from here.
    }
    /*[/pro]*/

    /*[pro strip-from="lite"]*/
    /**
     * Action handler.
     *
     * @since 15xxxx Directory stats.
     *
     * @param mixed Input action argument(s).
     */
    protected function ajaxStats($args)
    {
        if (!$this->plugin->currentUserCanSeeStats()) {
            return; // Not allowed to see stats.
        }
        if (empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'])) {
            return; // Unauthenticated POST data.
        }
        if (!$this->plugin->options['stats_enable']) {
            exit(); // Not applicable.
        }
        $dir_stats    = DirStats::instance();
        $is_multisite = is_multisite();

        if (!$is_multisite  || current_user_can($this->plugin->network_cap)) {
            $stats_data = array(
                'forCache'          => $dir_stats->forCache(),
                'forHtmlCCache'     => $dir_stats->forHtmlCCache(),
                'largestCacheSize'  => $dir_stats->largestCacheSize(),
                'largestCacheCount' => $dir_stats->largestCacheCount(),

                'sysLoadAverages'  => $this->plugin->sysLoadAverages(),
                'sysMemoryStatus'  => $this->plugin->sysMemoryStatus(),
                'sysOpcacheStatus' => $this->plugin->sysOpcacheStatus(),
            );
            if ($is_multisite) {
                $stats_data = array_merge($stats_data, array(
                    'forHostCache'      => $dir_stats->forHostCache(),
                    'forHtmlCHostCache' => $dir_stats->forHtmlCHostCache(),
                ));
            }
        } else { // Stats for a child blog owner.
            $stats_data = array(
                'forHostCache'          => $dir_stats->forHostCache(),
                'forHtmlCHostCache'     => $dir_stats->forHtmlCHostCache(),
                'largestHostCacheSize'  => $dir_stats->largestHostCacheSize(),
                'largestHostCacheCount' => $dir_stats->largestHostCacheCount(),
            );
        }
        header('Content-Type: application/json; charset=UTF-8');

        exit(json_encode($stats_data)); // JavaScript will take it from here.
    }
    /*[/pro]*/

    /*[pro strip-from="lite"]*/
    /**
     * Action handler.
     *
     * @since 15xxxx Directory stats.
     *
     * @param mixed Input action argument(s).
     */
    protected function ajaxDirStats($args)
    {
        if (!$this->plugin->currentUserCanSeeStats()) {
            return; // Not allowed to see stats.
        }
        if (empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'])) {
            return; // Unauthenticated POST data.
        }
        if (!$this->plugin->options['stats_enable']) {
            exit(); // Not applicable.
        }
        $dir_stats    = DirStats::instance();
        $is_multisite = is_multisite();

        if (!$is_multisite  || current_user_can($this->plugin->network_cap)) {
            $dir_stats_data = array(
                'forCache'          => $dir_stats->forCache(),
                'forHtmlCCache'     => $dir_stats->forHtmlCCache(),
                'largestCacheSize'  => $dir_stats->largestCacheSize(),
                'largestCacheCount' => $dir_stats->largestCacheCount(),
            );
            if ($is_multisite) {
                $dir_stats_data = array_merge($dir_stats_data, array(
                    'forHostCache'      => $dir_stats->forHostCache(),
                    'forHtmlCHostCache' => $dir_stats->forHtmlCHostCache(),
                ));
            }
        } else { // Stats for a child blog owner.
            $dir_stats_data = array(
                'forHostCache'          => $dir_stats->forHostCache(),
                'forHtmlCHostCache'     => $dir_stats->forHtmlCHostCache(),
                'largestHostCacheSize'  => $dir_stats->largestHostCacheSize(),
                'largestHostCacheCount' => $dir_stats->largestHostCacheCount(),
            );
        }
        header('Content-Type: application/json; charset=UTF-8');

        exit(json_encode($dir_stats_data)); // JavaScript will take it from here.
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
        if (is_multisite() && !current_user_can($this->plugin->network_cap)) {
            return; // Nothing to do.
        }
        if (empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'])) {
            return; // Unauthenticated POST data.
        }
        if (!empty($_FILES[GLOBAL_NS]['tmp_name']['import_options'])) {
            $import_file_contents = file_get_contents($_FILES[GLOBAL_NS]['tmp_name']['import_options']);
            unlink($_FILES[GLOBAL_NS]['tmp_name']['import_options']); // Deleted uploaded file.

            $args = wp_slash(json_decode($import_file_contents, true));

            unset($args['crons_setup']); // CANNOT be imported!
            unset($args['last_pro_update_check']); // CANNOT be imported!
            unset($args['last_pro_stats_log']); // CANNOT be imported!
        }
        $args = $this->plugin->trimDeep(stripslashes_deep((array) $args));
        $this->plugin->updateOptions($args); // Save/update options.

        $redirect_to = self_admin_url('/admin.php'); // Redirect preparations.
        $query_args  = array('page' => GLOBAL_NS, GLOBAL_NS.'_updated' => '1');

        $this->plugin->autoWipeCache(); // May produce a notice.

        if ($this->plugin->options['enable']) {
            if (!($add_wp_cache_to_wp_config = $this->plugin->addWpCacheToWpConfig())) {
                $query_args[GLOBAL_NS.'_wp_config_wp_cache_add_failure'] = '1';
            }
            if (!($add_advanced_cache = $this->plugin->addAdvancedCache())) {
                $query_args[GLOBAL_NS.'_advanced_cache_add_failure'] = $add_advanced_cache === null ? 'advanced-cache' : '1';
            }
            $this->plugin->updateBlogPaths(); // Multisite networks only.
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
        if (is_multisite() && !current_user_can($this->plugin->network_cap)) {
            return; // Nothing to do.
        }
        if (empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'])) {
            return; // Unauthenticated POST data.
        }
        $this->plugin->restoreDefaultOptions(); // Restore defaults.

        $redirect_to = self_admin_url('/admin.php'); // Redirect preparations.
        $query_args  = array('page' => GLOBAL_NS, GLOBAL_NS.'_restored' => '1');

        $this->plugin->autoWipeCache(); // May produce a notice.

        if ($this->plugin->options['enable']) {
            if (!($add_wp_cache_to_wp_config = $this->plugin->addWpCacheToWpConfig())) {
                $query_args[GLOBAL_NS.'_wp_config_wp_cache_add_failure'] = '1';
            }
            if (!($add_advanced_cache = $this->plugin->addAdvancedCache())) {
                $query_args[GLOBAL_NS.'_advanced_cache_add_failure'] = $add_advanced_cache === null ? 'advanced-cache' : '1';
            }
            $this->plugin->updateBlogPaths(); // Multisite networks only.
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
        if (is_multisite() && !current_user_can($this->plugin->network_cap)) {
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
        if (is_multisite() && !current_user_can($this->plugin->network_cap)) {
            return; // Nothing to do.
        }
        if (empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'])) {
            return; // Unauthenticated POST data.
        }
        $args = $this->plugin->trimDeep(stripslashes_deep((array) $args));

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
        $product_api_response = json_decode(wp_remote_retrieve_body($product_api_response));

        if (!is_object($product_api_response) || !empty($product_api_response->error) || empty($product_api_response->pro_version) || empty($product_api_response->pro_zip)) {
            if (!empty($product_api_response->error)) {
                $error = substr((string) $product_api_response->error, 0, 1000);
            } else {
                $error = __('Unknown error. Please wait 15 minutes and try again.', SLUG_TD);
            }
            $redirect_to = self_admin_url('/admin.php'); // Redirect preparations.
            $query_args  = array('page' => GLOBAL_NS.'-pro-updater', GLOBAL_NS.'_error' => $error);
            $redirect_to = add_query_arg(urlencode_deep($query_args), $redirect_to);

            wp_redirect($redirect_to).exit();
        }
        $this->plugin->updateOptions(array(
            'last_pro_update_check' => time(),
            'pro_update_check'      => $args['check'],
            'pro_update_username'   => $args['username'],
            'pro_update_password'   => $args['password'],
            'latest_pro_version'    => $product_api_response->pro_version,
        ));
        $this->plugin->dismissMainNotice('new-pro-version-available');

        $redirect_to = self_admin_url('/update.php');
        $query_args  = array( // Like a normal WP plugin.
            'action'   => 'upgrade-plugin',
            'plugin'   => plugin_basename(PLUGIN_FILE),
            '_wpnonce' => wp_create_nonce('upgrade-plugin_'.plugin_basename(PLUGIN_FILE)),

            // See: `preSiteTransientUpdatePlugins()` where these are picked up.
            GLOBAL_NS.'_update_pro_version' => $product_api_response->pro_version,
            GLOBAL_NS.'_update_pro_zip'     => base64_encode($product_api_response->pro_zip),
            // @TODO Encrypt/decrypt to avoid mod_security issues. Base64 is not enough.
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
        $args = $this->plugin->trimDeep(stripslashes_deep((array) $args));
        $this->plugin->dismissNotice($args['key']);

        wp_redirect(remove_query_arg(GLOBAL_NS)).exit();
    }
}
