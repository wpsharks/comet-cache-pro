<?php
/*[pro strip-from="lite"]*/
namespace WebSharks\ZenCache\Pro;

/*
 * Showing admin bar.
 *
 * @since 15xxxx Improving admin bar.
 *
 * @return boolean True if showing.
 */
$self->adminBarShowing = function () use ($self) {
    if (!is_null($showing = &$self->cacheKey('adminBarShowing'))) {
        return $showing; // Already cached this.
    }
    $showing = $self->options['enable']
        && ($self->options['cache_clear_admin_bar_enable']
            || ($self->options['stats_enable'] && $self->options['stats_admin_bar_enable']))
        && current_user_can($self->cap) && is_admin_bar_showing();

    return $showing; // True or false.
};

/*
 * Filter WordPress admin bar.
 *
 * @since 150422 Rewrite.
 *
 * @attaches-to `admin_bar_menu` hook.
 *
 * @param $wp_admin_bar \WP_Admin_Bar
 */
$self->adminBarMenu = function (\WP_Admin_Bar &$wp_admin_bar) use ($self) {
    if (!$self->adminBarShowing()) {
        return; // Nothing to do.
    }
    if ($self->options['cache_clear_admin_bar_enable']) {
        if (is_multisite() && current_user_can($self->network_cap)) {
            $wp_admin_bar->add_menu(
                array(
                    'parent' => 'top-secondary',
                    'id'     => GLOBAL_NS.'-wipe',

                    'title' => __('Wipe', SLUG_TD),
                    'href'  => '#',

                    'meta' => array(
                            'title'    => __('Wipe Cache (Start Fresh). Clears the cache for all sites in this network at once!', SLUG_TD),
                            'class'    => '-wipe',
                            'tabindex' => -1,
                    ),
                )
            );
        }
        if (!is_multisite() || !is_network_admin()) {
            $wp_admin_bar->add_menu(
                array(
                    'parent' => 'top-secondary',
                    'id'     => GLOBAL_NS.'-clear',

                    'title' => __('Clear Cache', SLUG_TD),
                    'href'  => '#',
                    'meta'  => array(
                            'title' => is_multisite() && current_user_can($self->network_cap)
                                ? __('Clear Cache (Start Fresh). Affects the current site only.', SLUG_TD)
                                : __('Clear Cache (Start Fresh)', SLUG_TD),
                            'class'    => '-clear',
                            'tabindex' => -1,
                    ),
                )
            );
        }
    }
    if ($self->options['stats_enable'] && $self->options['stats_admin_bar_enable']) {
        $wp_admin_bar->add_menu(
            array(
                'parent' => 'top-secondary',
                'id'     => GLOBAL_NS.'-stats',

                'title' => __('Cache Stats', SLUG_TD),
                'href'  => '#',

                'meta' => array(
                        'title'    => __('Cache statistics.', SLUG_TD),
                        'class'    => '-stats',
                        'tabindex' => -1,
                ),
            )
        );
        $wp_admin_bar->add_group(
            array(
                'parent' => GLOBAL_NS.'-stats',
                'id'     => GLOBAL_NS.'-stats-wrapper',

                'meta' => array(
                    'class' => '-wrapper',
                ),
            )
        );
        $wp_admin_bar->add_menu(
            array(
                'parent' => GLOBAL_NS.'-stats-wrapper',
                'id'     => GLOBAL_NS.'-stats-container',

                'title' => '<div class="-refreshing"></div>'.

                            '<canvas class="-chart-a"></canvas>'.
                            // '<canvas class="-chart-b"></canvas>'.

                            '<div class="-totals">'.
                            '  <div class="-heading">'.__('Current Cache Totals', SLUG_TD).'</div>'.
                            '  <div class="-files"><span class="-value">&nbsp;</span></div>'.
                            '  <div class="-size"><span class="-value">&nbsp;</span></div>'.
                            '  <div class="-dir">'.esc_html(basename(WP_CONTENT_DIR).'/'.$self->options['base_dir'].'/*').'</div>'.
                            '</div>'.

                            '<div class="-disk">'.
                            '  <div class="-heading">'.__('Current Disk Health', SLUG_TD).'</div>'.
                            '  <div class="-size"><span class="-value">&nbsp;</span> '.__('total capacity', SLUG_TD).'</div>'.
                            '  <div class="-free"><span class="-value">&nbsp;</span> '.__('available', SLUG_TD).'</div>'.
                            '</div>'.

                            '<div class="-more-info">'.
                            '  <a href="'.esc_attr(add_query_arg(urlencode_deep(array('page' => GLOBAL_NS.'-stats')), network_admin_url('/admin.php'))).'">'.__('More Info', SLUG_TD).'</a>'.
                            '</div>'.

                            '<div class="-spacer"></div>',

                'meta' => array(
                        'class'    => '-container',
                        'tabindex' => -1,
                ),
            )
        );
    }
};

