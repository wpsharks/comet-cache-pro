<?php
namespace WebSharks\ZenCache\Pro;

/**
 * ZenCache Plugin.
 *
 * @since 150422 Rewrite.
 */
class Plugin extends AbsBaseAp
{
    /**
     * Enable plugin hooks?
     *
     * @since 150422 Rewrite.
     *
     * @type bool If `FALSE`, run without hooks.
     */
    public $enable_hooks = true;

    /**
     * Pro-only option keys.
     *
     * @since 150422 Rewrite.
     *
     * @type array Pro-only option keys.
     */
    public $pro_only_option_keys = array();

    /**
     * Default options.
     *
     * @since 150422 Rewrite.
     *
     * @type array Default options.
     */
    public $default_options = array();

    /**
     * Configured options.
     *
     * @since 150422 Rewrite.
     *
     * @type array Configured options.
     */
    public $options = array();

    /**
     * WordPress capability.
     *
     * @since 150422 Rewrite.
     *
     * @type string WordPress capability.
     */
    public $cap = 'activate_plugins';

    /**
     * WordPress capability.
     *
     * @since 150422 Rewrite.
     *
     * @type string WordPress capability.
     */
    public $update_cap = 'update_plugins';

    /**
     * WordPress capability.
     *
     * @since 150422 Rewrite.
     *
     * @type string WordPress capability.
     */
    public $network_cap = 'manage_network_plugins';

    /**
     * WordPress capability.
     *
     * @since 150422 Rewrite.
     *
     * @type string WordPress capability.
     */
    public $uninstall_cap = 'delete_plugins';

    /**
     * Cache directory.
     *
     * @since 150422 Rewrite.
     *
     * @type string Cache directory; relative to the configured base directory.
     */
    public $cache_sub_dir = 'cache';

    /*[pro strip-from="lite"]*/
    /**
     * HTML Compressor cache directory (public).
     *
     * @since 150422 Rewrite.
     *
     * @type string Public HTML Compressor cache directory; relative to the configured base directory.
     */
    public $htmlc_cache_sub_dir_public = 'htmlc/public';
    /*[/pro]*/

    /*[pro strip-from="lite"]*/
    /**
     * HTML Compressor cache directory (private).
     *
     * @since 150422 Rewrite.
     *
     * @type string Private HTML Compressor cache directory; relative to the configured base directory.
     */
    public $htmlc_cache_sub_dir_private = 'htmlc/private';
    /*[/pro]*/

