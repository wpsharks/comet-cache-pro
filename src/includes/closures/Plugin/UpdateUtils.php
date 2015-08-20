<?php
/*[pro strip-from="lite"]*/
namespace WebSharks\ZenCache\Pro;

/*
 * Checks for a new pro release once every hour.
 *
 * @since 150422 Rewrite.
 *
 * @attaches-to `admin_init` hook.
 *
 * @see pre_site_transient_update_plugins()
 */
$self->checkLatestProVersion = function () use ($self) {
    if (!$self->options['pro_update_check']) {
        return; // Nothing to do.
    }
    if (!current_user_can($self->update_cap)) {
        return; // Nothing to do.
    }
    if (is_multisite() && !current_user_can($self->network_cap)) {
        return; // Nothing to do.
    }
    if ($self->options['last_pro_update_check'] >= strtotime('-1 hour')) {
        return; // No reason to keep checking on this.
    }
    $self->updateOptions(array('last_pro_update_check' => time()));

    $product_api_url        = 'https://'.urlencode(DOMAIN).'/';
    $product_api_input_vars = array('product_api' => array('action' => 'latest_pro_version'));

    $product_api_response = wp_remote_post($product_api_url, array('body' => $product_api_input_vars));
    $product_api_response = json_decode(wp_remote_retrieve_body($product_api_response));

    if (is_object($product_api_response) && !empty($product_api_response->pro_version)) {
        $self->updateOptions(array('latest_pro_version' => $product_api_response->pro_version));
    }
    if ($self->options['latest_pro_version'] && version_compare(VERSION, $self->options['latest_pro_version'], '<')) {
        $self->dismissMainNotice('new-pro-version-available'); // Dismiss any existing notices like this.
        $pro_updater_page = add_query_arg(urlencode_deep(array('page' => GLOBAL_NS.'-pro-updater')), network_admin_url('/admin.php'));
        $self->enqueueMainNotice(sprintf(__('<strong>%1$s Pro:</strong> a new version is now available. Please <a href="%2$s">upgrade to v%3$s</a>.', SLUG_TD), esc_html(NAME), esc_attr($pro_updater_page), esc_html($self->options['latest_pro_version'])), array('persistent_key' => 'new-pro-version-available'));
    }
};

/*
 * Modifies transient data associated with this plugin.
 *
 * @since 150422 Rewrite.
 *
 * @attaches-to `pre_site_transient_update_plugins` filter.
 *
 * @param object $transient Transient data provided by the WP filter.
 *
 * @return object Transient object; possibly altered by this routine.
 */
$self->preSiteTransientUpdatePlugins = function ($transient) use ($self) {
    if (!current_user_can($self->update_cap)) {
        return $transient; // Nothing to do here.
    }
    if (is_multisite() && !current_user_can($self->network_cap)) {
        return; // Nothing to do.
    }
    if (!is_admin() || $GLOBALS['pagenow'] !== 'update.php') {
        return $transient; // Nothing to do here.
    }
    $_r = $self->trimDeep(stripslashes_deep($_REQUEST));

    if (empty($_r['action']) || $_r['action'] !== 'upgrade-plugin') {
        return $transient; // Nothing to do here.
    }
    if (empty($_r['_wpnonce']) || !wp_verify_nonce((string) $_r['_wpnonce'], 'upgrade-plugin_'.plugin_basename(PLUGIN_FILE))) {
        return $transient; // Nothing to do here.
    }
    if (empty($_r[GLOBAL_NS.'_update_pro_version']) || empty($_r[GLOBAL_NS.'_update_pro_zip'])) {
        return $transient; // Nothing to do here.
    }
    $update_pro_version = (string) $_r[GLOBAL_NS.'_update_pro_version'];
    $update_pro_zip     = base64_decode((string) $_r[GLOBAL_NS.'_update_pro_zip'], true);
     // @TODO Encrypt/decrypt to avoid mod_security issues. Base64 is not enough.

    if (!is_object($transient)) {
        $transient = new \stdClass();
    }
    $transient->last_checked                           = time();
    $transient->checked[plugin_basename(PLUGIN_FILE)]  = VERSION;
    $transient->response[plugin_basename(PLUGIN_FILE)] = (object) array(
        'id'          => 0,
        'slug'        => basename(PLUGIN_FILE, '.php'),
        'url'         => add_query_arg(urlencode_deep(array('page' => GLOBAL_NS.'-pro-updater')), self_admin_url('/admin.php')),
        'new_version' => $update_pro_version, 'package' => $update_pro_zip,
    );
    return $transient; // Nodified now.
};

/*
 * Appends hidden inputs for pro updater when FTP credentials are requested by WP.
 *
 * @since 150422 Rewrite.
 *
 * @attaches-to `fs_ftp_connection_types` filter.
 *
 * @param array $types Types of connections.
 *
 * @return array $types Types of connections.
 */
$self->fsFtpConnectionTypes = function ($types) use ($self) {
    if (!current_user_can($self->update_cap)) {
        return $transient; // Nothing to do here.
    }
    if (is_multisite() && !current_user_can($self->network_cap)) {
        return; // Nothing to do.
    }
    if (!is_admin() || $GLOBALS['pagenow'] !== 'update.php') {
        return $types; // Nothing to do here.
    }
    $_r = $self->trimDeep(stripslashes_deep($_REQUEST));

    if (empty($_r['action']) || $_r['action'] !== 'upgrade-plugin') {
        return $types; // Nothing to do here.
    }
    if (empty($_r[GLOBAL_NS.'_update_pro_version']) || empty($_r[GLOBAL_NS.'_update_pro_zip'])) {
        return $types; // Nothing to do here.
    }
    $update_pro_version = (string) $_r[GLOBAL_NS.'_update_pro_version'];
    $update_pro_zip     = (string) $_r[GLOBAL_NS.'_update_pro_zip']; // Encrypted!

    echo '<script type="text/javascript">';
    echo '   (function($){ $(document).ready(function(){';
    echo '      var $form = $(\'input#hostname\').closest(\'form\');';
    echo '      $form.append(\'<input type="hidden" name="'.esc_attr(GLOBAL_NS.'_update_pro_version').'" value="'.esc_attr($update_pro_version).'" />\');';
    echo '      $form.append(\'<input type="hidden" name="'.esc_attr(GLOBAL_NS.'_update_pro_zip').'" value="'.esc_attr($update_pro_zip).'" />\');';
    echo '   }); })(jQuery);';
    echo '</script>';

    return $types; // Filter through.
};
/*[/pro]*/
