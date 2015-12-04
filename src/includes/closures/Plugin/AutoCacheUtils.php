<?php
/*[pro strip-from="lite"]*/
namespace WebSharks\ZenCache\Pro;

/*
 * Runs the auto-cache engine via CRON job.
 *
 * @since 150422 Rewrite.
 *
 * @attaches-to `_cron_'.__GLOBAL_NS__.'_auto_cache`
 */
$self->autoCache = function () use ($self) {
    if (!$self->options['enable']) {
        return; // Nothing to do.
    }
    if (!$self->options['auto_cache_enable']) {
        return; // Nothing to do.
    }
    if (!$self->options['auto_cache_sitemap_url']) {
        if (!$self->options['auto_cache_other_urls']) {
            return; // Nothing to do.
        }
    }
    new AutoCache();
};

/**
 * Check if Auto-Cache Engine XML Sitemap is valid and remove old notice if necessary.
 *
 * @since 15xxxx Improving XML Sitemap error checking.
 *
 * @param bool $force Defaults to a FALSE value.
 *
 * @attaches-to `admin_init`
 *
 * @note This routine is also called from `saveOptions()`.
 */
$self->autoCacheMaybeClearPrimaryXmlSitemapError = function ($force = false) use ($self) {
    if ($force) {
        $self->dismissMainNotice('xml_sitemap_missing');
        return; // Nothing else to do.
    }
    if (!$self->options['enable']) {
        return; // Nothing to do.
    }
    if (!$self->options['auto_cache_enable']) {
        return; // Nothing to do.
    }
    if (!$self->options['auto_cache_sitemap_url']) {
        return; // Nothing to do.
    }
    $is_multisite                = is_multisite(); // Multisite network?
    $can_consider_domain_mapping = $is_multisite && $self->canConsiderDomainMapping();
    $blog_url                   = rtrim(network_home_url('', 'http'), '/');

    if ($is_multisite && $can_consider_domain_mapping) {
        $blog_url = $self->domainMappingUrlFilter($blog_url);
    }
    if ($blog_url && ($blog_sitemap_path = ltrim($self->options['auto_cache_sitemap_url'], '/'))) {
        $self->autoCacheCheckXmlSitemap($blog_url.'/'.$blog_sitemap_path, false, false);
    }
};

/**
 * Check if Auto-Cache Engine XML Sitemap is valid and display a notice if necessary.
 *
 * @since 15xxxx Improving XML Sitemap error checking.
 *
 * @param string    $sitemap       A URL to an XML sitemap file.
 *                                 This supports nested XML sitemap index files too; i.e. `<sitemapindex>`.
 *                                 Note that GZIP files are NOT supported at this time.
 * @param bool      $___recursive  Is this routine being called as part of a recursive routine?
 * @param bool|null $is_child_blog Is this routine being called from a child blog?
 *
 * @return bool `TRUE` if there was no failure fetching XML Sitemap, else `FALSE`. This also creates a dashboard notice in some cases.
 *
 * @note This routine is also used by the AutoCache class when the Auto-Cache Engine is running.
 */
$self->autoCacheCheckXmlSitemap = function ($sitemap, $___recursive = false, $is_child_blog = null) use ($self) {
    $failure = ''; // Initialize.

    if (is_wp_error($head = wp_remote_head($sitemap, array('redirection' => 5)))) {
        $failure = 'WP_Http says: '.$head->get_error_message().'.';
    } elseif (empty($head['response']['code']) || (int)$head['response']['code'] >= 400) {
        $failure = sprintf(__('HEAD response code (<code>%1$s</code>) indicates an error.', SLUG_TD), esc_html((int)@$head['response']['code']));
    } elseif (empty($head['headers']['content-type']) || stripos($head['headers']['content-type'], 'xml') === false) {
        $failure = sprintf(__('Content-Type (<code>%1$s</code>) indicates an error.', SLUG_TD), esc_html((string)@$head['headers']['content-type']));
    }
    if ($failure) { // Failure encountered above?
        if (!$is_child_blog && !$___recursive && $self->options['auto_cache_sitemap_url']) { // If this is a primary sitemap location.
            $self->dismissMainNotice('xml_sitemap_missing'); // Clear any previous XML Sitemap notice, which may reference an old URL; see http://wsharks.com/1SAofhP
            $self->enqueueMainNotice(
              sprintf(__('<strong>%1$s says...</strong> The Auto-Cache Engine is currently configured with an XML Sitemap location that could not be found. We suggest that you install the <a href="http://zencache.com/r/google-xml-sitemaps-plugin/" target="_blank">Google XML Sitemaps</a> plugin. Or, empty the XML Sitemap field and only use the list of URLs instead. See: <strong>Dashboard → %1$s → Auto-Cache Engine → XML Sitemap URL</strong>', SLUG_TD), esc_html(NAME)).'</p><hr />'.
              sprintf(__('<p><strong>Problematic Sitemap URL:</strong> <a href="%1$s" target="_blank">%1$s</a> / <strong>Diagnostic Report:</strong> %2$s', SLUG_TD), esc_html($sitemap), $failure),
              array('class' => 'error', 'persistent_key' => 'xml_sitemap_missing', 'dismissable' => false)
            );
        }
        return false; // Nothing more we can do in this case.
    }

    if (!$is_child_blog && !$___recursive) { // Any previous problems have been fixed; dismiss any existing failure notice
        $self->dismissMainNotice('xml_sitemap_missing');
    }

    return true;
};
/*[/pro]*/
