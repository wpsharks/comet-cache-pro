<?php
namespace WebSharks\CometCache\Traits\Plugin;

use WebSharks\CometCache\Classes;

trait AdminBarUtils
{
    /**
     * Showing admin bar.
     *
     * @since 16xxxx Improving admin bar.
     *
     * @param bool $feature Check something specific?
     *
     * @return bool True if showing.
     */
    public function adminBarShowing($feature = '')
    {
        $feature = trim(mb_strtolower((string) $feature));
        if (!is_null($showing = &$this->cacheKey('adminBarShowing', $feature))) {
            return $showing; // Already cached this.
        }


        if ($showing) {

            $current_user_can_clear_cache = $this->currentUserCanClearCache();

                case 'cache_clear':
                case 'cache_clear_options':
                    $showing = $current_user_can_clear_cache;
                    break;


                default: // Default case handler.
                    $showing = $current_user_can_clear_cache

                    break;
            }
        }
        return $showing;
    }

    /**
     * Filter WordPress admin bar.
     *
     * @since 16xxxx Rewrite.
     *
     * @attaches-to `admin_bar_menu` hook.
     *
     * @param $wp_admin_bar \WP_Admin_Bar
     */
    public function adminBarMenu(\WP_Admin_Bar &$wp_admin_bar)
    {
        if (!$this->adminBarShowing()) {
            return; // Nothing to do.
        }
        if ($this->adminBarShowing('cache_wipe')) {
            $wp_admin_bar->add_menu(
                [
                    'parent' => 'top-secondary',
                    'id'     => GLOBAL_NS.'-wipe',

                    'title' => __('Wipe', SLUG_TD),
                    'href'  => '#',

                    'meta' => [
                        'title'    => __('Wipe Cache (Start Fresh). Clears the cache for all sites in this network at once!', SLUG_TD),
                        'class'    => '-wipe',
                        'tabindex' => -1,
                    ],
                ]
            );
        }
        if ($this->adminBarShowing('cache_clear')) {
            if (($cache_clear_options_showing = $this->adminBarShowing('cache_clear_options'))) {
                $cache_clear_options = '<li class="-home-url-only"><a href="#" title="'.__('Clear the Home Page cache', SLUG_TD).'">'.__('Home Page', SLUG_TD).'</a></li>';

            } else {
                $cache_clear_options = ''; // Empty in this default case.
            }

            }

            }
        }
    }

    /**
     * Injects `<meta>` tag w/ JSON-encoded data.
     *
     * @since 150422 Rewrite.
     *
     * @attaches-to `admin_head` hook.
     */
    public function adminBarMetaTags()
    {
        if (!$this->adminBarShowing()) {
            return; // Nothing to do.
        }
        $vars = [
            '_wpnonce'                 => wp_create_nonce(),
            ],
        ];
        echo '<meta property="'.esc_attr(GLOBAL_NS).':admin-bar-vars" content="data-json"'.
             ' data-json="'.esc_attr(json_encode($vars)).'" id="'.esc_attr(GLOBAL_NS).'-admin-bar-vars" />'."\n";
    }

    /**
     * Adds CSS for WordPress admin bar.
     *
     * @since 16xxxx Rewrite.
     *
     * @attaches-to `wp_enqueue_scripts` hook.
     * @attaches-to `admin_enqueue_scripts` hook.
     */
    public function adminBarStyles()
    {
        if (!$this->adminBarShowing()) {
            return; // Nothing to do.
        }
        $deps = []; // Plugin dependencies.

        wp_enqueue_style(GLOBAL_NS.'-admin-bar', $this->url('/src/client-s/css/admin-bar.min.css'), $deps, VERSION, 'all');
    }

    /**
     * Adds JS for WordPress admin bar.
     *
     * @since 16xxxx Rewrite.
     *
     * @attaches-to `wp_enqueue_scripts` hook.
     * @attaches-to `admin_enqueue_scripts` hook.
     */
    public function adminBarScripts()
    {
        if (!$this->adminBarShowing()) {
            return; // Nothing to do.
        }
        $deps = ['jquery', 'admin-bar']; // Plugin dependencies.

        wp_enqueue_script(GLOBAL_NS.'-admin-bar', $this->url('/src/client-s/js/admin-bar.min.js'), $deps, VERSION, true);
    }
}

/*[pro strip-from="lite"]*/
namespace WebSharks\CometCache\Pro\Traits\Plugin;

use WebSharks\CometCache\Pro\Classes;

