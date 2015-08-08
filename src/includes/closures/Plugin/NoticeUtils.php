<?php
namespace WebSharks\ZenCache\Pro;

/*
 * Notice utilities.
 */

/*
 * Get admin notices.
 *
 * @since 15xxxx Improving multisite compat.
 *
 * @param integer $blog_id Optional. Defaults to the current blog ID.
 *  Use any value `< 0` to indicate the main site.
 *
 * @return array All notices.
 */
$self->getNotices = function ($blog_id = 0) use ($self) {
    if (is_multisite()) {
        if (!($blog_id = (integer) $blog_id)) {
            $blog_id = (integer) get_current_blog_id();
        }
        if ($blog_id < 0) { // Blog for main site.
            $blog_id = (integer) $GLOBALS['current_site']->blog_id;
        }
        $blog_suffix = '_'.$blog_id; // Site option suffix.
        $notices     = get_site_option(GLOBAL_NS.$blog_suffix.'_notices');
    } else {
        $notices = get_site_option(GLOBAL_NS.'_notices');
    }
    if (!is_array($notices)) {
        $notices = array(); // Force array.
        $self->updateNotices($notices, $blog_id);
    }
    return $notices;
};

/*
 * Update admin notices.
 *
 * @since 15xxxx Improving multisite compat.
 *
 * @param array $notices New array of notices.
 *
 * @param integer $blog_id Optional. Defaults to the current blog ID.
 *  Use any value `< 0` to indicate the main site.
 */
$self->updateNotices = function (array $notices, $blog_id = 0) use ($self) {
    if (is_multisite()) {
        if (!($blog_id = (integer) $blog_id)) {
            $blog_id = (integer) get_current_blog_id();
        }
        if ($blog_id < 0) { // Blog for main site.
            $blog_id = (integer) $GLOBALS['current_site']->blog_id;
        }
        $blog_suffix = '_'.$blog_id; // Site option suffix.
        update_site_option(GLOBAL_NS.$blog_suffix.'_notices', $notices);
    } else {
        update_site_option(GLOBAL_NS.'_notices', $notices);
    }
};

/*
 * Notice queue additions.
 */

/*
 * Enqueue an administrative notice.
 *
 * @since 150422 Rewrite. Improved 15xxxx.
 *
 * @param string $notice         HTML markup containing the notice itself.
 *
 * @param string $persistent_key Optional. A unique key which identifies a particular type of persistent notice.
 *
 * @param bool   $push_to_top    Optional. Defaults to a `false` value. If `true`, the notice is pushed to the top of the stack; i.e. displayed above any others.
 *
 * @param integer $blog_id Optional. Defaults to the current blog ID. Use any value `< 0` to indicate the main site.
 *
 * @param string $class A specific notice class. Defaults to an empty string. Pass as `error` to indicate it's an error.
 */
$self->enqueueNotice = function ($notice, $persistent_key = '', $push_to_top = false, $blog_id = 0, $class = '') use ($self) {
    $notice         = (string) $notice;
    $persistent_key = (string) $persistent_key;
    $blog_id        = (integer) $blog_id;
    $class          = (string) $class;

    if (!$notice) {
        return; // Nothing to do.
    }
    $notices = $self->getNotices($blog_id);

    if ($persistent_key) { // Persistent?
        if (stripos($persistent_key, 'persistent--') !== 0) {
            $persistent_key = 'persistent--'.$persistent_key;
        }
        $key = $persistent_key; // Hard-coded, persistent key.
    } else {
        $key = uniqid('', true); // Auto-generated unique ID key.
    }
    if ($class) { // Add a class key suffix? e.g., is this an error?
        $key .= '--class-'.$class; // e.g., `class--error`.
    }
    if ($push_to_top) { // Prepend key in this case.
        $notices = array($key => $notice) + $notices;
    } else {
        $notices[$key] = $notice; // Append key.
    }
    $self->updateNotices($notices, $blog_id);
};

/*
 * Enqueue an administrative error notice.
 *
 * @since 150422 Rewrite. Improved 15xxxx.
 */
