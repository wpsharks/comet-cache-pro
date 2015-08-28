<?php
/*[pro strip-from="lite"]*/
namespace WebSharks\ZenCache\Pro;

/**
 * Pro Updater Page.
 *
 * @since 150422 Rewrite.
 */
class MenuPageProUpdater extends MenuPage
{
    /**
     * Constructor.
     *
     * @since 150422 Rewrite.
     */
    public function __construct()
    {
        parent::__construct(); // Parent constructor.

        echo '<form id="plugin-menu-page" class="plugin-menu-page" method="post" enctype="multipart/form-data"'.
             ' action="'.esc_attr(add_query_arg(urlencode_deep(array('page' => GLOBAL_NS.'-pro-updater', '_wpnonce' => wp_create_nonce())), self_admin_url('/admin.php'))).'">'."\n";

        /* ----------------------------------------------------------------------------------------- */

        echo '<div class="plugin-menu-page-heading">'."\n";

        echo '   <button type="submit" style="float:right;">'.sprintf(__('Update %1$s Now', SLUG_TD), esc_html(NAME)).' <i class="si si-magic"></i></button>'."\n";

        echo '   <div class="plugin-menu-page-panel-togglers" title="'.esc_attr(__('All Panels', SLUG_TD)).'">'."\n";
        echo '      <button type="button" class="plugin-menu-page-panels-open"><i class="si si-chevron-down"></i></button>'."\n";
        echo '      <button type="button" class="plugin-menu-page-panels-close"><i class="si si-chevron-up"></i></button>'."\n";
        echo '   </div>'."\n";

        echo '   <div class="plugin-menu-page-upsells">'."\n";
        if (current_user_can($this->plugin->cap)) {
            echo '  <a href="'.esc_attr(add_query_arg(urlencode_deep(array('page' => GLOBAL_NS)), self_admin_url('/admin.php'))).'"><i class="si si-cogs"></i> '.__('Options', SLUG_TD).'</a>'."\n";
        }
        echo '      <a href="'.esc_attr('http://zencache.com/r/zencache-subscribe/').'" target="_blank"><i class="si si-envelope"></i> '.__('Newsletter', SLUG_TD).'</a>'."\n";
        echo '      <a href="'.esc_attr('http://zencache.com/r/zencache-beta-testers-list/').'" target="_blank"><i class="si si-envelope"></i> '.__('Beta Testers', SLUG_TD).'</a>'."\n";
        echo '   </div>'."\n";
	
		echo '<div class="plugin-menu-page-version">'.
			 '  <small><span>'.sprintf(__('%1$s&trade; v', SLUG_TD), esc_html(NAME)).'</span></small>'.
			 ''.esc_html(VERSION).
			 '   <a href="'.esc_attr('https://zencache.com/changelog/').'" target="_blank"> '.__('(changelog)', SLUG_TD).'</a>.'.
			 '	<a href="'.esc_attr(add_query_arg(urlencode_deep(array('page' => GLOBAL_NS.'-pro-updater')), self_admin_url('/admin.php'))).'" target="_blank"> '.__('Latest Pro v', SLUG_TD).'</a>'.
			 ''	 .$this->plugin->options['latest_pro_version'].
			'</div>'."\n";

			
        echo '   <img src="'.$this->plugin->url('/src/client-s/images/pro-updater.png').'" alt="'.esc_attr(__('Pro Plugin Updater', SLUG_TD)).'" />'."\n";

        echo '</div>'."\n";

        /* ----------------------------------------------------------------------------------------- */

        echo '<hr />'."\n";

        /* ----------------------------------------------------------------------------------------- */

        if (!empty($_REQUEST[GLOBAL_NS.'_error'])) {
            echo '<div class="plugin-menu-page-error error">'."\n";
            echo '   <i class="si si-thumbs-down"></i> '.esc_html(stripslashes((string) $_REQUEST[GLOBAL_NS.'_error']))."\n";
            echo '</div>'."\n";
        }
        /* ----------------------------------------------------------------------------------------- */

        echo '<div class="plugin-menu-page-body">'."\n";

        /* ----------------------------------------------------------------------------------------- */

        echo '<div class="plugin-menu-page-panel">'."\n";

        echo '   <a href="#" class="plugin-menu-page-panel-heading open">'."\n";
        echo '      <i class="si si-sign-in"></i> '.__('Update Credentials', SLUG_TD)."\n";
        echo '   </a>'."\n";

        echo '   <div class="plugin-menu-page-panel-body clearfix open">'."\n";
        echo '      <i class="si si-user si-4x" style="float:right; margin: 0 0 0 25px;"></i>'."\n";
        echo '      <h3>'.sprintf(__('%1$s™ Authentication', SLUG_TD), esc_html(NAME)).'</h3>'."\n";
        echo '      <p>'.sprintf(__('From this page you can update to the latest version of %1$s Pro for WordPress. %1$s Pro is a premium product available for purchase @ <a href="http://zencache.com/prices/" target="_blank">zencache.com</a>. In order to connect with our update servers, we ask that you supply your account login details for <a href="http://zencache.com/" target="_blank">zencache.com</a>. If you prefer not to provide your password, you can use your License Key in place of your password. Your License Key is located under "My Account" when you log in @ <a href="http://zencache.com/" target="_blank">zencache.com</a>. This will authenticate your copy of %1$s Pro; providing you with access to the latest version. You only need to enter these credentials once. %1$s Pro will save them in your WordPress database; making future upgrades even easier. <i class="si si-smile-o"></i>', SLUG_TD), esc_html(NAME)).'</p>'."\n";
        echo '      <hr />'."\n";
        echo '      <h3>'.sprintf(__('Customer Username', SLUG_TD), esc_html(NAME)).'</h3>'."\n";
        echo '      <p><input type="text" name="'.esc_attr(GLOBAL_NS).'[proUpdate][username]" value="'.esc_attr($this->plugin->options['pro_update_username']).'" autocomplete="off" /></p>'."\n";
        echo '      <h3>'.sprintf(__('Customer Password or Product License Key', SLUG_TD), esc_html(NAME)).'</h3>'."\n";
        echo '      <p><input type="password" name="'.esc_attr(GLOBAL_NS).'[proUpdate][password]" value="'.esc_attr($this->plugin->options['pro_update_password']).'" autocomplete="off" /></p>'."\n";
        echo '   </div>'."\n";

        echo '</div>'."\n";

        /* ----------------------------------------------------------------------------------------- */

        echo '<div class="plugin-menu-page-panel">'."\n";

        echo '   <a href="#" class="plugin-menu-page-panel-heading open">'."\n";
        echo '      <i class="si si-bullhorn"></i> '.__('Update Notifier', SLUG_TD)."\n";
        echo '   </a>'."\n";

        echo '   <div class="plugin-menu-page-panel-body clearfix open">'."\n";
        echo '      <i class="si si-rss si-4x" style="float:right; margin: 0 0 0 25px;"></i>'."\n";
        echo '      <h3>'.sprintf(__('%1$s™ Update Notifier', SLUG_TD), esc_html(NAME)).'</h3>'."\n";
        echo '      <p>'.sprintf(__('When a new version of %1$s Pro becomes available, %1$s Pro can display a notification in your WordPress Dashboard prompting you to return to this page and perform an upgrade. Would you like this functionality enabled or disabled?', SLUG_TD), esc_html(NAME)).'</p>'."\n";
        echo '      <hr />'."\n";
        echo '      <p><select name="'.esc_attr(GLOBAL_NS).'[proUpdate][check]" autocomplete="off">'."\n";
        echo '            <option value="1"'.selected($this->plugin->options['pro_update_check'], '1', false).'>'.sprintf(__('Yes, display a notification in my WordPress Dashboard when a new version is available.', SLUG_TD), esc_html(NAME)).'</option>'."\n";
        echo '            <option value="0"'.selected($this->plugin->options['pro_update_check'], '0', false).'>'.sprintf(__('No, do not display any %1$s update notifications in my WordPress Dashboard.', SLUG_TD), esc_html(NAME)).'</option>'."\n";
        echo '         </select></p>'."\n";
        echo '   </div>'."\n";

        echo '</div>'."\n";

        /* ----------------------------------------------------------------------------------------- */

        echo '<div class="plugin-menu-page-save">'."\n";
        echo '   <button type="submit">'.sprintf(__('Update %1$s Now', SLUG_TD), esc_html(NAME)).' <i class="si si-magic"></i></button>'."\n";
        echo '</div>'."\n";

        /* ----------------------------------------------------------------------------------------- */

        echo '</div>'."\n";
        echo '</form>';
    }
}
/*[/pro]*/