trait AdminBarUtils
{
    /**
     * Showing admin bar.
     *
     * @since 151002 Improving admin bar.
     *
     * @param bool $feature Check something specific?
     *
     * @return bool True if showing.
     */
    public function adminBarShowing($feature = '')
    {
        $feature = trim(mb_strtolower((string) $feature));
        if (!is_null($showing = &$this->cacheKey('adminBarShowing', $feature))) {
            return $showing; // Already cached this.
        }
        $is_multisite = is_multisite(); // Call this once only.

        if (($showing = $this->options['enable'] && is_admin_bar_showing())) {
            switch ($feature) {
                case 'cache_wipe':
                    $showing = $this->options['cache_clear_admin_bar_enable'] && $is_multisite;
                    break;

                case 'cache_clear':
                case 'cache_clear_options':
                    $showing = $this->options['cache_clear_admin_bar_enable'] && (!$is_multisite || !is_network_admin() || $this->isMenuPage(GLOBAL_NS.'*'));
                    // `$this->isMenuPage(GLOBAL_NS.'*')` shows "Cache Clear" button in Network Admin when configuring options; i.e., avoids confusion.
                    if ($feature === 'cache_clear_options') {
                        $showing = $showing && $this->options['cache_clear_admin_bar_options_enable'];
                    }
                    break;

                case 'stats':
                    $showing = $this->options['stats_enable'] && $this->options['stats_admin_bar_enable'];
                    break;

                default: // Default case handler.
                    $showing = ($this->options['cache_clear_admin_bar_enable'] && $is_multisite)
                               || ($this->options['cache_clear_admin_bar_enable'] && (!$is_multisite || !is_network_admin() || $this->isMenuPage(GLOBAL_NS.'*')))
                               || ($this->options['stats_enable'] && $this->options['stats_admin_bar_enable']);
                    break;
            }
        }
        if ($showing) {
            $current_user_can_wipe_cache  = $is_multisite && current_user_can($this->network_cap);
            $current_user_can_clear_cache = $this->currentUserCanClearCache();
            $current_user_can_see_stats   = $this->currentUserCanSeeStats();

            switch ($feature) {
                case 'cache_wipe':
                    $showing = $current_user_can_wipe_cache;
                    break;

                case 'cache_clear':
                case 'cache_clear_options':
                    $showing = $current_user_can_clear_cache;
                    break;

                case 'stats':
                    $showing = $current_user_can_see_stats;
                    break;

                default: // Default case handler.
                    $showing = $current_user_can_wipe_cache
                               || $current_user_can_clear_cache
                               || $current_user_can_see_stats;
                    break;
            }
        }
        return $showing;
    }

