<?php
/*[pro strip-from="lite"]*/
namespace WebSharks\CometCache\Pro\Traits\Plugin;

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
    if (!$self->autoCacheCheckPhpIni()) {
        return; // Server does not meet minimum requirements.
    }
    new AutoCache();
};

/**
 * Check if PHP configuration meets minimum requirements for Auto-Cache Engine and remove old notice if necessary.
 *
 * @since 160103 Improving Auto-Cache Engine minimum PHP requirements reporting.
 *
 * @attaches-to `admin_init`
 */
$self->autoCacheMaybeClearPhpIniError = function () use ($self) {
    if (!is_null($done = &$self->cacheKey('autoCacheMaybeClearPhpIniError'))) {
        return; // Already did this.
    }
    $done = true; // Flag as having been done.

    if (!$self->options['enable']) {
        return; // Nothing to do.
    }
    if (!$self->options['auto_cache_enable']) {
        return; // Nothing to do.
    }
    $self->autoCacheCheckPhpIni();
};

/**
 * Check if PHP configuration meets minimum requirements for Auto-Cache Engine and display a notice if necessary.
 *
 * @since 160103 Improving Auto-Cache Engine minimum PHP requirements reporting.
 *
 * @return bool `TRUE` if all required PHP configuration is present, else `FALSE`. This also creates a dashboard notice in some cases.
 *
 * @note  Unlike `autoCacheCheckXmlSitemap()`, this routine is NOT used by the Auto-Cache Engine class when the Auto-Cache Engine is running.
 *        However, this routine is called prior to running the Auto-Cache Engine, so caching here should be avoided (this gets called during
 *        `admin_init` and prior to running the Auto-Cache Engine).
 */
$self->autoCacheCheckPhpIni = function () use ($self) {
    if (!filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN)) { // Is allow_url_fopen=1?
        $self->dismissMainNotice('allow_url_fopen_disabled'); // Clear any previous allow_url_fopen notice.
        $self->enqueueMainNotice(
          sprintf(__('<strong>%1$s says...</strong> The Auto-Cache Engine requires <a href="http://cometcache.com/r/allow_url_fopen/" target="_blank">PHP URL-aware fopen wrappers</a> (<code>allow_url_fopen=1</code>), however this option has been disabled by your <code>php.ini</code> runtime configuration. Please contact your web hosting company to resolve this issue or disable the Auto-Cache Engine in the <a href="'.esc_attr(add_query_arg(urlencode_deep(array('page' => GLOBAL_NS)), self_admin_url('/admin.php'))).'">settings</a>.', SLUG_TD), esc_html(NAME)),
          array('class' => 'error', 'persistent_key' => 'allow_url_fopen_disabled', 'dismissable' => false)
        );
        return false; // Nothing more we can do in this case.
    }
    $self->dismissMainNotice('allow_url_fopen_disabled'); // Any previous problems have been fixed; dismiss any existing failure notice

    return true;
};

/**
 * Check if Auto-Cache Engine XML Sitemap is valid and remove old notice if necessary.
 *
 * @since 151220 Improving XML Sitemap error checking.
 *
 * @attaches-to `admin_init`
 */
