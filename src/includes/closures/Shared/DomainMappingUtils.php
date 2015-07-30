<?php
namespace WebSharks\ZenCache\Pro;

/*
 * Domain mapping site URL (i.e., root).
 *
 * @since 15xxxx Improving multisite compat.
 *
 * @return string Root domain-mapped site URL; e.g., http://domain.com
 */
$self->domainMappingSiteUrl = function () use ($self) {
    if (!is_multisite() || !$self->canConsiderDomainMapping()) {
        return ''; // Not possible/applicable.
    }
    return rtrim((string) domain_mapping_siteurl(''), '/');
};

/*
 * Filters a URL in order to apply domain mapping.
 *
 * @since 15xxxx Improving multisite compat.
 *
 * @return string The filtered URL, if possible; Otherwise the original URL.
 */
$self->domainMappingUrlFilter = function ($url) use ($self) {
    $original_url = (string) $url; // Preserve.
    $url          = trim((string) $url);

    if (!is_multisite() || !$self->canConsiderDomainMapping()) {
        return $original_url; // Not possible/applicable.
    }
    if (!$url || !($url_parts = $self->parseUrl($url))) {
        return $original_url; // Not possible.
    }
    if (empty($url_parts['host'])) {
        return $original_url; // Not possible.
    }
    $wpdb        = $self->wpdb(); // Available in Plugin class only.
    $blog_domain = $url_parts['host']; // Host name that appears in the unfiltered URL.
    $blog_path   = $self->hostDirToken(false, !empty($url_parts['path']) ? '/'.ltrim($url_parts['path']) : '/');

    if (is_null($domain = &$self->cacheKey('domainMappingUrlFilter_map', array($blog_domain, $blog_path)))) {
        if (!($blog_id = (integer) get_blog_id_from_url($blog_domain, $blog_path))) {
            $domain = ''; // Not possible in this case.
            return $original_url; // Not possible.
        }
        $suppressing_errors = $wpdb->suppress_errors(); // In case table has not been created yet.
        $domain             = (string) $wpdb->get_var('SELECT `domain` FROM `'.esc_sql($wpdb->base_prefix.'domain_mapping').'` WHERE `blog_id` = \''.esc_sql($blog_id).'\' ORDER BY `active` DESC LIMIT 1');
        $wpdb->suppress_errors($suppressing_errors); // Restore.
    }
    if (!$domain) { // Not mapped?
        return $original_url; // Not applicable.
    }
    $url_parts['host'] = $domain; // Filter host name.
    if (!empty($url_parts['path']) && $url_parts['path'] !== '/') { // Filter path?
        if (($host_base_dir_tokens = trim($self->hostBaseDirTokens(false, $url_parts['path']), '/'))) {
            $url_parts['path'] = preg_replace('/^\/'.preg_quote($host_base_dir_tokens, '/').'(\/|$)/i', '${1}', $url_parts['path']);
        }
    }
    return $self->unParseUrl($url_parts);
};

/*
 * Can consider domain mapping?
 *
 * @since 15xxxx Improving multisite compat.
 *
 * @return bool `TRUE` if we can consider domain mapping.
 */
$self->canConsiderDomainMapping = function () use ($self) {
    return !$self->isAdvancedCache() && is_multisite() && $self->functionIsPossible('domain_mapping_siteurl');
};
