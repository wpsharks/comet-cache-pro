<?php
namespace WebSharks\ZenCache\Pro;

/**
 * Menu Page.
 *
 * @since 150422 Rewrite
 */
class MenuPage extends AbsBase
{
    /**
     * Constructor.
     *
     * @since 150422 Rewrite
     *
     * @param MenuPage $menu_page Menu page.
     */
    public function __construct(MenuPage $menu_page = null)
    {
        parent::__construct();

        if ($menu_page) {
            new $menu_page();
        }
    }
}