$self->autoCacheMaybeClearPrimaryXmlSitemapError = function () use ($self) {
    if (!is_null($done = &$self->cacheKey('autoCacheMaybeClearPrimaryXmlSitemapError'))) {
        return; // Already did this.
    }
    $done = true; // Flag as having been done.

    if (!$self->options['enable']) {
        return; // Nothing to do.
    }
    if (!$self->options['auto_cache_enable']) {
        return; // Nothing to do.
    }
    if (!$self->options['auto_cache_sitemap_url']) {
        return; // Nothing to do.
    }
    if(($last_checked = get_transient(GLOBAL_NS.'-'.md5($self->options['auto_cache_sitemap_url']))) && (time() <= ((int)$last_checked + HOUR_IN_SECONDS))) {
        $self->dismissMainNotice('xml_sitemap_missing'); // Previous error was fixed; we only create transient when Sitemap passes validation
        return; // Nothing to do; already checked within the last hour.
    }
    $is_multisite                = is_multisite(); // Multisite network?
    $can_consider_domain_mapping = $is_multisite && $self->canConsiderDomainMapping();
    $blog_url                   = rtrim(network_home_url(''), '/');

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
 * @since 151220 Improving XML Sitemap error checking.
 *
 * @param string    $sitemap           A URL to an XML sitemap file.
 *                                     This supports nested XML sitemap index files too; i.e. `<sitemapindex>`.
 *                                     Note that GZIP files are NOT supported at this time.
 * @param bool      $is_nested_sitemap Are we traversing a primary sitemap and now dealing with a nested sitemap?
 * @param bool|null $is_child_blog     Is this routine being called from a child blog?
 *
 * @return bool `TRUE` if there was no failure fetching XML Sitemap, else `FALSE`. This also creates a dashboard notice in some cases.
 *
 * @note  This routine is also used by the AutoCache class when the Auto-Cache Engine is running.
 */
$self->autoCacheCheckXmlSitemap = function ($sitemap, $is_nested_sitemap = false, $is_child_blog = null) use ($self) {
    $failure = ''; // Initialize.

    if (is_wp_error($head = wp_remote_head($sitemap, array('redirection' => 5)))) {
        $failure = 'WP_Http says: '.$head->get_error_message().'.';
        if(stripos($head->get_error_message(), 'timed out') !== false || stripos($head->get_error_message(), 'timeout') !== false) { // $head->get_error_code() only returns generic `http_request_failed`
            $failure .= '<br /><em>'.__('Note: Most timeout errors are resolved by refreshing the page and trying again. If timeout errors persist, please see <a href="http://cometcache.com/r/kb-article-why-am-i-seeing-a-timeout-error/" target="_blank">this article</a>.', SLUG_TD).'</em>';
        }
    } elseif (empty($head['response']['code']) || (int)$head['response']['code'] >= 400) {
        $failure = sprintf(__('HEAD response code (<code>%1$s</code>) indicates an error.', SLUG_TD), esc_html((int)@$head['response']['code']));
    } elseif (empty($head['headers']['content-type']) || stripos($head['headers']['content-type'], 'xml') === false) {
        $failure = sprintf(__('Content-Type (<code>%1$s</code>) indicates an error.', SLUG_TD), esc_html((string)@$head['headers']['content-type']));
    }
    if ($failure) { // Failure encountered above?
        if (!$is_child_blog && !$is_nested_sitemap && $self->options['auto_cache_sitemap_url']) { // If this is a primary sitemap location.
            $self->dismissMainNotice('xml_sitemap_missing'); // Clear any previous XML Sitemap notice, which may reference an old URL; see http://wsharks.com/1SAofhP
            $self->enqueueMainNotice(
              sprintf(__('<strong>%1$s says...</strong> The Auto-Cache Engine is currently configured with an XML Sitemap location that could not be found. We suggest that you install the <a href="http://cometcache.com/r/google-xml-sitemaps-plugin/" target="_blank">Google XML Sitemaps</a> plugin. Or, empty the XML Sitemap field and only use the list of URLs instead. See: <strong>Dashboard → %1$s → Auto-Cache Engine → XML Sitemap URL</strong>', SLUG_TD), esc_html(NAME)).'</p><hr />'.
              sprintf(__('<p><strong>Problematic Sitemap URL:</strong> <a href="%1$s" target="_blank">%1$s</a> / <strong>Diagnostic Report:</strong> %2$s', SLUG_TD), esc_html($sitemap), $failure),
              array('class' => 'error', 'persistent_key' => 'xml_sitemap_missing', 'dismissable' => false)
            );
            delete_transient(GLOBAL_NS.'-'.md5($self->options['auto_cache_sitemap_url'])); // Ensures that we check the XML Sitemap URL again immediately until the issue is fixed
        }
        return false; // Nothing more we can do in this case.
    }

    if (!$is_child_blog && !$is_nested_sitemap) { // Any previous problems have been fixed; dismiss any existing failure notice
        $self->dismissMainNotice('xml_sitemap_missing');
        set_transient(GLOBAL_NS.'-'.md5($self->options['auto_cache_sitemap_url']), time(), WEEK_IN_SECONDS); // Reduce repeated validation attempts.
    }

    return true;
};
/*[/pro]*/