    /**
     * Filter WordPress admin bar.
     *
     * @since 150422 Rewrite.
     *
     * @attaches-to `admin_bar_menu` hook.
     *
     * @param $wp_admin_bar \WP_Admin_Bar
     */
    public function adminBarMenu(\WP_Admin_Bar &$wp_admin_bar)
    {
        if (!$this->adminBarShowing()) {
            return; // Nothing to do.
        }
        if ($this->adminBarShowing('cache_wipe')) {
            $wp_admin_bar->add_menu(
                [
                    'parent' => 'top-secondary',
                    'id'     => GLOBAL_NS.'-wipe',

                    'title' => __('Wipe', SLUG_TD),
                    'href'  => '#',

                    'meta' => [
                        'title'    => __('Wipe Cache (Start Fresh). Clears the cache for all sites in this network at once!', SLUG_TD),
                        'class'    => '-wipe',
                        'tabindex' => -1,
                    ],
                ]
            );
        }
        if ($this->adminBarShowing('cache_clear')) {
            if (($cache_clear_options_showing = $this->adminBarShowing('cache_clear_options'))) {
                $cache_clear_options = '<li class="-home-url-only"><a href="#" title="'.__('Clear the Home Page cache', SLUG_TD).'">'.__('Home Page', SLUG_TD).'</a></li>';

                if (!is_admin()) {
                    $cache_clear_options .= '<li class="-current-url-only"><a href="#" title="'.__('Clear the cache for the current URL', SLUG_TD).'">'.__('Current URL', SLUG_TD).'</a></li>';
                }
                $cache_clear_options .= '<li class="-specific-url-only"><a href="#" title="'.__('Clear the cache for a specific URL', SLUG_TD).'">'.__('Specific URL', SLUG_TD).'</a></li>';

                if ($this->functionIsPossible('opcache_reset') && $this->currentUserCanClearOpCache()) {
                    $cache_clear_options .= '<li class="-opcache-only"><a href="#" title="'.__('Clear PHP\'s OPcache', SLUG_TD).'">'.__('OPcache', SLUG_TD).'</a></li>';
                }
                if ($this->options['cdn_enable'] && $this->currentUserCanClearCdnCache()) {
                    $cache_clear_options .= '<li class="-cdn-only"><a href="#" title="'.__('Clear the CDN cache', SLUG_TD).'">'.__('CDN Cache', SLUG_TD).'</a></li>';
                }
                if ($this->currentUserCanClearExpiredTransients()) {
                    $cache_clear_options .= '<li class="-transients-only"><a href="#" title="'.__('Clear expired transients from the database', SLUG_TD).'">'.__('Expired Transients', SLUG_TD).'</a></li>';
                }
            } else {
                $cache_clear_options = ''; // Empty in this default case.
            }
            if ($cache_clear_options && $this->options['cache_clear_admin_bar_options_enable'] === '2') {
                $wp_admin_bar->add_menu(
                    [
                        'parent' => 'top-secondary',
                        'id'     => GLOBAL_NS.'-clear-options',

                        'title' => '',
                        'href'  => '#',
                        'meta'  => [
                            'title'    => __('Clear Options', SLUG_TD),
                            'class'    => '-clear-options',
                            'tabindex' => -1,
                        ],
                    ]
                );
                $wp_admin_bar->add_group(
                    [
                        'parent' => GLOBAL_NS.'-clear-options',
                        'id'     => GLOBAL_NS.'-clear-options-wrapper',

                        'meta' => [
                            'class' => '-wrapper',
                        ],
                    ]
                );
                $wp_admin_bar->add_menu(
                    [
                        'parent' => GLOBAL_NS.'-clear-options-wrapper',
                        'id'     => GLOBAL_NS.'-clear-options-container',

                        'title' => '<div class="-label">'.
                                   '   <span class="-text">'.__('Clear Cache', SLUG_TD).'</span>'.
                                   '</div>'.

                                   '<ul class="-options">'.
                                   '   '.$cache_clear_options.
                                   '</ul>'.

                                   '<div class="-spacer"></div>',

                        'meta' => [
                            'class'    => '-container',
                            'tabindex' => -1,
                        ],
                    ]
                );
            }
            $wp_admin_bar->add_menu(
                [
                    'parent' => 'top-secondary',
                    'id'     => GLOBAL_NS.'-clear',

                    'title' => __('Clear Cache', SLUG_TD),
                    'href'  => '#',
                    'meta'  => [
                        'title' => is_multisite() && current_user_can($this->network_cap)
                            ? __('Clear Cache (Start Fresh). Affects the current site only.', SLUG_TD)
                            : '',
                        'class'    => '-clear',
                        'tabindex' => -1,
                    ],
                ]
            );
            if ($cache_clear_options && $this->options['cache_clear_admin_bar_options_enable'] === '1') {
                $wp_admin_bar->add_group(
                    [
                        'parent' => GLOBAL_NS.'-clear',
                        'id'     => GLOBAL_NS.'-clear-options-wrapper',

                        'meta' => [
                            'class' => '-wrapper',
                        ],
                    ]
                );
                $wp_admin_bar->add_menu(
                    [
                        'parent' => GLOBAL_NS.'-clear-options-wrapper',
                        'id'     => GLOBAL_NS.'-clear-options-container',

                        'title' => '<ul class="-options">'.
                                   '   '.$cache_clear_options.
                                   '</ul>'.

                                   '<div class="-spacer"></div>',

                        'meta' => [
                            'class'    => '-container',
                            'tabindex' => -1,
                        ],
                    ]
                );
            }
        }
        if ($this->adminBarShowing('stats')) {
            $wp_admin_bar->add_menu(
                [
                    'parent' => 'top-secondary',
                    'id'     => GLOBAL_NS.'-stats',

                    'title' => __('Cache Stats', SLUG_TD),
                    'href'  => esc_attr(add_query_arg(urlencode_deep(['page' => GLOBAL_NS.'-stats']), network_admin_url('/admin.php'))).'',

                    'meta' => [
                        'class'    => '-stats',
                        'tabindex' => -1,
                    ],
                ]
            );
            $wp_admin_bar->add_group(
                [
                    'parent' => GLOBAL_NS.'-stats',
                    'id'     => GLOBAL_NS.'-stats-wrapper',

                    'meta' => [
                        'class' => '-wrapper',
                    ],
                ]
            );
            $wp_admin_bar->add_menu(
                [
                    'parent' => GLOBAL_NS.'-stats-wrapper',
                    'id'     => GLOBAL_NS.'-stats-container',

                    'title' => '<div class="-refreshing"></div>'.

                               '<canvas class="-chart-a"></canvas>'.
                               // '<canvas class="-chart-b"></canvas>'.

                               '<div class="-totals">'.
                               '  <div class="-heading">'.__('Current Cache Totals', SLUG_TD).'</div>'.
                               '  <div class="-files"><span class="-value">&nbsp;</span></div>'.
                               '  <div class="-size"><span class="-value">&nbsp;</span></div>'.
                               '  <div class="-dir">'.esc_html(basename(WP_CONTENT_DIR).'/'.$this->options['base_dir'].'/*').'</div>'.
                               '</div>'.

                               '<div class="-disk">'.
                               '  <div class="-heading">'.__('Current Disk Health', SLUG_TD).'</div>'.
                               '  <div class="-size"><span class="-value">&nbsp;</span> '.__('total capacity', SLUG_TD).'</div>'.
                               '  <div class="-free"><span class="-value">&nbsp;</span> '.__('available', SLUG_TD).'</div>'.
                               '</div>'.

                               (current_user_can($this->cap) ?
                                   '<div class="-more-info">'.
                                   '  <a href="'.esc_attr(add_query_arg(urlencode_deep(['page' => GLOBAL_NS.'-stats']), network_admin_url('/admin.php'))).'">'.__('More Info', SLUG_TD).'</a>'.
                                   '</div>'
                                   : '').

                               '<div class="-spacer"></div>',

                    'meta' => [
                        'class'    => '-container',
                        'tabindex' => -1,
                    ],
                ]
            );
        }
    }