$self->enqueueError = function ($notice, $persistent_key = '', $push_to_top = false, $blog_id = 0) use ($self) {
    $self->enqueueNotice($notice, $persistent_key, $push_to_top, $blog_id, 'error');
};

/*
 * Enqueue an administrative notice (main site).
 *
 * @since 15xxxx. Improving multisite compat.
 */
$self->enqueueMainNotice = function ($notice, $persistent_key = '', $push_to_top = false) use ($self) {
    $self->enqueueNotice($notice, $persistent_key, $push_to_top, -1, '');
};

/*
 * Enqueue an administrative error notice (main site).
 *
 * @since 15xxxx. Improving multisite compat.
 */
$self->enqueueMainError = function ($notice, $persistent_key = '', $push_to_top = false) use ($self) {
    $self->enqueueNotice($notice, $persistent_key, $push_to_top, -1, 'error');
};

/*
 * Notice queue removals.
 */

/*
 * Dismiss an administrative notice.
 *
 * @since 15xxxx Improving multisite compat.
 *
 * @param string $key A unique key which identifies a particular notice.
 *
 * @param integer $blog_id The blog ID from which to dismiss the notice.
 */
$self->dismissNotice = function ($key, $blog_id = 0) use ($self) {
    $key     = (string) $key;
    $blog_id = (integer) $blog_id;

    if (!$key) {
        return; // Empty.
    }
    $notices = $self->getNotices($blog_id);
    unset($notices[$key]); // Dismiss this key.
    $self->updateNotices($notices, $blog_id);
};

/*
 * Dismiss an administrative notice (main site).
 *
 * @since 15xxxx Improving multisite compat.
 *
 * @param string $key A unique key which identifies a particular notice.
 */
$self->dismissMainNotice = function ($key) use ($self) {
    $self->dismissNotice($key, -1);
};

/*
 * Notice display handler.
 */

/*
 * Render admin notices.
 *
 * @since 150422 Rewrite. Improved 15xxxx.
 *
 * @attaches-to `all_admin_notices` hook.
 */
$self->allAdminNotices = function () use ($self) {
    if (!($notices = $self->getNotices())) {
        return; // Nothing to do.
    }
    $notices = $updated_notices = array_unique($notices);

    foreach (array_keys($updated_notices) as $_key) {
        if (!is_string($_key) || stripos($_key, 'persistent--') !== 0) {
            unset($updated_notices[$_key]);
        }
    } // Leave persistent notices; ditch others.
    unset($_key); // Housekeeping after updating notices.

    $self->updateNotices($updated_notices); // Persistent only.

    if (!current_user_can($self->cap)) {
        return; // Unable to see.
    }
    foreach ($notices as $_key => $_notice) {
        if (is_string($_key) && stripos($_key, 'persistent--') === 0) {
            $_dismiss_css = 'display:inline-block; float:right; margin:0 0 0 15px; text-decoration:none; font-weight:bold;';
            $_dismiss     = add_query_arg(urlencode_deep(array(GLOBAL_NS => array('dismissNotice' => array('key' => $_key)), '_wpnonce' => wp_create_nonce())));
            $_dismiss     = '<a style="'.esc_attr($_dismiss_css).'" href="'.esc_attr($_dismiss).'">'.__('dismiss &times;', SLUG_TD).'</a>';
        } else {
            $_dismiss_css = $_dismiss = ''; // Empty.
        }
        if (stripos($_key, 'new-pro-version-available') !== false && (!IS_PRO || !current_user_can($self->update_cap))) {
            continue; // Unable to see.
        }
        if (stripos($_key, 'class--error') !== false) {
            $_class = 'error'; // Error notice.
        } else {
            $_class = 'updated'; // Notice.
        }
        echo '<div class="'.esc_attr($_class).'"><p>'.$_notice.$_dismiss.'</p></div>';
    }
    unset($_key, $_notice, $_dismiss_css, $_dismiss, $_class); // Housekeeping.
};