/*
 * Injects `<meta>` tag w/ JSON-encoded data.
 *
 * @since 150422 Rewrite.
 *
 * @attaches-to `admin_head` hook.
 */
$self->adminBarMetaTags = function () use ($self) {
    if (!$self->adminBarShowing()) {
        return; // Nothing to do.
    }
    $vars = array(
        '_wpnonce'                 => wp_create_nonce(),
        'isMultisite'              => is_multisite(), // Network?
        'currentUserHasNetworkCap' => current_user_can($self->network_cap),
        'htmlCompressorEnabled'    => (boolean) $self->options['htmlc_enable'],
        'ajaxURL'                  => site_url('/wp-load.php', is_ssl() ? 'https' : 'http'),
        'i18n'                     => array(
            'name'           => NAME,
            'perSymbol'      => __('%', SLUG_TD),
            'file'           => __('file', SLUG_TD),
            'files'          => __('files', SLUG_TD),
            'pageCache'      => __('Page Cache', SLUG_TD),
            'htmlCompressor' => __('HTML Compressor', SLUG_TD),
            'currentTotal'   => __('Current Total', SLUG_TD),
            'currentSite'    => __('Current Site', SLUG_TD),
            'xDayHigh'       => __('%s Day High', SLUG_TD),
        ),
    );
    echo '<meta property="'.esc_attr(GLOBAL_NS).':admin-bar-vars" content="data-json"'.
         ' data-json="'.esc_attr(json_encode($vars)).'" id="'.esc_attr(GLOBAL_NS).'-admin-bar-vars" />'."\n";
};

/*
 * Adds CSS for WordPress admin bar.
 *
 * @since 150422 Rewrite.
 *
 * @attaches-to `wp_enqueue_scripts` hook.
 * @attaches-to `admin_enqueue_scripts` hook.
 */
$self->adminBarStyles = function () use ($self) {
    if (!$self->adminBarShowing()) {
        return; // Nothing to do.
    }
    $deps = array(); // Plugin dependencies.

    wp_enqueue_style(GLOBAL_NS.'-admin-bar', $self->url('/src/client-s/css/admin-bar.min.css'), $deps, VERSION, 'all');
};

/*
 * Adds JS for WordPress admin bar.
 *
 * @since 150422 Rewrite.
 *
 * @attaches-to `wp_enqueue_scripts` hook.
 * @attaches-to `admin_enqueue_scripts` hook.
 */
$self->adminBarScripts = function () use ($self) {
    if (!$self->adminBarShowing()) {
        return; // Nothing to do.
    }
    $deps = array('jquery', 'admin-bar'); // Plugin dependencies.

    if ($self->options['stats_enable'] && $self->options['stats_admin_bar_enable']) {
        $deps[] = 'chartjs'; // Add ChartJS dependency.
        wp_enqueue_script('chartjs', set_url_scheme('//cdnjs.cloudflare.com/ajax/libs/Chart.js/1.0.2/Chart.min.js'), array(), null, true);
    }
    wp_enqueue_script(GLOBAL_NS.'-admin-bar', $self->url('/src/client-s/js/admin-bar.min.js'), $deps, VERSION, true);
};
/*[/pro]*/
