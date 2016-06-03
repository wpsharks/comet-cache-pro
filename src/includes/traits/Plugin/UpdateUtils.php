<?php
namespace WebSharks\CometCache\Pro\Traits\Plugin;

use WebSharks\CometCache\Pro\Classes;

trait UpdateUtils
{
    /**
     * Checks for a new lite release.
     *
     * @since 151220 Show version number in plugin options.
     *
     * @attaches-to `admin_init` hook.
     */
    public function maybeCheckLatestLiteVersion()
    {
        if (IS_PRO) {
            return; // Not applicable.
        }
        if (!$this->options['lite_update_check']) {
            return; // Nothing to do.
        }
        if (!current_user_can($this->update_cap)) {
            return; // Nothing to do.
        }
        if (is_multisite() && !current_user_can($this->network_cap)) {
            return; // Nothing to do.
        }
        if ($this->options['last_lite_update_check'] >= strtotime('-1 hour')) {
            return; // No reason to keep checking on this.
        }
        $this->updateOptions(['last_lite_update_check' => time()]);

        $product_api_url        = 'https://'.urlencode(DOMAIN).'/';
        $product_api_input_vars = ['product_api' => ['action' => 'latest_lite_version']];

        $product_api_response = wp_remote_post($product_api_url, ['body' => $product_api_input_vars]);
        $product_api_response = json_decode(wp_remote_retrieve_body($product_api_response));

        if (is_object($product_api_response) && !empty($product_api_response->lite_version)) {
            $this->updateOptions(['latest_lite_version' => $product_api_response->lite_version]);
        }
        // Disabling the notice for now. We only run this check to collect the latest version number.
        #if ($this->options['latest_lite_version'] && version_compare(VERSION, $this->options['latest_lite_version'], '<')) {
        #    $this->dismissMainNotice('new-lite-version-available'); // Dismiss any existing notices like this.
        #    $lite_updater_page = network_admin_url('/plugins.php'); // In a network this points to the master plugins list.
        #    $this->enqueueMainNotice(sprintf(__('<strong>%1$s:</strong> a new version is now available. Please <a href="%2$s">upgrade to v%3$s</a>.', SLUG_TD), esc_html(NAME), esc_attr($lite_updater_page), esc_html($this->options['latest_lite_version'])), array('persistent_key' => 'new-lite-version-available'));
        #}
    }

    /*[pro strip-from="lite"]*/
    /**
     * Checks for a new pro release.
     *
     * @since 150422 Rewrite.
     *
     * @attaches-to `admin_init` hook.
     *
     * @see pre_site_transient_update_plugins()
     */
    public function maybeCheckLatestProVersion()
    {
        if (!$this->options['pro_update_check']) {
            return; // Nothing to do.
        }
        if (!current_user_can($this->update_cap)) {
            return; // Nothing to do.
        }
        if (is_multisite() && !current_user_can($this->network_cap)) {
            return; // Nothing to do.
        }
        if ($this->options['last_pro_update_check'] >= strtotime('-1 hour')) {
            return; // No reason to keep checking on this.
        }
        $this->updateOptions(['last_pro_update_check' => time()]);

        $product_api_url        = 'https://'.urlencode(DOMAIN).'/';
        $product_api_input_vars = ['product_api' => ['action' => 'latest_pro_version']];

        $product_api_response = wp_remote_post($product_api_url, ['body' => $product_api_input_vars]);
        $product_api_response = json_decode(wp_remote_retrieve_body($product_api_response));

        if (is_object($product_api_response) && !empty($product_api_response->pro_version)) {
            $this->updateOptions(['latest_pro_version' => $product_api_response->pro_version]);
        } else { // Let's try the proxy server
            $product_api_url      = 'http://proxy.websharks-inc.net/'.urlencode(SLUG_TD).'/';
            $product_api_response = wp_remote_post($product_api_url, ['body' => $product_api_input_vars, 'timeout' => 15]);
            $product_api_response = json_decode(wp_remote_retrieve_body($product_api_response));

            if (is_object($product_api_response) && !empty($product_api_response->pro_version)) {
                $this->updateOptions(['latest_pro_version' => $product_api_response->pro_version]);
            }
        }
        if ($this->options['latest_pro_version'] && version_compare(VERSION, $this->options['latest_pro_version'], '<')) {
            $this->dismissMainNotice('new-pro-version-available'); // Dismiss any existing notices like this.
            $pro_updater_page = add_query_arg(urlencode_deep(['page' => GLOBAL_NS.'-pro-updater']), network_admin_url('/admin.php'));
            $this->enqueueMainNotice(sprintf(__('<strong>%1$s Pro:</strong> a new version is now available. Please <a href="%2$s">upgrade to v%3$s</a>.', SLUG_TD), esc_html(NAME), esc_attr($pro_updater_page), esc_html($this->options['latest_pro_version'])), ['persistent_key' => 'new-pro-version-available']);
        }
    }

