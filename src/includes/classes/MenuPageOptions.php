<?php
namespace WebSharks\CometCache\Pro\Classes;

/**
 * Options Page.
 *
 * @since 150422 Rewrite.
 */
class MenuPageOptions extends AbsBase
{
    /**
     * Constructor.
     *
     * @since 150422 Rewrite.
     * @since 17xxxx Refactor (breaking apart).
     */
    public function __construct()
    {
        parent::__construct(); // Parent constructor.

        echo '<form id="plugin-menu-page" class="plugin-menu-page" method="post" enctype="multipart/form-data" autocomplete="off"'.
             ' action="'.esc_attr(add_query_arg(urlencode_deep(['page' => GLOBAL_NS, '_wpnonce' => wp_create_nonce()]), self_admin_url('/admin.php'))).'">'."\n";

        new MenuPage\Options\Heading();

        /* ----------------------------------------------------------------------------------------- */

        echo '<div class="plugin-menu-page-body">'."\n";

        /* ----------------------------------------------------------------------------------------- */

        echo '<h2 class="plugin-menu-page-section-heading">'.
             '  '.__('Basic Configuration (Required)', SLUG_TD).
             '  <small><span>'.sprintf(__('Review these basic options and %1$s&trade; will be ready-to-go!', SLUG_TD), esc_html(NAME)).'</span></small>'.
             '</h2>';

        new MenuPage\Options\Enable();
        new MenuPage\Options\ProUpdater();
        new MenuPage\Options\Safeguards();

        /* ----------------------------------------------------------------------------------------- */

        echo '<h2 class="plugin-menu-page-section-heading">'.
             '  '.__('Advanced Configuration (All Optional)', SLUG_TD).
             '  <small>'.__('Recommended for advanced site owners only; already pre-configured for most WP installs.', SLUG_TD).'</small>'.
             '</h2>';

        new MenuPage\Options\ManualClearing();
        new MenuPage\Options\AutomaticClearing();

        new MenuPage\Options\Statistics();
        new MenuPage\Options\Directory();
        new MenuPage\Options\Expiration();

        new MenuPage\Options\ClientSide();
        new MenuPage\Options\UserRequests();
        new MenuPage\Options\GetRequests();
        new MenuPage\Options\Nf404Requests();
        new MenuPage\Options\FeedRequests();

        new MenuPage\Options\HostExclusions();
        new MenuPage\Options\UriExclusions();
        new MenuPage\Options\RefererExclusions();
        new MenuPage\Options\UserAgentExclusions();

        new MenuPage\Options\AutoCacheEngine();
        new MenuPage\Options\HtmlCompressor();
        new MenuPage\Options\StaticCdnFilters();
        new MenuPage\Options\ApacheOptimizations();

        new MenuPage\Options\MobileMode();
        new MenuPage\Options\VersionSalt();

        new MenuPage\Options\Developers();
        new MenuPage\Options\ImportExport();

        /* ----------------------------------------------------------------------------------------- */

        echo '<div class="plugin-menu-page-save">'."\n";
        echo '   <button type="submit">'.__('Save All Changes', SLUG_TD).' <i class="si si-save"></i></button>'."\n";
        echo '</div>'."\n";

        /* ----------------------------------------------------------------------------------------- */

        echo '</div>'."\n";
        echo '</form>';
    }
}
