<?php
/*[pro exclude-file-from="lite"]*/
/*[pro strip-from="lite"]*/
namespace WebSharks\CometCache\Pro\Traits\Plugin;

use WebSharks\CometCache\Pro\Classes;

trait UpdateUtils
{
    /**
     * Checks for a new pro release.
     *
     * @since 150422 Rewrite.
     * @since 160917 Don't check current user.
     *
     * @attaches-to `admin_init` hook.
     */
    public function maybeCheckLatestProVersion()
    {
        if (!$this->options['pro_update_check']) {
            return; // Nothing to do; not enabled right now.
        } elseif ($this->options['last_pro_update_check'] >= strtotime('-1 hour') && empty($_REQUEST['force-check'])) {
            return; // Already did this recently & not forcing a new check.
        }
        $this->updateOptions(['last_pro_update_check' => time()]);
        $pro_slug = str_replace('_', '-', GLOBAL_NS).'-pro';

        // This is the most common type of update check, which occurs quite frequently.
        // Instead of connecting to our server all the time, we use CDN endpoints that are faster.

        // The CDN has simple static text files containing version info in two different flavors:
        // 1.) `version.txt` returns the latest stable version; i.e., stable release only (default).
        // 2.) `version-ars.txt` returns (a)ny (r)elease (s)tate; i.e., considers beta/RC versions also.

        if (!$this->options['pro_update_check_stable']) {
            $wp_remote_response = wp_remote_get('http://cdn.wpsharks.com/software/latest/'.urlencode($pro_slug).'/version-ars.txt');
            $latest_pro_version = trim(wp_remote_retrieve_body($wp_remote_response));
        } else {
            $wp_remote_response = wp_remote_get('http://cdn.wpsharks.com/software/latest/'.urlencode($pro_slug).'/version.txt');
            $latest_pro_version = trim(wp_remote_retrieve_body($wp_remote_response));
        }
        if ($latest_pro_version && preg_match('/^[0-9]{6}/u', $latest_pro_version)) {
            $this->updateOptions(['latest_pro_version' => $latest_pro_version]);
            if ($this->versionCompare($latest_pro_version, VERSION, '>', true)) {
                $this->maybeCheckLatestProPackage();
            }
        }
    }

    /**
     * Checks for a new pro release package.
     *
     * @since 160917 Enhancing update utils.
     */
    protected function maybeCheckLatestProPackage()
    {
        if (!$this->options['pro_update_check']) {
            return; // Not enabled right now.
        } elseif (!$this->options['pro_update_username']) {
            return; // Not possible; missing username.
        } elseif (!$this->options['pro_update_password']) {
            return; // Not possible; missing license key.
        }
        $this->dismissMainNotice('pro_update_error');
        $this->updateOptions(['last_pro_update_check' => time()]);
        $pro_slug = str_replace('_', '-', GLOBAL_NS).'-pro';

        $product_api_endpoint          = 'https://'.urlencode(DOMAIN).'/';
        $product_api_endpoint_fallback = 'http://update-fallback.wpsharks.io/cc-proxy';

        $product_api_headers      = ['x-via-software: '.__FUNCTION__];
        $product_api_request_vars = [
            'product_api' => [
                'action'   => 'latest_pro_update',
                'username' => (string) $this->options['pro_update_username'],
                'password' => (string) $this->options['pro_update_password'],
                'stable'   => (string) $this->options['pro_update_check_stable'],
            ],
        ];
        $wp_remote_response = wp_remote_post($product_api_endpoint, [
            'timeout' => 5, // Plenty.
            'headers' => $product_api_headers,
            'body'    => $product_api_request_vars,
        ]); // Attempt using the primary API endpoint first!

        // Try fallback only when there is an error related to SSL in some way.
        // NOTE: If the update server is simply busy, retrying via the proxy will only make the problem worse.
        // For that reason, avoid using the fallback when the error is something other than an SSL-related issue.
        if (is_wp_error($wp_remote_response) && preg_match('/\b(?:ssl|https|certif|verif|cipher|proto)/ui', $wp_remote_response->get_error_message())) {
            $wp_remote_response = wp_remote_post($product_api_endpoint_fallback, [
                'timeout' => 5, // Plenty.
                'headers' => $product_api_headers,
                'body'    => $product_api_request_vars,
            ]);
        } // Now let's go with whichever response we ended up with above.
        $product_api_response = json_decode(wp_remote_retrieve_body($wp_remote_response));

        if (!empty($product_api_response->error)) { // Error returned by our API response?
            $this->enqueueMainNotice(
                sprintf(__('<strong>%1$s:</strong> An error occurred while checking for updates: <code>%2$s</code><br/>', SLUG_TD), esc_html(NAME), esc_html($product_api_response->error)).
                sprintf(__('Please review <strong><a href="%2$s">%1$s → Plugin Options → Update Credentials</a></strong>', SLUG_TD), esc_html(NAME), esc_url(add_query_arg(urlencode_deep(['page' => GLOBAL_NS]), self_admin_url('/admin.php')))),
                ['class' => 'error', 'persistent_key' => 'pro_update_error', 'dismissable' => false]
            ); // Inform site owner when an update error occurs.
        } elseif (!empty($product_api_response->pro_version) && !empty($product_api_response->pro_zip)) {
            $this->updateOptions(['latest_pro_version' => $product_api_response->pro_version, 'latest_pro_package' => $product_api_response->pro_zip]);
        }
    }

