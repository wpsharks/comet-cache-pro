<?php
namespace WebSharks\ZenCache\Pro;

/**
 * Utilities.
 *
 * @since 150422 Rewrite.
 */
class MenuPageUtils extends AbsBase
{
    /**
     * Adds CSS for administrative menu pages.
     *
     * @since 140422 First documented version.
     *
     * @attaches-to `admin_enqueue_scripts` hook.
     */
    public function enqueue_admin_styles()
    {
        if (empty($_GET['page']) || strpos($_GET['page'], __NAMESPACE__) !== 0) {
            return;
        } // Nothing to do; NOT a plugin page in the administrative area.

        $deps = array(); // Plugin dependencies.

        wp_enqueue_style(__NAMESPACE__, $this->url('/client-s/css/menu-pages.min.css'), $deps, $this->version, 'all');
    }

    /**
     * Adds JS for administrative menu pages.
     *
     * @since 140422 First documented version.
     *
     * @attaches-to `admin_enqueue_scripts` hook.
     */
    public function enqueue_admin_scripts()
    {
        if (empty($_GET['page']) || strpos($_GET['page'], __NAMESPACE__) !== 0) {
            return;
        } // Nothing to do; NOT a plugin page in the administrative area.

        $deps = array('jquery'); // Plugin dependencies.

        wp_enqueue_script(__NAMESPACE__, $this->url('/client-s/js/menu-pages.min.js'), $deps, $this->version, true);
    }

    /**
     * Creates network admin menu pages.
     *
     * @since 140422 First documented version.
     *
     * @attaches-to `network_admin_menu` hook.
     */
    public function add_network_menu_pages()
    {
        $icon = file_get_contents(dirname(__FILE__).'/client-s/images/inline-icon.svg');
        $icon = 'data:image/svg+xml;base64,'.base64_encode($this->color_svg_menu_icon($icon));

        add_menu_page($this->name, $this->name, $this->network_cap, __NAMESPACE__, array($this, 'menu_page_options'), $icon);

        add_submenu_page(__NAMESPACE__, __('Plugin Options', $this->text_domain), __('Plugin Options', $this->text_domain),
                         $this->network_cap, __NAMESPACE__, array($this, 'menu_page_options'));

        if (current_user_can($this->network_cap)) {
            // Multi-layer security here.
            add_submenu_page(__NAMESPACE__, __('Pro Plugin Updater', $this->text_domain), __('Plugin Updater', $this->text_domain),
                             $this->update_cap, __NAMESPACE__.'-pro-updater', array($this, 'menu_page_pro_updater'));
        }
    }

    /**
     * Creates admin menu pages.
     *
     * @since 140422 First documented version.
     *
     * @attaches-to `admin_menu` hook.
     */
    public function add_menu_pages()
    {
        if (is_multisite()) {
            return;
        } // Multisite networks MUST use network admin area.

        $icon = file_get_contents(dirname(__FILE__).'/client-s/images/inline-icon.svg');
        $icon = 'data:image/svg+xml;base64,'.base64_encode($this->color_svg_menu_icon($icon));

        add_menu_page($this->name, $this->name, $this->cap, __NAMESPACE__, array($this, 'menu_page_options'), $icon);

        add_submenu_page(__NAMESPACE__, __('Plugin Options', $this->text_domain), __('Plugin Options', $this->text_domain),
                         $this->cap, __NAMESPACE__, array($this, 'menu_page_options'));

        add_submenu_page(__NAMESPACE__, __('Pro Plugin Updater', $this->text_domain), __('Plugin Updater', $this->text_domain),
                         $this->update_cap, __NAMESPACE__.'-pro-updater', array($this, 'menu_page_pro_updater'));
    }

    /**
     * Adds link(s) to ZenCache row on the WP plugins page.
     *
     * @since 140422 First documented version.
     *
     * @attaches-to `plugin_action_links_'.plugin_basename($this->file)` filter.
     *
     * @param array $links An array of the existing links provided by WordPress.
     *
     * @return array Revised array of links.
     */
    public function add_settings_link($links)
    {
        $links[] = '<a href="'.esc_attr(add_query_arg(urlencode_deep(array('page' => __NAMESPACE__)), self_admin_url('/admin.php'))).'">'.__('Settings', $this->text_domain).'</a>';

        return $this->apply_wp_filters(__METHOD__, $links, get_defined_vars());
    }

    /**
     * Fills menu page inline SVG icon color.
     *
     * @since 150409 Fixing bug in SVG icons.
     *
     * @param string $svg Inline SVG icon markup.
     *
     * @return string Inline SVG icon markup.
     */
    public function color_svg_menu_icon($svg)
    {
        if (!($color = get_user_option('admin_color'))) {
            $color = 'fresh';
        } // Default color scheme.

        if (empty($this->wp_admin_icon_colors[$color])) {
            return $svg;
        } // Not possible.

        $icon_colors         = $this->wp_admin_icon_colors[$color];
        $use_icon_fill_color = $icon_colors['base']; // Default base.

        $current_pagenow = !empty($GLOBALS['pagenow']) ? $GLOBALS['pagenow'] : '';
        $current_page    = !empty($_REQUEST['page']) ? $_REQUEST['page'] : '';

        if (strpos($current_pagenow, __NAMESPACE__) === 0 || strpos($current_page, __NAMESPACE__) === 0) {
            $use_icon_fill_color = $icon_colors['current'];
        }

        return str_replace(' fill="currentColor"', ' fill="'.esc_attr($use_icon_fill_color).'"', $svg);
    }

    /**
     * WordPress admin icon color schemes.
     *
     * @since 150409 Fixing bug in SVG icons.
     *
     * @type array WP admin icon colors.
     *
     * @note These must be hard-coded, because they don't become available
     *    in core until `admin_init`; i.e., too late for `admin_menu`.
     */
    public $wp_admin_icon_colors = array(
        'fresh'     => array('base' => '#999999', 'focus' => '#2EA2CC', 'current' => '#FFFFFF'),
        'light'     => array('base' => '#999999', 'focus' => '#CCCCCC', 'current' => '#CCCCCC'),
        'blue'      => array('base' => '#E5F8FF', 'focus' => '#FFFFFF', 'current' => '#FFFFFF'),
        'midnight'  => array('base' => '#F1F2F3', 'focus' => '#FFFFFF', 'current' => '#FFFFFF'),
        'sunrise'   => array('base' => '#F3F1F1', 'focus' => '#FFFFFF', 'current' => '#FFFFFF'),
        'ectoplasm' => array('base' => '#ECE6F6', 'focus' => '#FFFFFF', 'current' => '#FFFFFF'),
        'ocean'     => array('base' => '#F2FCFF', 'focus' => '#FFFFFF', 'current' => '#FFFFFF'),
        'coffee'    => array('base' => '#F3F2F1', 'focus' => '#FFFFFF', 'current' => '#FFFFFF'),
    );

    /**
     * Loads the admin menu page options.
     *
     * @since 140422 First documented version.
     * @see add_network_menu_pages()
     * @see add_menu_pages()
     */
    public function menu_page_options()
    {
        require_once dirname(__FILE__).'/includes/menu-pages.php';
        $menu_pages = new menu_pages();
        $menu_pages->options();
    }

    /**
     * Loads admin menu page for pro updater.
     *
     * @since 140422 First documented version.
     * @see add_network_menu_pages()
     * @see add_menu_pages()
     */
    public function menu_page_pro_updater()
    {
        require_once dirname(__FILE__).'/includes/menu-pages.php';
        $menu_pages = new menu_pages();
        $menu_pages->pro_updater();
    }
}
