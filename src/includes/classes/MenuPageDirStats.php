<?php
/*[pro strip-from="lite"]*/
namespace WebSharks\ZenCache\Pro;

/**
 * Dir Stats Page.
 *
 * @since 15xxxx Directory stats.
 */
class MenuPageDirStats extends MenuPage
{
    /**
     * Constructor.
     *
     * @since 15xxxx Directory stats.
     */
    public function __construct()
    {
        parent::__construct(); // Parent constructor.

        echo '<div id="plugin-menu-page" class="plugin-menu-page">'."\n";

        /* ----------------------------------------------------------------------------------------- */

        echo '<div class="plugin-menu-page-heading">'."\n";

        echo '   <button type="button" class="plugin-menu-page-dir-stats-button" style="float:right;">'.
                    __('Refresh Stats/Charts', SLUG_TD).' <i class="si si-refresh"></i>'.
                 '</button>'."\n";

        echo '   <div class="plugin-menu-page-upsells">'."\n";
        if (current_user_can($this->plugin->cap)) {
            echo '  <a href="'.esc_attr(add_query_arg(urlencode_deep(array('page' => GLOBAL_NS)), self_admin_url('/admin.php'))).'"><i class="si si-cogs"></i> '.__('Options', SLUG_TD).'</a>'."\n";
        }
        echo '      <a href="'.esc_attr('http://zencache.com/r/zencache-subscribe/').'" target="_blank"><i class="si si-envelope"></i> '.__('Newsletter', SLUG_TD).'</a>'."\n";
        echo '      <a href="'.esc_attr('http://zencache.com/r/zencache-beta-testers-list/').'" target="_blank"><i class="si si-envelope"></i> '.__('Beta Testers', SLUG_TD).'</a>'."\n";
        echo '   </div>'."\n";

        echo '   <img src="'.$this->plugin->url('/src/client-s/images/dir-stats.png').'" alt="'.esc_attr(__('Cache Directory Stats', SLUG_TD)).'" />'."\n";

        echo '</div>'."\n";

        /* ----------------------------------------------------------------------------------------- */

        echo '<hr />'."\n";

        /* ----------------------------------------------------------------------------------------- */

        echo '<div class="plugin-menu-page-body">'."\n";

        /* ----------------------------------------------------------------------------------------- */

        echo '  <div class="plugin-menu-page-dir-stats">'."\n";
        echo '      <div class="-wrapper">'."\n";
        echo '          <div class="-container">'."\n";

        echo '              <div class="-refreshing"></div>'."\n";

        echo '              <div class="-totals">'."\n";
        echo '                  <div class="-heading">'.__('Current Cache Totals', SLUG_TD).'</div>'."\n";
        echo '                  <div class="-files"><span class="-value">&nbsp;</span></div>'."\n";
        echo '                  <div class="-size"><span class="-value">&nbsp;</span></div>'."\n";
        echo '                  <div class="-dir">'.esc_html(basename(WP_CONTENT_DIR).'/'.$this->plugin->options['base_dir'].'/*').'</div>'."\n";
        echo '              </div>'."\n";

        echo '              <div class="-disk">'."\n";
        echo '                  <div class="-heading">'.__('Current Disk Health', SLUG_TD).'</div>'."\n";
        echo '                  <div class="-size"><span class="-value">&nbsp;</span> '.__('total capacity', SLUG_TD).'</div>'."\n";
        echo '                  <div class="-free"><span class="-value">&nbsp;</span> '.__('available', SLUG_TD).'</div>'."\n";
        echo '              </div>'."\n";

        echo '              <div class="-chart-divider"></div>'."\n";

        echo '              <div class="-chart-a">'."\n";
        echo '                  <div class="-heading">'.__('Cache File Counts', SLUG_TD).'</div>'."\n";
        echo '                  <canvas class="-canvas"></canvas>'."\n";
        echo '              </div>'."\n";

        echo '              <div class="-chart-divider"></div>'."\n";

        echo '              <div class="-chart-b">'."\n";
        echo '                  <div class="-heading">'.__('Cache File Sizes', SLUG_TD).'</div>'."\n";
        echo '                  <canvas class="-canvas"></canvas>'."\n";
        echo '              </div>'."\n";

        echo '          </div>'."\n";
        echo '      </div>'."\n";
        echo '  </div>'."\n";

        /* ----------------------------------------------------------------------------------------- */

        echo '</div>'."\n";
        echo '</div>';
    }
}
/*[/pro]*/
