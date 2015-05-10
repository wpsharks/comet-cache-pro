<?php
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
        return; // Functionality is disabled here.
    }
    if (!current_user_can($self->update_cap)) {
        return; // Nothing to do.
    }
    if ($self->options['last_pro_update_check'] >= strtotime('-1 hour')) {
        return; // No reason to keep checking on this.
    }
    $self->options['last_pro_update_check'] = time();
    update_option(GLOBAL_NS.'_options', $self->options);
    if (is_multisite()) {
        update_site_option(GLOBAL_NS.'_options', $self->options);
    }
    $product_api_url        = 'https://'.urlencode(DOMAIN).'/';
    $product_api_input_vars = array('product_api' => array('action' => 'latest_pro_version'));

    $product_api_response = wp_remote_post($product_api_url, array('body' => $product_api_input_vars));
    $product_api_response = json_decode(wp_remote_retrieve_body($product_api_response), true);

    if (!is_array($product_api_response) || empty($product_api_response['pro_version']) || version_compare(VERSION, $product_api_response['pro_version'], '>=')) {
        return; // Current pro version is the latest stable version. Nothing more to do here.
    }
    $pro_updater_page = network_admin_url('/admin.php'); // Page that initiates an update.
    $pro_updater_page = add_query_arg(urlencode_deep(array('page' => GLOBAL_NS.'-pro-updater')), $pro_updater_page);

    $self->enqueueNotice(sprintf(__('<strong>%1$s Pro:</strong> a new version is now available. Please <a href="%2$s">upgrade to v%3$s</a>.', SLUG_TD), esc_html(NAME), esc_attr($pro_updater_page), esc_html($product_api_response['pro_version'])), 'persistent-new-pro-version-available');
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
    if (!is_admin() || $GLOBALS['pagenow'] !== 'update.php') {
        return $types; // Nothing to do here.
    }
    $_r = $self->trimDeep(stripslashes_deep($_REQUEST));

    if (empty($_r['action']) || $_r['action'] !== 'upgrade-plugin') {
        return $types; // Nothing to do here.
    }
    if (empty($_r[GLOBAL_NS.'__update_pro_version']) || !($update_pro_version = (string) $_r[GLOBAL_NS.'__update_pro_version'])) {
        return $types; // Nothing to do here.
    }
    if (empty($_r[GLOBAL_NS.'__update_pro_zip']) || !($update_pro_zip = (string) $_r[GLOBAL_NS.'__update_pro_zip'])) {
        return $types; // Nothing to do here.
    }
    echo '<script type="text/javascript">';
    echo '   (function($){ $(document).ready(function(){';
    echo '      var $form = $(\'input#hostname\').closest(\'form\');';
    echo '      $form.append(\'<input type="hidden" name="'.esc_attr(GLOBAL_NS.'__update_pro_version').'" value="'.esc_attr($update_pro_version).'" />\');';
    echo '      $form.append(\'<input type="hidden" name="'.esc_attr(GLOBAL_NS.'__update_pro_zip').'" value="'.esc_attr($update_pro_zip).'" />\');';
    echo '   }); })(jQuery);';
    echo '</script>';

    return $types; // Filter through.
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
    if (!is_admin() || $GLOBALS['pagenow'] !== 'update.php') {
        return $transient; // Nothing to do here.
    }
    $_r = $self->trimDeep(stripslashes_deep($_REQUEST));

    if (empty($_r['action']) || $_r['action'] !== 'upgrade-plugin') {
        return $transient; // Nothing to do here.
    }
    if (!current_user_can($self->update_cap)) {
        return $transient; // Nothing to do here.
    }
    if (empty($_r['_wpnonce']) || !wp_verify_nonce((string) $_r['_wpnonce'], 'upgrade-plugin_'.plugin_basename(PLUGIN_FILE))) {
        return $transient; // Nothing to do here.
    }
    if (empty($_r[GLOBAL_NS.'__update_pro_version']) || !($update_pro_version = (string) $_r[GLOBAL_NS.'__update_pro_version'])) {
        return $transient; // Nothing to do here.
    }
    if (empty($_r[GLOBAL_NS.'__update_pro_zip']) || !($update_pro_zip = base64_decode((string) $_r[GLOBAL_NS.'__update_pro_zip'], true))) {
        return $transient; // Nothing to do here.
    }
    if (!is_object($transient)) {
        $transient = new \stdClass();
    }
    $transient->last_checked                           = time();
    $transient->checked[plugin_basename(PLUGIN_FILE)]  = VERSION;
    $transient->response[plugin_basename(PLUGIN_FILE)] = (object) array(
        'id'          => 0,
        'slug'        => basename(PLUGIN_FILE, '.php'),
        'url'         => add_query_arg(urlencode_deep(array('page' => GLOBAL_NS.'-pro-updater')), self_admin_url('/admin.php')),
        'new_version' => $update_pro_version,
        'package'     => $update_pro_zip,
    );
    return $transient; // Nodified now.
};