    /**
     * Plugin constructor.
     *
     * @since 150422 Rewrite.
     *
     * @param bool $enable_hooks Defaults to `TRUE`.
     */
    public function __construct($enable_hooks = true)
    {
        parent::__construct();

        $closures_dir = dirname(dirname(__FILE__)).'/closures/Plugin';
        $self         = $this; // Reference for closures.

        foreach (scandir($closures_dir) as $_closure) {
            if (substr($_closure, -4) === '.php') {
                require $closures_dir.'/'.$_closure;
            }
        }
        unset($_closure); // Housekeeping.
        /* -------------------------------------------------------------- */

        if (!($this->enable_hooks = (boolean) $enable_hooks)) {
            return; // Stop here; construct without hooks.
        }
        /* -------------------------------------------------------------- */

        add_action('after_setup_theme', array($this, 'setup'));
        register_activation_hook(PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(PLUGIN_FILE, array($this, 'deactivate'));
    }

    /**
     * Plugin Setup.
     *
     * @since 150422 Rewrite.
     */
    public function setup()
    {
        if (!is_null($setup = &$this->cacheKey(__FUNCTION__))) {
            return; // Already setup.
        }
        $setup = -1; // Flag as having been setup.

        if ($this->enable_hooks) {
            $this->doWpAction('before_'.GLOBAL_NS.'_'.__FUNCTION__, get_defined_vars());
        }
        /* -------------------------------------------------------------- */

        load_plugin_textdomain(SLUG_TD); // Text domain.

        $this->pro_only_option_keys = array(
            'admin_bar_enable',
            'change_notifications_enable',
            'cache_clear_s2clean_enable',
            'cache_clear_eval_code',
            'when_logged_in',
            'version_salt',

            'htmlc_enable',
            'htmlc_css_exclusions',
            'htmlc_js_exclusions',
            'htmlc_cache_expiration_time',
            'htmlc_compress_combine_head_body_css',
            'htmlc_compress_combine_head_js',
            'htmlc_compress_combine_footer_js',
            'htmlc_compress_combine_remote_css_js',
            'htmlc_compress_inline_js_code',
            'htmlc_compress_css_code',
            'htmlc_compress_js_code',
            'htmlc_compress_html_code',

            'auto_cache_enable',
            'auto_cache_max_time',
            'auto_cache_delay',
            'auto_cache_sitemap_url',
            'auto_cache_other_urls',
            'auto_cache_user_agent',

            'cdn_enable',
            'cdn_host',
            'cdn_hosts',
            'cdn_invalidation_var',
            'cdn_invalidation_counter',
            'cdn_over_ssl',
            'cdn_whitelisted_extensions',
            'cdn_blacklisted_extensions',
            'cdn_whitelisted_uri_patterns',
            'cdn_blacklisted_uri_patterns',

            'dir_stats_enable',

            'pro_update_check',
            'last_pro_update_check',
            'pro_update_username',
            'pro_update_password',
            'last_pro_stats_log',
        );
        $this->default_options = array(
            /* Core/systematic plugin options. */

            'version'     => VERSION,
            'crons_setup' => '0', // `0` or timestamp.

            /* Primary switch; enable? */

            'enable' => '0', // `0|1`.

            /* Related to debugging. */

            'debugging_enable' => '1',
            // `0|1|2` // 2 indicates greater debugging detail.

            /* Related to admin bar. */

            'admin_bar_enable' => '1', // `0|1`.

            /* Related to cache directory. */

            'base_dir'      => 'cache/zencache', // Relative to `WP_CONTENT_DIR`.
            'cache_max_age' => '7 days', // `strtotime()` compatible.

            /* Related to automatic cache clearing. */

            'change_notifications_enable' => '1', // `0|1`.

            'cache_clear_s2clean_enable' => '0', // `0|1`.
            'cache_clear_eval_code'      => '', // PHP code.

            'cache_clear_xml_feeds_enable' => '1', // `0|1`.

            'cache_clear_xml_sitemaps_enable'  => '1', // `0|1`.
            'cache_clear_xml_sitemap_patterns' => '/sitemap*.xml',
            // Empty string or line-delimited patterns.

            'cache_clear_home_page_enable'  => '1', // `0|1`.
            'cache_clear_posts_page_enable' => '1', // `0|1`.

            'cache_clear_custom_post_type_enable' => '1', // `0|1`.
            'cache_clear_author_page_enable'      => '1', // `0|1`.

            'cache_clear_term_category_enable' => '1', // `0|1`.
            'cache_clear_term_post_tag_enable' => '1', // `0|1`.
            'cache_clear_term_other_enable'    => '0', // `0|1`.

            /* Misc. cache behaviors. */

            'allow_browser_cache' => '0', // `0|1`.
            'when_logged_in'      => '0', // `0|1|postload`.
            'get_requests'        => '0', // `0|1`.
            'feeds_enable'        => '0', // `0|1`.
            'cache_404_requests'  => '0', // `0|1`.

            /* Related to exclusions. */

            'exclude_uris'   => '', // Empty string or line-delimited patterns.
            'exclude_refs'   => '', // Empty string or line-delimited patterns.
            'exclude_agents' => 'w3c_validator', // Empty string or line-delimited patterns.

            /* Related to version salt. */

            'version_salt' => '', // Any string value.

            /* Related to HTML compressor. */

            'htmlc_enable'                => '0', // Enable HTML compression?
            'htmlc_css_exclusions'        => '', // Empty string or line-delimited patterns.
            'htmlc_js_exclusions'         => '.php?', // Empty string or line-delimited patterns.
            'htmlc_cache_expiration_time' => '14 days', // `strtotime()` compatible.

            'htmlc_compress_combine_head_body_css' => '1', // `0|1`.
            'htmlc_compress_combine_head_js'       => '1', // `0|1`.
            'htmlc_compress_combine_footer_js'     => '1', // `0|1`.
            'htmlc_compress_combine_remote_css_js' => '1', // `0|1`.
            'htmlc_compress_inline_js_code'        => '1', // `0|1`.
            'htmlc_compress_css_code'              => '1', // `0|1`.
            'htmlc_compress_js_code'               => '1', // `0|1`.
            'htmlc_compress_html_code'             => '1', // `0|1`.

            /* Related to auto-cache engine. */

            'auto_cache_enable'      => '0', // `0|1`.
            'auto_cache_max_time'    => '900', // In seconds.
            'auto_cache_delay'       => '500', // In milliseconds.
            'auto_cache_sitemap_url' => 'sitemap.xml', // Relative to `site_url()`.
            'auto_cache_other_urls'  => '', // A line-delimited list of any other URLs.
            'auto_cache_user_agent'  => 'WordPress',

            /* Related to CDN functionality. */

            'cdn_enable' => '0', // `0|1`; enable CDN filters?

            'cdn_host'  => '', // e.g., `d1v41qemfjie0l.cloudfront.net`
            'cdn_hosts' => '', // e.g., line-delimited list of CDN hosts.

            'cdn_invalidation_var'     => 'iv', // A query string variable name.
            'cdn_invalidation_counter' => '1', // Current version counter.

            'cdn_over_ssl' => '0', // `0|1`; enable SSL compat?

            'cdn_whitelisted_extensions' => '', // Whitelisted extensions.
            // This is a comma-delimited list. Delimiters may include of these: `[|;,\s]`.
            // Defaults to all extensions supported by the WP media library; i.e. `wp_get_mime_types()`.

            'cdn_blacklisted_extensions' => 'eot,ttf,otf,woff', // Blacklisted extensions.
            // This is a comma-delimited list. Delimiters may include of these: `[|;,\s]`.

            'cdn_whitelisted_uri_patterns' => '', // A line-delimited list of inclusion patterns.
            // Wildcards `*` are supported here. Matched against local file URIs.

            'cdn_blacklisted_uri_patterns' => '', // A line-delimited list of exclusion patterns.
            // Wildcards `*` are supported here. Matched against local file URIs.

            /* Related to cache statistics. */

            'dir_stats_enable' => '1', // `0|1`; enable?

            /* Related to automatic pro updates. */

            'pro_update_check'      => '1', // `0|1`; enable?
            'last_pro_update_check' => '0', // Timestamp.

            'pro_update_username' => '', // Username.
            'pro_update_password' => '', // Password or license key.

            /* Related to stats logging. */

            'last_pro_stats_log' => '0', // Timestamp.

            /* Related to uninstallation routines. */

            'uninstall_on_deletion' => '0', // `0|1`.
        );
        $this->default_options = $this->applyWpFilters(GLOBAL_NS.'_default_options', $this->default_options);

        $options = is_array($options = get_site_option(GLOBAL_NS.'_options')) ? $options : array();
        if (!$options && is_array($quick_cache_options = get_site_option('quick_cache_options'))) {
            $options                = $quick_cache_options; // Old Quick Cache options.
            $options['crons_setup'] = $this->default_options['crons_setup'];
        }
        $this->options = array_merge($this->default_options, $options);
        $this->options = $this->applyWpFilters(GLOBAL_NS.'_options', $this->options);
        $this->options = array_intersect_key($this->options, $this->default_options);

        $this->options['base_dir'] = trim($this->options['base_dir'], '\\/'." \t\n\r\0\x0B");
        if (!$this->options['base_dir']) { // Do NOT allow this to be empty--ever!
            $this->options['base_dir'] = $this->default_options['base_dir'];
        }
        $this->cap           = $this->applyWpFilters(GLOBAL_NS.'_cap', $this->cap);
        $this->update_cap    = $this->applyWpFilters(GLOBAL_NS.'_update_cap', $this->update_cap);
        $this->network_cap   = $this->applyWpFilters(GLOBAL_NS.'_network_cap', $this->network_cap);
        $this->uninstall_cap = $this->applyWpFilters(GLOBAL_NS.'_uninstall_cap', $this->uninstall_cap);

        /* -------------------------------------------------------------- */

        if (!$this->enable_hooks) {
            return; // Stop here; setup without hooks.
        }
        /* -------------------------------------------------------------- */

        add_action('init', array($this, 'checkAdvancedCache'));
        add_action('init', array($this, 'checkBlogPaths'));
        add_action('wp_loaded', array($this, 'actions'));

        add_action('admin_init', array($this, 'checkVersion'));

        /*[pro strip-from="lite"]*/
        add_action('admin_init', array($this, 'statsLogPinger'));
        /*[/pro]*/

        /*[pro strip-from="lite"]*/
        add_action('admin_init', array($this, 'checkLatestProVersion'));
        add_filter('fs_ftp_connection_types', array($this, 'fsFtpConnectionTypes'));
        add_filter('pre_site_transient_update_plugins', array($this, 'preSiteTransientUpdatePlugins'));
        /*[/pro]*/

        /*[pro strip-from="lite"]*/
        add_action('admin_bar_menu', array($this, 'adminBarMenu'));
        add_action('wp_head', array($this, 'adminBarMetaTags'), 0);
        add_action('wp_enqueue_scripts', array($this, 'adminBarStyles'));
        add_action('wp_enqueue_scripts', array($this, 'adminBarScripts'));
        /*[/pro]*/

        /*[pro strip-from="lite"]*/
        add_action('admin_head', array($this, 'adminBarMetaTags'), 0);
        add_action('admin_enqueue_scripts', array($this, 'adminBarStyles'));
        add_action('admin_enqueue_scripts', array($this, 'adminBarScripts'));
        /*[/pro]*/

        add_action('admin_enqueue_scripts', array($this, 'enqueueAdminStyles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueAdminScripts'));

        add_action('all_admin_notices', array($this, 'allAdminNotices'));

        add_action('admin_menu', array($this, 'addMenuPages'));
        add_action('network_admin_menu', array($this, 'addNetworkMenuPages'));
        add_filter('plugin_action_links_'.plugin_basename(PLUGIN_FILE), array($this, 'addSettingsLink'));

        add_filter('enable_live_network_counts', array($this, 'updateBlogPaths'));

        add_action('admin_init', array($this, 'autoClearCacheOnSettingChanges'));
        add_action('safecss_save_pre', array($this, 'autoClearCacheOnJetpackCustomCss'), 10, 1);
        add_action('upgrader_process_complete', array($this, 'autoClearOnUpgraderProcessComplete'), 10, 2);

        add_action('switch_theme', array($this, 'autoClearCache'));
        add_action('wp_create_nav_menu', array($this, 'autoClearCache'));
        add_action('wp_update_nav_menu', array($this, 'autoClearCache'));
        add_action('wp_delete_nav_menu', array($this, 'autoClearCache'));

        add_action('save_post', array($this, 'autoClearPostCache'));
        add_action('delete_post', array($this, 'autoClearPostCache'));
        add_action('clean_post_cache', array($this, 'autoClearPostCache'));
        add_action('post_updated', array($this, 'autoClearAuthorPageCache'), 10, 3);
        add_action('pre_post_update', array($this, 'autoClearPostCacheTransition'), 10, 2);

        add_action('added_term_relationship', array($this, 'autoClearPostTermsCache'), 10, 1);
        add_action('delete_term_relationships', array($this, 'autoClearPostTermsCache'), 10, 1);

        add_action('trackback_post', array($this, 'autoClearCommentPostCache'));
        add_action('pingback_post', array($this, 'autoClearCommentPostCache'));
        add_action('comment_post', array($this, 'autoClearCommentPostCache'));
        add_action('transition_comment_status', array($this, 'autoClearCommentPostCacheTransition'), 10, 3);

        add_action('create_term', array($this, 'autoClearCache'));
        add_action('edit_terms', array($this, 'autoClearCache'));
        add_action('delete_term', array($this, 'autoClearCache'));

        add_action('add_link', array($this, 'autoClearCache'));
        add_action('edit_link', array($this, 'autoClearCache'));
        add_action('delete_link', array($this, 'autoClearCache'));

        /*[pro strip-from="lite"]*/
        add_action('profile_update', array($this, 'autoClearUserCacheA1'));
        add_filter('add_user_metadata', array($this, 'autoClearUserCacheFA2'), 10, 2);
        add_filter('update_user_metadata', array($this, 'autoClearUserCacheFA2'), 10, 2);
        add_filter('delete_user_metadata', array($this, 'autoClearUserCacheFA2'), 10, 2);
        add_action('set_auth_cookie', array($this, 'autoClearUserCacheA4'), 10, 4);
        add_action('clear_auth_cookie', array($this, 'autoClearUserCacheCur'));
        /*[/pro]*/

        /*[pro strip-from="lite"]*/
        if ($this->options['enable'] && $this->options['htmlc_enable']) {
            add_action('wp_print_footer_scripts', array($this, 'htmlCFooterScripts'), -PHP_INT_MAX);
            add_action('wp_print_footer_scripts', array($this, 'htmlCFooterScripts'), PHP_INT_MAX);
        }
        /*[/pro]*/

        /*[pro strip-from="lite"]*/
        if ($this->options['enable'] && $this->options['cdn_enable']) {
            add_action('upgrader_process_complete', array($this, 'bumpCdnInvalidationCounter'), 10, 0);
            if (!is_admin()) { // Don't even bother in the admin area.
                new CdnFilters(); // Setup CDN filters.
            }
        }
        /*[/pro]*/
        /* -------------------------------------------------------------- */

        if (!is_multisite() || is_main_site()) { // Main site only.
            add_filter('cron_schedules', array($this, 'extendCronSchedules'));

            if ((integer) $this->options['crons_setup'] < 1439005906 || substr($this->options['crons_setup'], 10) !== '-'.__NAMESPACE__) {
                wp_clear_scheduled_hook('_cron_'.GLOBAL_NS.'_cleanup');
                wp_schedule_event(time() + 60, 'daily', '_cron_'.GLOBAL_NS.'_cleanup');

                /*[pro strip-from="lite"]*/ // Auto-cache engine.
                wp_clear_scheduled_hook('_cron_'.GLOBAL_NS.'_auto_cache');
                wp_schedule_event(time() + 60, 'every15m', '_cron_'.GLOBAL_NS.'_auto_cache');
                /*[/pro]*/

                $this->options['crons_setup'] = time().'-'.__NAMESPACE__;
                update_site_option(GLOBAL_NS.'_options', $this->options);
            }
            add_action('_cron_'.GLOBAL_NS.'_cleanup', array($this, 'purgeCacheDir'));

            /*[pro strip-from="lite"]*/ // Auto-cache engine.
            add_action('_cron_'.GLOBAL_NS.'_auto_cache', array($this, 'autoCache'));
            /*[/pro]*/
        }
        /* -------------------------------------------------------------- */

        $this->doWpAction('after_'.GLOBAL_NS.'_'.__FUNCTION__, get_defined_vars());
        $this->doWpAction(GLOBAL_NS.'_'.__FUNCTION__.'_complete', get_defined_vars());
    }
}