    /**
     * Injects `<meta>` tag w/ JSON-encoded data.
     *
     * @since 150422 Rewrite.
     *
     * @attaches-to `admin_head` hook.
     */
    public function adminBarMetaTags()
    {
        if (!$this->adminBarShowing()) {
            return; // Nothing to do.
        }
        $vars = [
            '_wpnonce'                 => wp_create_nonce(),
            'isMultisite'              => is_multisite(),
            'currentUserHasCap'        => current_user_can($this->cap),
            'currentUserHasNetworkCap' => current_user_can($this->network_cap),
            'htmlCompressorEnabled'    => (boolean) $this->options['htmlc_enable'],
            'ajaxURL'                  => site_url('/wp-load.php', is_ssl() ? 'https' : 'http'),
            'i18n'                     => [
                'name'             => NAME,
                'perSymbol'        => __('%', SLUG_TD),
                'file'             => __('file', SLUG_TD),
                'files'            => __('files', SLUG_TD),
                'pageCache'        => __('Page Cache', SLUG_TD),
                'htmlCompressor'   => __('HTML Compressor', SLUG_TD),
                'currentTotal'     => __('Current Total', SLUG_TD),
                'currentSite'      => __('Current Site', SLUG_TD),
                'xDayHigh'         => __('%s Day High', SLUG_TD),
                'enterSpecificUrl' => __('Enter a specific URL to clear the cache for that page:', SLUG_TD),
            ],
        ];
        echo '<meta property="'.esc_attr(GLOBAL_NS).':admin-bar-vars" content="data-json"'.
             ' data-json="'.esc_attr(json_encode($vars)).'" id="'.esc_attr(GLOBAL_NS).'-admin-bar-vars" />'."\n";
    }

    /**
     * Adds CSS for WordPress admin bar.
     *
     * @since 150422 Rewrite.
     *
     * @attaches-to `wp_enqueue_scripts` hook.
     * @attaches-to `admin_enqueue_scripts` hook.
     */
    public function adminBarStyles()
    {
        if (!$this->adminBarShowing()) {
            return; // Nothing to do.
        }
        $deps = []; // Plugin dependencies.

        wp_enqueue_style(GLOBAL_NS.'-admin-bar', $this->url('/src/client-s/css/admin-bar.min.css'), $deps, VERSION, 'all');
    }

    /**
     * Adds JS for WordPress admin bar.
     *
     * @since 150422 Rewrite.
     *
     * @attaches-to `wp_enqueue_scripts` hook.
     * @attaches-to `admin_enqueue_scripts` hook.
     */
    public function adminBarScripts()
    {
        if (!$this->adminBarShowing()) {
            return; // Nothing to do.
        }
        $deps = ['jquery', 'admin-bar']; // Plugin dependencies.

        if ($this->adminBarShowing('stats')) {
            $deps[] = 'chartjs'; // Add ChartJS dependency.
            wp_enqueue_script('chartjs', set_url_scheme('//cdnjs.cloudflare.com/ajax/libs/Chart.js/1.0.2/Chart.min.js'), [], null, true);
        }
        wp_enqueue_script(GLOBAL_NS.'-admin-bar', $this->url('/src/client-s/js/admin-bar.min.js'), $deps, VERSION, true);
    }
}
/*[/pro]*/
