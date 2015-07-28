<?php
namespace WebSharks\ZenCache\Pro;

/*
 * Current host name.
 *
 * @since 15xxxx Improving multisite compat.
 *
 * @param boolean $consider_domain_mapping Consider domain mapping?
 *
 * @return string Current HTTP host name.
 */
$self->httpHost = function ($consider_domain_mapping = true) use ($self) {
    $is_advanced_cache = ($self instanceof AdvancedCache);

    // Catch invalid calls to this class member.
    if ($consider_domain_mapping && $is_advanced_cache && func_num_args() >= 1) {
        throw new \Exception(__('Invalid argument. Not possible in this context.', SLUG_TD));
    }
    if (!is_null($host = &$self->cacheKey('httpHost', array($is_advanced_cache, $consider_domain_mapping)))) {
        return $host; // Already did this.
    }
    if ($is_advanced_cache) { // Environment variable all that's possible.
        // Note: `link-template.php` is not loaded in WP core yet in this case.
        $host = !empty($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : '';

    // Consider domain mapping on a multisite network?
    } elseif ($consider_domain_mapping && is_multisite() // Domain mapping?
            && function_exists('domain_mapping_siteurl') && get_site_option('dm_redirect_admin')) {
        $host = (string) parse_url(domain_mapping_siteurl(home_url()), PHP_URL_HOST);

    // Use environment variable if possible.
    } elseif (!empty($_SERVER['HTTP_HOST'])) {
        $host = $_SERVER['HTTP_HOST'];

    // Use `home_url()` otherwise.
    } else { // Last-ditch fallback.
        $host = (string) parse_url(home_url(), PHP_URL_HOST);
    }
    return $host;
};
