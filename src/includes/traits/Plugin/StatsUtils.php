<?php
/*[pro strip-from="lite"]*/
namespace WebSharks\CometCache\Pro\Traits\Plugin;

trait StatsUtils {
    /*
     * Pings our stats log w/ anonymous details.
     *
     * @since 150716 Adding stats logging.
     *
     * @attaches-to `admin_init` hook.
     *
     * @see https://cometcache.com/?p=2426
     */
    public function statsLogPinger()
    {
        if (!$this->applyWpFilters(GLOBAL_NS.'_statsLogPinger_enable', IS_PRO)) {
            return; // Stats collection disabled by site.
        }
        if ($this->options['last_pro_stats_log'] >= strtotime('-1 week')) {
            return; // No reason to keep pinging.
        }
        $this->updateOptions(['last_pro_stats_log' => time()]);

        $wpdb               = $this->wpdb(); // WordPress DB.
        $stats_api_url      = 'https://stats.wpsharks.io/log';
        $stats_api_url_args = [ // See: <https://cometcache.com/?p=2426>
                                'os'              => PHP_OS,
                                'php_version'     => PHP_VERSION,
                                'mysql_version'   => $wpdb->db_version(),
                                'wp_version'      => get_bloginfo('version'),
                                'product_version' => VERSION, // Plugin version.
                                'product'         => SLUG_TD.(IS_PRO ? '-pro' : ''),
        ];
        $stats_api_url      = add_query_arg(urlencode_deep($stats_api_url_args), $stats_api_url);

        wp_remote_get(
            $stats_api_url,
            [
                'blocking'  => false,
                'sslverify' => false,
            ]
        );
    }
}
/*[/pro]*/