    /**
     * Show latest pro version changelog.
     *
     * @since 160917 Enhancing update utils.
     *
     * @attaches-to `admin_init` hook.
     */
    public function maybeShowLatestProVersionChangelog()
    {
        $pro_slug = str_replace('_', '-', GLOBAL_NS).'-pro';

        if (!empty($GLOBALS['pagenow']) && $GLOBALS['pagenow'] === 'plugin-install.php'
                && !empty($_REQUEST['plugin']) && $_REQUEST['plugin'] === $pro_slug
                && !empty($_REQUEST['tab']) && $_REQUEST['tab'] === 'plugin-information') {
            wp_redirect('https://'.urlencode(DOMAIN).'/changelog/?in-wp');
            exit(); // Stop upon redirecting.
        }
    }

    /**
     * Transient filter.
     *
     * @since 160917 Enhancing update utils.
     *
     * @attaches-to `site_transient_update_plugins` filter.
     *
     * @param \StdClass|mixed $report Report details.
     *
     * @return \StdClass|mixed Report details.
     */
    public function onGetSiteTransientUpdatePlugins($report)
    {
        if (!is_object($report)) { // e.g., Does not exist yet?
            $report = new \StdClass(); // Force object instance.
        }
        if (!isset($report->response) || !is_array($report->response)) {
            $report->response = []; // Force an array value.
            // This may not exist due to HTTP errors or other quirks.
        }
        $plugin_url      = 'https://'.urlencode(DOMAIN).'/';
        $plugin_slug     = basename(PLUGIN_FILE, '.php');
        $plugin_basename = plugin_basename(PLUGIN_FILE);

        $this->maybeCheckLatestProVersion(); // Bypass `admin_init` dependency.
        // This makes it compatible with third-party libraries like ManageWP, etc.

        $latest_version = $this->options['latest_pro_version'];
        $latest_package = $this->options['latest_pro_package'];

        if ($latest_version && $latest_package && $this->versionCompare($latest_version, VERSION, '>', true)) {
            $report->response[$plugin_basename] = (object) [
                'id'          => 0,
                'url'         => $plugin_url,
                'slug'        => $plugin_slug,
                'plugin'      => $plugin_basename,
                'new_version' => $latest_version,
                'package'     => $latest_package,
                'tested'      => get_bloginfo('version'),
            ];
        }
        return $report; // With possible update for this app.
    }

    /**
     * Auto-update filter.
     *
     * @since 161221 Adding auto-update option.
     *
     * @attaches-to `auto_update_plugin` filter.
     *
     * @param bool   $update Should update?
     * @param object $item   Item to check.
     *
     * @return bool Should update?
     */
    public function maybeAutoUpdateInBackground($update, $item)
    {
        $pro_slug = str_replace('_', '-', GLOBAL_NS).'-pro';

        if (!empty($item->slug) && $item->slug === $pro_slug) {
            return $update = true; // Auto-update.
        } else {
            return $update; // Unchanged in this case.
        }
    }
}
/*[/pro]*/
