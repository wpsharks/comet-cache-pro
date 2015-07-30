<?php
namespace WebSharks\ZenCache\Pro;

/*
 * Current host name.
 *
 * @since 15xxxx Improving multisite compat.
 *
 * @param boolean $consider_domain_mapping Consider?
 *
 * @return string Current HTTP host name.
 */
$self->httpHost = function ($consider_domain_mapping = false) use ($self) {
    $is_multisite                = is_multisite();
    $can_consider_domain_mapping = $self->canConsiderDomainMapping();
    $cache_keys                  = array($consider_domain_mapping, $can_consider_domain_mapping);

    if (!is_null($host = &$self->cacheKey('httpHost', $cache_keys))) {
        return $host; // Already did this.
    }
    if ($is_multisite && $consider_domain_mapping && $can_consider_domain_mapping) {
        return ($host = (string) $self->parseUrl($self->domainMappingSiteUrl(), PHP_URL_HOST));
    }
    if (!empty($_SERVER['HTTP_HOST'])) {
        return ($host = (string) $_SERVER['HTTP_HOST']);
    }
    return ($host = (string) $self->parseUrl(home_url(), PHP_URL_HOST));
};