    /**
     * Modifies transient data associated with this plugin.
     *
     * @since 150422 Rewrite.
     *
     * @attaches-to `pre_site_transient_update_plugins` filter.
     *
     * @param object $transient Transient data provided by the WP filter.
     *
     * @return object Transient object; possibly altered by this routine.
     */
    public function preSiteTransientUpdatePlugins($transient)
    {
        if (!current_user_can($this->update_cap)) {
            return $transient; // Nothing to do.
        }
        if (is_multisite() && !current_user_can($this->network_cap)) {
            return $transient; // Nothing to do.
        }
        if (!is_admin() || $GLOBALS['pagenow'] !== 'update.php') {
            return $transient; // Nothing to do.
        }
        $_r = $this->trimDeep(stripslashes_deep($_REQUEST));

        if (empty($_r['action']) || $_r['action'] !== 'upgrade-plugin') {
            return $transient; // Nothing to do here.
        }
        if (empty($_r['_wpnonce']) || !wp_verify_nonce((string) $_r['_wpnonce'], 'upgrade-plugin_'.plugin_basename(PLUGIN_FILE))) {
            return $transient; // Nothing to do here.
        }
        if (empty($_r[GLOBAL_NS.'_update_pro_version'])) {
            return $transient; // Nothing to do here.
        }

        $update_pro_version = (string) $_r[GLOBAL_NS.'_update_pro_version'];

        if (!($update_pro_zip = get_site_transient(GLOBAL_NS.'_update_pro_zip_'.$update_pro_version))) {
            return $transient; // Nothing to do here.
        }

        if (!is_object($transient)) {
            $transient = new \stdClass();
        }
        $transient->last_checked                           = time();
        $transient->checked[plugin_basename(PLUGIN_FILE)]  = VERSION;
        $transient->response[plugin_basename(PLUGIN_FILE)] = (object) [
            'id'          => 0,
            'slug'        => basename(PLUGIN_FILE, '.php'),
            'url'         => add_query_arg(urlencode_deep(['page' => GLOBAL_NS.'-pro-updater']), self_admin_url('/admin.php')),
            'new_version' => $update_pro_version, 'package' => $update_pro_zip,
        ];
        return $transient; // Notified now.
    }

    /**
     * Appends hidden inputs for pro updater when FTP credentials are requested by WP.
     *
     * @since 150422 Rewrite.
     *
     * @attaches-to `fs_ftp_connection_types` filter.
     *
     * @param array $types Types of connections.
     *
     * @return array $types Types of connections.
     */
    public function fsFtpConnectionTypes($types)
    {
        if (!current_user_can($this->update_cap)) {
            return $types; // Nothing to do.
        }
        if (is_multisite() && !current_user_can($this->network_cap)) {
            return $types; // Nothing to do.
        }
        if (!is_admin() || $GLOBALS['pagenow'] !== 'update.php') {
            return $types; // Nothing to do.
        }
        $_r = $this->trimDeep(stripslashes_deep($_REQUEST));

        if (empty($_r['action']) || $_r['action'] !== 'upgrade-plugin') {
            return $types; // Nothing to do.
        }
        if (empty($_r[GLOBAL_NS.'_update_pro_version']) || empty($_r[GLOBAL_NS.'_update_pro_zip'])) {
            return $types; // Nothing to do.
        }
        $update_pro_version = (string) $_r[GLOBAL_NS.'_update_pro_version'];
        $update_pro_zip     = (string) $_r[GLOBAL_NS.'_update_pro_zip']; // Encrypted!

        echo '<script type="text/javascript">';
        echo '   (function($){ $(document).ready(function(){';
        echo '      var $form = $(\'input#hostname\').closest(\'form\');';
        echo '      $form.append(\'<input type="hidden" name="'.esc_attr(GLOBAL_NS.'_update_pro_version').'" value="'.esc_attr($update_pro_version).'" />\');';
        echo '      $form.append(\'<input type="hidden" name="'.esc_attr(GLOBAL_NS.'_update_pro_zip').'" value="'.esc_attr($update_pro_zip).'" />\');';
        echo '   }); })(jQuery);';
        echo '</script>';

        return $types; // Filter through.
    }
    /*[/pro]*/
}
