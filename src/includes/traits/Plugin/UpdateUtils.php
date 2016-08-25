<?php
namespace WebSharks\CometCache\Pro\Traits\Plugin;

use WebSharks\CometCache\Pro\Classes;

trait UpdateUtils
{
    /**
     * Checks for a new lite release.
     *
     * @since 151220 Show version number in plugin options.
     * @since $v Don't check current user.
     *
     * @attaches-to `admin_init` hook.
     */
    public function maybeCheckLatestLiteVersion()
    {
        if (IS_PRO) {
            return; // Not applicable.
        } elseif (!$this->options['lite_update_check']) {
            return; // Nothing to do.
        } elseif ($this->options['last_lite_update_check'] >= strtotime('-1 hour')) {
            if (empty($_REQUEST['force-check'])) {
                return; // Nothing to do.
            }
        }
        $this->updateOptions(['last_lite_update_check' => time()]);

        $product_api_url        = 'https://'.urlencode(DOMAIN).'/';
        $product_api_input_vars = ['product_api' => ['action' => 'latest_lite_version']];

        $product_api_response = wp_remote_post($product_api_url, ['body' => $product_api_input_vars]);
        $product_api_response = json_decode(wp_remote_retrieve_body($product_api_response));

        if (is_object($product_api_response) && !empty($product_api_response->lite_version)) {
            $this->updateOptions(['latest_lite_version' => $product_api_response->lite_version]);
        }
    }

    /*[pro strip-from="lite"]*/
    /**
     * Checks for a new pro release.
     *
     * @since 150422 Rewrite.
     * @since $v Don't check current user.
     *
     * @attaches-to `admin_init` hook.
     */
    public function maybeCheckLatestProVersion()
    {
        if (!$this->options['pro_update_check']) {
            return; // Nothing to do.
        } elseif ($this->options['last_pro_update_check'] >= strtotime('-1 hour')) {
            if (empty($_REQUEST['force-check'])) {
                return; // Nothing to do.
            }
        }
        $this->updateOptions(['last_pro_update_check' => time()]);

        $product_api_url        = 'https://'.urlencode(DOMAIN).'/';
        $product_api_input_vars = [
            'product_api' => [
                'action' => 'latest_pro_version',
                'stable' => (string) $this->options['pro_update_check_stable'],
            ],
        ];
        $product_api_response = wp_remote_post($product_api_url, ['body' => $product_api_input_vars]);
        $product_api_response = json_decode(wp_remote_retrieve_body($product_api_response));

        if (is_object($product_api_response) && !empty($product_api_response->pro_version)) {
            $this->updateOptions(['latest_pro_version' => $product_api_response->pro_version]);

            if (version_compare($product_api_response->pro_version, VERSION, '>')) {
                $this->maybeCheckLatestProPackage();
            }
        } else { // Let's try the proxy server as a fallback.
            $product_api_url      = 'http://proxy.websharks-inc.net/'.urlencode(SLUG_TD).'/';
            $product_api_response = wp_remote_post($product_api_url, ['body' => $product_api_input_vars, 'timeout' => 15]);
            $product_api_response = json_decode(wp_remote_retrieve_body($product_api_response));

            if (is_object($product_api_response) && !empty($product_api_response->pro_version)) {
                $this->updateOptions(['latest_pro_version' => $product_api_response->pro_version]);

                if (version_compare($product_api_response->pro_version, VERSION, '>')) {
                    $this->maybeCheckLatestProPackage();
                }
            }
        }
    }

    /**
     * Checks for a new pro release package.
     *
     * @since $v Enhancing update utils.
     */
    protected function maybeCheckLatestProPackage()
    {
        if (!$this->options['pro_update_check']) {
            return; // Nothing to do.
        } elseif (!$this->options['pro_update_username']) {
            return; // Not possible.
        } elseif (!$this->options['pro_update_password']) {
            return; // Not possible.
        }
        $this->updateOptions(['last_pro_update_check' => time()]);

        $product_api_url        = 'https://'.urlencode(DOMAIN).'/';
        $product_api_input_vars = [
            'product_api' => [
                'action'   => 'latest_pro_update',
                'stable'   => (string) $this->options['pro_update_check_stable'],
                'username' => (string) $this->options['pro_update_username'],
                'password' => (string) $this->options['pro_update_password'],
            ],
        ];
        $product_api_response = wp_remote_post($product_api_url, ['body' => $product_api_input_vars]);
        $product_api_response = json_decode(wp_remote_retrieve_body($product_api_response));

        if (is_object($product_api_response) && !empty($product_api_response->pro_version) && !empty($product_api_response->pro_zip)) {
            $this->updateOptions(['latest_pro_version' => $product_api_response->pro_version, 'latest_pro_package' => $product_api_response->pro_zip]);
            //
        } else { // Let's try the proxy server as a fallback.
            $product_api_url      = 'http://proxy.websharks-inc.net/'.urlencode(SLUG_TD).'/';
            $product_api_response = wp_remote_post($product_api_url, ['body' => $product_api_input_vars, 'timeout' => 15]);
            $product_api_response = json_decode(wp_remote_retrieve_body($product_api_response));

            if (is_object($product_api_response) && !empty($product_api_response->pro_version) && !empty($product_api_response->pro_zip)) {
                $this->updateOptions(['latest_pro_version' => $product_api_response->pro_version, 'latest_pro_package' => $product_api_response->pro_zip]);
            }
        }
    }

    /**
     * Transient filter.
     *
     * @since $v Enhancing update utils.
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

        if ($latest_version && $latest_package && version_compare($latest_version, VERSION, '>')) {
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
    /*[/pro]*/
}
