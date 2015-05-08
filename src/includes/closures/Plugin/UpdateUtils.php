<?php
namespace WebSharks\ZenCache\Pro;

/**
 * Utilities.
 *
 * @since 150422 Rewrite.
 */
class UpdateUtils extends AbsBase
{
    /**
     * Checks for a new pro release once every hour.
     *
     * @since 140422 First documented version.
     *
     * @attaches-to `admin_init` hook.
     *
     * @see pre_site_transient_update_plugins()
     */
    public function check_latest_pro_version()
    {
        if (!$this->options['pro_update_check']) {
            return;
        } // Functionality is disabled here.

        if (!current_user_can($this->update_cap)) {
            return;
        } // Nothing to do.

        if ($this->options['last_pro_update_check'] >= strtotime('-1 hour')) {
            return;
        } // No reason to keep checking on this.

        $this->options['last_pro_update_check'] = time(); // Update; checking now.
        update_option(__NAMESPACE__.'_options', $this->options); // Save this option value now.
        if (is_multisite()) {
            update_site_option(__NAMESPACE__.'_options', $this->options);
        }

        $product_api_url        = 'https://'.urlencode($this->domain).'/';
        $product_api_input_vars = array('product_api' => array('action' => 'latest_pro_version'));

        $product_api_response = wp_remote_post($product_api_url, array('body' => $product_api_input_vars));
        $product_api_response = json_decode(wp_remote_retrieve_body($product_api_response), true);

        if (!is_array($product_api_response) || empty($product_api_response['pro_version']) || version_compare($this->version, $product_api_response['pro_version'], '>=')) {
            return;
        } // Current pro version is the latest stable version. Nothing more to do here.

        $pro_updater_page = network_admin_url('/admin.php'); // Page that initiates an update.
        $pro_updater_page = add_query_arg(urlencode_deep(array('page' => __NAMESPACE__.'-pro-updater')), $pro_updater_page);

        $this->enqueue_notice(sprintf(__('<strong>%1$s Pro:</strong> a new version is now available. Please <a href="%2$s">upgrade to v%3$s</a>.', $this->text_domain),
                                      esc_html($this->name), esc_attr($pro_updater_page), esc_html($product_api_response['pro_version'])), 'persistent-new-pro-version-available');
    }

    /**
     * Appends hidden inputs for pro updater when FTP credentials are requested by WP.
     *
     * @since 150218 See: <https://github.com/websharks/quick-cache/issues/389#issuecomment-68620617>
     *
     * @attaches-to `fs_ftp_connection_types` filter.
     *
     * @param array $types Types of connections.
     *
     * @return array $types Types of connections.
     */
    public function fs_ftp_connection_types($types)
    {
        if (!is_admin() || $GLOBALS['pagenow'] !== 'update.php') {
            return $types;
        } // Nothing to do here.

        $_r = $this->trim_deep(stripslashes_deep($_REQUEST));

        if (empty($_r['action']) || $_r['action'] !== 'upgrade-plugin') {
            return $types;
        } // Nothing to do here.

        if (empty($_r[__NAMESPACE__.'__update_pro_version']) || !($update_pro_version = (string) $_r[__NAMESPACE__.'__update_pro_version'])) {
            return $types;
        } // Nothing to do here.

        if (empty($_r[__NAMESPACE__.'__update_pro_zip']) || !($update_pro_zip = (string) $_r[__NAMESPACE__.'__update_pro_zip'])) {
            return $types;
        } // Nothing to do here.

        echo '<script type="text/javascript">';
        echo '   (function($){ $(document).ready(function(){';
        echo '      var $form = $(\'input#hostname\').closest(\'form\');';
        echo '      $form.append(\'<input type="hidden" name="'.esc_attr(__NAMESPACE__.'__update_pro_version').'" value="'.esc_attr($update_pro_version).'" />\');';
        echo '      $form.append(\'<input type="hidden" name="'.esc_attr(__NAMESPACE__.'__update_pro_zip').'" value="'.esc_attr($update_pro_zip).'" />\');';
        echo '   }); })(jQuery);';
        echo '</script>';

        return $types; // Filter through.
    }

    /**
     * Modifies transient data associated with this plugin.
     *
     * @since 140422 First documented version.
     *
     * @attaches-to `pre_site_transient_update_plugins` filter.
     *
     * @param object $transient Transient data provided by the WP filter.
     *
     * @return object Transient object; possibly altered by this routine.
     *
     * @see check_latest_pro_version()
     */
    public function pre_site_transient_update_plugins($transient)
    {
        if (!is_admin() || $GLOBALS['pagenow'] !== 'update.php') {
            return $transient;
        } // Nothing to do here.

        $_r = array_map('trim', stripslashes_deep($_REQUEST));

        if (empty($_r['action']) || $_r['action'] !== 'upgrade-plugin') {
            return $transient;
        } // Nothing to do here.

        if (!current_user_can($this->update_cap)) {
            return $transient;
        } // Nothing to do here.

        if (empty($_r['_wpnonce']) || !wp_verify_nonce((string) $_r['_wpnonce'], 'upgrade-plugin_'.plugin_basename($this->file))) {
            return $transient;
        } // Nothing to do here.

        if (empty($_r[__NAMESPACE__.'__update_pro_version']) || !($update_pro_version = (string) $_r[__NAMESPACE__.'__update_pro_version'])) {
            return $transient;
        } // Nothing to do here.

        if (empty($_r[__NAMESPACE__.'__update_pro_zip']) || !($update_pro_zip = base64_decode((string) $_r[__NAMESPACE__.'__update_pro_zip'], true))) {
            return $transient;
        } // Nothing to do here.

        if (!is_object($transient)) {
            $transient = new \stdClass();
        }

        $transient->last_checked                           = time();
        $transient->checked[plugin_basename($this->file)]  = $this->version;
        $transient->response[plugin_basename($this->file)] = (object) array(
            'id'          => 0, 'slug' => basename($this->file, '.php'),
            'url'         => add_query_arg(urlencode_deep(array('page' => __NAMESPACE__.'-pro-updater')), self_admin_url('/admin.php')),
            'new_version' => $update_pro_version, 'package' => $update_pro_zip, );

        return $transient; // Nodified now.
    }
}
