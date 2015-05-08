<?php
namespace WebSharks\ZenCache\Pro;

/**
 * Utilities.
 *
 * @since 150422 Rewrite.
 */
class AdminBarUtils extends AbsBase
{
    /**
     * Filter WordPress admin bar.
     *
     * @since 140422 First documented version.
     *
     * @attaches-to `admin_bar_menu` hook.
     *
     * @param $wp_admin_bar \WP_Admin_Bar
     */
    public function admin_bar_menu(&$wp_admin_bar)
    {
        if (!$this->options['enable']) {
            return;
        } // Nothing to do.

        if (!$this->options['admin_bar_enable']) {
            return;
        } // Nothing to do.

        if (!current_user_can($this->cap) || !is_admin_bar_showing()) {
            return;
        } // Nothing to do.

        if (is_multisite() && current_user_can($this->network_cap)) {
            // Allow network administrators to wipe the entire cache on a multisite network.
            $wp_admin_bar->add_node(array('parent' => 'top-secondary', 'id' => __NAMESPACE__.'-wipe', 'title' => __('Wipe', $this->text_domain), 'href' => '#',
                                          'meta'   => array('title' => __('Wipe Cache (Start Fresh); clears the cache for all sites in this network at once!', $this->text_domain),
                                                            'class' => __NAMESPACE__, 'tabindex' => -1, ), ));
        }

        $wp_admin_bar->add_node(array('parent' => 'top-secondary', 'id' => __NAMESPACE__.'-clear', 'title' => __('Clear Cache', $this->text_domain), 'href' => '#',
                                      'meta'   => array('title' => ((is_multisite() && current_user_can($this->network_cap))
                                          ? __('Clear Cache (Start Fresh); affects the current site only.', $this->text_domain)
                                          : __('Clear Cache (Start Fresh)', $this->text_domain)),
                                                        'class' => __NAMESPACE__, 'tabindex' => -1, ), ));
    }

    /**
     * Injects `<meta>` tag w/ JSON-encoded data for WordPress admin bar.
     *
     * @since 140422 First documented version.
     *
     * @attaches-to `admin_head` hook.
     */
    public function admin_bar_meta_tags()
    {
        if (!$this->options['enable']) {
            return; // Nothing to do.
        }
        if (!$this->options['admin_bar_enable']) {
            return; // Nothing to do.
        }
        if (!current_user_can($this->cap) || !is_admin_bar_showing()) {
            return; // Nothing to do.
        }
        $vars = array(// Dynamic JS vars.
                       'ajaxURL'  => site_url('/wp-load.php', is_ssl() ? 'https' : 'http'),
                       '_wpnonce' => wp_create_nonce(), );

        $vars = $this->apply_wp_filters(__METHOD__, $vars, get_defined_vars());

        $tags = '<meta property="'.esc_attr(__NAMESPACE__).':vars" content="data-json"'.
                ' data-json="'.esc_attr(json_encode($vars)).'" id="'.esc_attr(__NAMESPACE__).'-vars" />'."\n";

        echo $this->apply_wp_filters(__METHOD__, $tags, get_defined_vars());
    }

    /**
     * Adds CSS for WordPress admin bar.
     *
     * @since 140422 First documented version.
     *
     * @attaches-to `wp_enqueue_scripts` hook.
     * @attaches-to `admin_enqueue_scripts` hook.
     */
    public function admin_bar_styles()
    {
        if (!$this->options['enable']) {
            return; // Nothing to do.
        }
        if (!$this->options['admin_bar_enable']) {
            return; // Nothing to do.
        }
        if (!current_user_can($this->cap) || !is_admin_bar_showing()) {
            return; // Nothing to do.
        }
        $deps = array(); // Plugin dependencies.

        wp_enqueue_style(__NAMESPACE__.'-admin-bar', $this->url('/client-s/css/admin-bar.min.css'), $deps, $this->version, 'all');
    }

    /**
     * Adds JS for WordPress admin bar.
     *
     * @since 140422 First documented version.
     *
     * @attaches-to `wp_enqueue_scripts` hook.
     * @attaches-to `admin_enqueue_scripts` hook.
     */
    public function admin_bar_scripts()
    {
        if (!$this->options['enable']) {
            return; // Nothing to do.
        }
        if (!$this->options['admin_bar_enable']) {
            return; // Nothing to do.
        }
        if (!current_user_can($this->cap) || !is_admin_bar_showing()) {
            return; // Nothing to do.
        }
        $deps = array('jquery'); // Plugin dependencies.

        wp_enqueue_script(__NAMESPACE__.'-admin-bar', $this->url('/client-s/js/admin-bar.min.js'), $deps, $this->version, true);
    }
}
