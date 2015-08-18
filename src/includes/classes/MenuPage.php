<?php
namespace WebSharks\ZenCache\Pro;

/**
 * Menu Page.
 *
 * @since 150422 Rewrite.
 */
class MenuPage extends AbsBase
{
    /**
     * Constructor.
     *
     * @since 150422 Rewrite.
     *
     * @param string $menu_page Menu page.
     */
    public function __construct($menu_page = '')
    {
        parent::__construct();

        if ($menu_page) {
            switch ($menu_page) {
                case 'options':
                    new MenuPageOptions();
                    break;

                /*[pro strip-from="lite"]*/
                case 'dir-stats':
                    new MenuPageDirStats();
                    break;
                /*[/pro]*/

                /*[pro strip-from="lite"]*/
                case 'pro-updater':
                    new MenuPageProUpdater();
                    break;
                /*[/pro]*/
            }
        }
    }
}
