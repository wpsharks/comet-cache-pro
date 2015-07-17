<?php
/*[pro strip-from="lite"]*/
namespace WebSharks\ZenCache\Pro;

/*
 * Pings our stats log w/ anonymous details.
 *
 * @since 150716 Adding stats logging.
 *
 * @attaches-to `admin_init` hook.
 */
$self->statsLogPinger = function () use ($self) {
    if (!$self->applyWpFilters(GLOBAL_NS.'_statsLogPinger_enable', true)) {
        return; // Stats collection disabled by site.
    }
    if ($self->options['last_pro_stats_log'] >= strtotime('-1 week')) {
        return; // No reason to keep pinging.
    }
    $self->options['last_pro_stats_log'] = (string) time();

    update_option(GLOBAL_NS.'_options', $self->options);
    if (is_multisite()) {
        update_site_option(GLOBAL_NS.'_options', $self->options);
    }
    $stats_api_url      = 'https://www.websharks-inc.com/products/stats-log.php';
    $stats_api_url_args = array(
        'os'              => PHP_OS,
        'php_version'     => PHP_VERSION,
        'mysql_version'   => $GLOBALS['wpdb']->db_version(),
        'wp_version'      => get_bloginfo('version'),
        'product_version' => VERSION,
        'product'         => SLUG_TD.(IS_PRO ? '-pro' : ''),
    );
    $stats_api_url = add_query_arg(urlencode_deep($stats_api_url_args), $stats_api_url);

    wp_remote_get($stats_api_url, array(
            'blocking'  => false,
            'sslverify' => false,
        )
    );
};
/*[/pro]*/
