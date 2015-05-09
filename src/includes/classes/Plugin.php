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

    /**
     * HTML Compressor cache directory (public).
     *
     * @since 150422 Rewrite.
     *
     * @type string Public HTML Compressor cache directory; relative to the configured base directory.
     */
    public $htmlc_cache_sub_dir_public = 'htmlc/public';

    /**
     * HTML Compressor cache directory (private).
     *
     * @since 150422 Rewrite.
     *
     * @type string Private HTML Compressor cache directory; relative to the configured base directory.
     */
    public $htmlc_cache_sub_dir_private = 'htmlc/private';

    /**
     * Used by the plugin's uninstall handler.
     *
     * @since 150422 Rewrite.
     *
     * @type bool If FALSE, run without hooks.
     */
    public $enable_hooks = true;

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
        register_activation_hook($this->file, array($this, 'activate'));
        register_deactivation_hook($this->file, array($this, 'deactivate'));
    }

    /**
     * Plugin Setup.
     *
     * @since 150422 Rewrite.
     */
    public function setup()
    {
        if (!is_null($setup = &$this->staticKey(__FUNCTION__))) {
            return; // Already setup.
        }
        $setup = -1; // Flag as having been setup.

        if ($this->enable_hooks) {
            $this->do_wp_action('before_'.GLOBAL_NS.'_'.__FUNCTION__, get_defined_vars());
        }
        /* -------------------------------------------------------------- */

        load_plugin_textdomain($this->text_domain);

        $this->default_options = array(
            /* Core/systematic plugin options. */

            'version'                              => $this->version,
            'crons_setup'                          => '0', // `0` or timestamp.

            /* Primary switch; enable? */

            'enable'                               => '0', // `0|1`.

            /* Related to debugging. */

            'debugging_enable'                     => '1',
            // `0|1|2` // 2 indicates greater debugging detail.

            /* Related to admin bar. */

            'admin_bar_enable'                     => '1', // `0|1`.

            /* Related to cache directory. */

            'base_dir'                             => 'cache/zencache', // Relative to `WP_CONTENT_DIR`.
            'cache_max_age'                        => '7 days', // `strtotime()` compatible.

            /* Related to automatic cache clearing. */

            'change_notifications_enable'          => '1', // `0|1`.

            'cache_clear_s2clean_enable'           => '0', // `0|1`.
            'cache_clear_eval_code'                => '', // PHP code.

            'cache_clear_xml_feeds_enable'         => '1', // `0|1`.

            'cache_clear_xml_sitemaps_enable'      => '1', // `0|1`.
            'cache_clear_xml_sitemap_patterns'     => '/sitemap*.xml',
            // Empty string or line-delimited patterns.

            'cache_clear_home_page_enable'         => '1', // `0|1`.
            'cache_clear_posts_page_enable'        => '1', // `0|1`.

            'cache_clear_custom_post_type_enable'  => '1', // `0|1`.
            'cache_clear_author_page_enable'       => '1', // `0|1`.

            'cache_clear_term_category_enable'     => '1', // `0|1`.
            'cache_clear_term_post_tag_enable'     => '1', // `0|1`.
            'cache_clear_term_other_enable'        => '0', // `0|1`.

            /* Misc. cache behaviors. */

            'allow_browser_cache'                  => '0', // `0|1`.
            'when_logged_in'                       => '0', // `0|1|postload`.
            'get_requests'                         => '0', // `0|1`.
            'feeds_enable'                         => '0', // `0|1`.
            'cache_404_requests'                   => '0', // `0|1`.

            /* Related to exclusions. */

            'exclude_uris'                         => '', // Empty string or line-delimited patterns.
            'exclude_refs'                         => '', // Empty string or line-delimited patterns.
            'exclude_agents'                       => 'w3c_validator', // Empty string or line-delimited patterns.

            /* Related to version salt. */

            'version_salt'                         => '', // Any string value.

            /* Related to HTML compressor. */

            'htmlc_enable'                         => '0', // Enable HTML compression?
            'htmlc_css_exclusions'                 => '', // Empty string or line-delimited patterns.
            'htmlc_js_exclusions'                  => '.php?', // Empty string or line-delimited patterns.
            'htmlc_cache_expiration_time'          => '14 days', // `strtotime()` compatible.

            'htmlc_compress_combine_head_body_css' => '1', // `0|1`.
            'htmlc_compress_combine_head_js'       => '1', // `0|1`.
            'htmlc_compress_combine_footer_js'     => '1', // `0|1`.
            'htmlc_compress_combine_remote_css_js' => '1', // `0|1`.
            'htmlc_compress_inline_js_code'        => '1', // `0|1`.
            'htmlc_compress_css_code'              => '1', // `0|1`.
            'htmlc_compress_js_code'               => '1', // `0|1`.
            'htmlc_compress_html_code'             => '1', // `0|1`.

            /* Related to auto-cache engine. */

            'auto_cache_enable'                    => '0', // `0|1`.
            'auto_cache_max_time'                  => '900', // In seconds.
            'auto_cache_delay'                     => '500', // In milliseconds.
            'auto_cache_sitemap_url'               => 'sitemap.xml', // Relative to `site_url()`.
            'auto_cache_other_urls'                => '', // A line-delimited list of any other URLs.
            'auto_cache_user_agent'                => 'WordPress',

            /* Related to CDN functionality. */

            'cdn_enable'                           => '0', // `0|1`; enable CDN filters?

            'cdn_host'                             => '', // e.g. `d1v41qemfjie0l.cloudfront.net`

            'cdn_invalidation_var'                 => 'iv', // A query string variable name.
            'cdn_invalidation_counter'             => '1', // Current version counter.

            'cdn_over_ssl'                         => '0', // `0|1`; enable SSL compat?

            'cdn_whitelisted_extensions'           => '', // Whitelisted extensions.
            // This is a comma-delimited list. Delimiters may include of these: `[|;,\s]`.
            // Defaults to all extensions supported by the WP media library; i.e. `wp_get_mime_types()`.

            'cdn_blacklisted_extensions'           => 'eot,ttf,otf,woff', // Blacklisted extensions.
            // This is a comma-delimited list. Delimiters may include of these: `[|;,\s]`.

            'cdn_whitelisted_uri_patterns'         => '', // A line-delimited list of inclusion patterns.
            // Wildcards `*` are supported here. Matched against local file URIs.

            'cdn_blacklisted_uri_patterns'         => '', // A line-delimited list of exclusion patterns.
            // Wildcards `*` are supported here. Matched against local file URIs.

            /* Related to automatic pro updates. */

            'pro_update_check'                     => '1', // `0|1`; enable?
            'last_pro_update_check'                => '0', // Timestamp.

            'pro_update_username'                  => '', // Username.
            'pro_update_password'                  => '', // Password or license key.

            /* Related to uninstallation routines. */

            'uninstall_on_deletion'                => '0', // `0|1`.

        ); // Default options are merged with those defined by the site owner.
        $options               = is_array($options = get_option(GLOBAL_NS.'_options')) ? $options : array();
        if (is_multisite() && is_array($site_options = get_site_option(GLOBAL_NS.'_options'))) {
            $options = array_merge($options, $site_options); // Multisite options.
        }
        if (!$options && is_multisite() && is_array($quick_cache_site_options = get_site_option('quick_cache_options'))) {
            $options                = $quick_cache_site_options;
            $options['crons_setup'] = $this->default_options['crons_setup'];
        }
        if (!$options && is_array($quick_cache_options = get_option('quick_cache_options'))) {
            $options                = $quick_cache_options;
            $options['crons_setup'] = $this->default_options['crons_setup'];
        }
        $this->default_options = $this->apply_wp_filters(GLOBAL_NS.'_default_options', $this->default_options, get_defined_vars());
        $this->options         = array_merge($this->default_options, $options); // This considers old options also.
        $this->options         = $this->apply_wp_filters(GLOBAL_NS.'_options', $this->options, get_defined_vars());
        $this->options         = array_intersect_key($this->options, $this->default_options);

        $this->options['base_dir'] = trim($this->options['base_dir'], '\\/'." \t\n\r\0\x0B");
        if (!$this->options['base_dir']) {
            $this->options['base_dir'] = $this->default_options['base_dir'];
        }
        $this->cap           = $this->apply_wp_filters(GLOBAL_NS.'_cap', $this->cap);
        $this->update_cap    = $this->apply_wp_filters(GLOBAL_NS.'_update_cap', $this->update_cap);
        $this->network_cap   = $this->apply_wp_filters(GLOBAL_NS.'_network_cap', $this->network_cap);
        $this->uninstall_cap = $this->apply_wp_filters(GLOBAL_NS.'_uninstall_cap', $this->uninstall_cap);

        /* -------------------------------------------------------------- */

        if (!$this->enable_hooks) {
            return; // Stop here; setup without hooks.
        }
        /* -------------------------------------------------------------- */

        add_action('init', array($this, 'check_advanced_cache'));
        add_action('init', array($this, 'check_blog_paths'));
        add_action('wp_loaded', array($this, 'actions'));

        add_action('admin_init', array($this, 'check_version'));
        add_action('admin_init', array($this, 'check_latest_pro_version'));
        add_action('admin_init', array($this, 'maybe_auto_clear_cache'));

        add_action('admin_bar_menu', array($this, 'admin_bar_menu'));
        add_action('wp_head', array($this, 'admin_bar_meta_tags'), 0);
        add_action('wp_enqueue_scripts', array($this, 'admin_bar_styles'));
        add_action('wp_enqueue_scripts', array($this, 'admin_bar_scripts'));

        add_action('admin_head', array($this, 'admin_bar_meta_tags'), 0);
        add_action('admin_enqueue_scripts', array($this, 'admin_bar_styles'));
        add_action('admin_enqueue_scripts', array($this, 'admin_bar_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        add_action('all_admin_notices', array($this, 'all_admin_notices'));
        add_action('all_admin_notices', array($this, 'all_admin_errors'));

        add_action('network_admin_menu', array($this, 'add_network_menu_pages'));
        add_action('admin_menu', array($this, 'add_menu_pages'));

        add_action('upgrader_process_complete', array($this, 'upgrader_process_complete'), 10, 2);
        add_action('safecss_save_pre', array($this, 'jetpack_custom_css'), 10, 1);

        add_action('switch_theme', array($this, 'auto_clear_cache'));
        add_action('wp_create_nav_menu', array($this, 'auto_clear_cache'));
        add_action('wp_update_nav_menu', array($this, 'auto_clear_cache'));
        add_action('wp_delete_nav_menu', array($this, 'auto_clear_cache'));

        add_action('save_post', array($this, 'auto_clear_post_cache'));
        add_action('delete_post', array($this, 'auto_clear_post_cache'));
        add_action('clean_post_cache', array($this, 'auto_clear_post_cache'));
        add_action('post_updated', array($this, 'auto_clear_author_page_cache'), 10, 3);
        add_action('pre_post_update', array($this, 'auto_clear_post_cache_transition'), 10, 2);

        add_action('added_term_relationship', array($this, 'auto_clear_post_terms_cache'), 10, 1);
        add_action('delete_term_relationships', array($this, 'auto_clear_post_terms_cache'), 10, 1);

        add_action('trackback_post', array($this, 'auto_clear_comment_post_cache'));
        add_action('pingback_post', array($this, 'auto_clear_comment_post_cache'));
        add_action('comment_post', array($this, 'auto_clear_comment_post_cache'));
        add_action('transition_comment_status', array($this, 'auto_clear_comment_transition'), 10, 3);

        add_action('profile_update', array($this, 'auto_clear_user_cache_a1'));
        add_filter('add_user_metadata', array($this, 'auto_clear_user_cache_fa2'), 10, 2);
        add_filter('update_user_metadata', array($this, 'auto_clear_user_cache_fa2'), 10, 2);
        add_filter('delete_user_metadata', array($this, 'auto_clear_user_cache_fa2'), 10, 2);
        add_action('set_auth_cookie', array($this, 'auto_clear_user_cache_a4'), 10, 4);
        add_action('clear_auth_cookie', array($this, 'auto_clear_user_cache_cur'));

        add_action('create_term', array($this, 'auto_clear_cache'));
        add_action('edit_terms', array($this, 'auto_clear_cache'));
        add_action('delete_term', array($this, 'auto_clear_cache'));

        add_action('add_link', array($this, 'auto_clear_cache'));
        add_action('edit_link', array($this, 'auto_clear_cache'));
        add_action('delete_link', array($this, 'auto_clear_cache'));

        add_filter('enable_live_network_counts', array($this, 'update_blog_paths'));

        add_filter('fs_ftp_connection_types', array($this, 'fs_ftp_connection_types'));
        add_filter('pre_site_transient_update_plugins', array($this, 'pre_site_transient_update_plugins'));

        add_filter('plugin_action_links_'.plugin_basename($this->file), array($this, 'add_settings_link'));

        if ($this->options['enable'] && $this->options['htmlc_enable']) {
            add_action('wp_print_footer_scripts', array($this, 'htmlc_footer_scripts'), -PHP_INT_MAX);
            add_action('wp_print_footer_scripts', array($this, 'htmlc_footer_scripts'), PHP_INT_MAX);
        }
        if ($this->options['enable'] && $this->options['cdn_enable']) {
            add_action('upgrader_process_complete', array($this, 'bump_cdn_invalidation_counter'), 10, 0);
            new CdnFilters(); // Setup CDN filters.
        }
        /* -------------------------------------------------------------- */

        add_filter('cron_schedules', array($this, 'extend_cron_schedules'));

        if (substr($this->options['crons_setup'], -4) !== '-pro' || (integer) $this->options['crons_setup'] < 1398051975) {
            wp_clear_scheduled_hook('_cron_'.GLOBAL_NS.'_auto_cache');
            wp_schedule_event(time() + 60, 'every15m', '_cron_'.GLOBAL_NS.'_auto_cache');

            wp_clear_scheduled_hook('_cron_'.GLOBAL_NS.'_cleanup');
            wp_schedule_event(time() + 60, 'daily', '_cron_'.GLOBAL_NS.'_cleanup');

            $this->options['crons_setup'] = time().'-'.($this->is_pro ? '-pro' : '');
            update_option(GLOBAL_NS.'_options', $this->options);
            if (is_multisite()) {
                update_site_option(GLOBAL_NS.'_options', $this->options);
            }
        }
        add_action('_cron_'.GLOBAL_NS.'_auto_cache', array($this, 'auto_cache'));
        add_action('_cron_'.GLOBAL_NS.'_cleanup', array($this, 'purge_cache'));

        /* -------------------------------------------------------------- */

        $this->do_wp_action('after_'.GLOBAL_NS.'_'.__FUNCTION__, get_defined_vars());
        $this->do_wp_action(GLOBAL_NS.'_'.__FUNCTION__.'_complete', get_defined_vars());
    }
}
