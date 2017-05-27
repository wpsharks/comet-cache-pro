<?php
/*[pro exclude-file-from="lite"]*/
/*[pro strip-from="lite"]*/
namespace WebSharks\CometCache\Pro\Traits\Plugin;

use WebSharks\CometCache\Pro\Classes;

trait StatsUtils
{
    /**
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
        if (!apply_filters(GLOBAL_NS.'_statsLogPinger_enable', IS_PRO)) {
            return; // Stats collection disabled by site.
        } elseif ($this->options['last_pro_stats_log'] >= strtotime('-1 week')) {
            return; // No reason to keep pinging.
        }
        $this->updateOptions(['last_pro_stats_log' => time()]);

        $stats_api_endpoint = 'https://stats.wpsharks.io/log';
        $stats_api_url_args = [ // See: <https://cometcache.com/?p=2426>
            'os'              => PHP_OS,
            'php_version'     => PHP_VERSION,
            'wp_version'      => get_bloginfo('version'),
            'mysql_version'   => $this->wpdb()->db_version(),
            'product_version' => VERSION, // Plugin version.
            'product'         => SLUG_TD.(IS_PRO ? '-pro' : ''),
        ];
        $stats_api_url = add_query_arg(urlencode_deep($stats_api_url_args), $stats_api_endpoint);
        wp_remote_get($stats_api_url, ['blocking' => false, 'sslverify' => false]);
    }
}
/*[/pro]*/
