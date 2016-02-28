<?php
namespace WebSharks\CometCache\Pro\Traits\Plugin;

use WebSharks\CometCache\Pro\Classes;

trait NoticeUtils {
    /*
     * Notice queue handlers.
     */

    /*
     * Enqueue an administrative notice.
     *
     * @since 150422 Rewrite. Improved 151002.
     *
     * @param string $notice         HTML markup containing the notice itself.
     * @param string $args Any additional arguments supported by the notice API in this plugin.
     * @param integer $blog_id Optional. Defaults to the current blog ID. Use any value `< 0` to indicate the main site.
     *
     * @return string A unique key generated for this notice.
     */
    public function enqueueNotice($notice, array $args = [], $blog_id = 0)
    {
        $notice  = trim((string) $notice);
        $blog_id = (integer) $blog_id;

        if (!$notice) {
            return; // Nothing to do.
        }
        $notice = ['notice' => $notice];
        $notice = $this->normalizeNotice($notice, $args);
        $key    = sha1(serialize($notice)); // Prevent dupes.

        $notices = $this->getNotices($blog_id);

        if ($notice['push_to_top']) {
            $notices = [$key => $notice] + $notices;
        } else {
            $notices[$key] = $notice; // Default behavior.
        }
        $this->updateNotices($notices, $blog_id);

        return $key; // For dismissals.
    }

    /*
     * Dismiss an administrative notice.
     *
     * @since 151002 Improving multisite compat.
     *
     * @param string $key_to_dismiss A unique key which identifies a particular notice.
     *  Or, a persistent key which identifies one or more persistent notices.
     *
     * @param integer $blog_id The blog ID from which to dismiss the notice.
     *
     * @return array All remaining notices.
     */
    public function dismissNotice($key_to_dismiss, $blog_id = 0)
    {
        $key_to_dismiss = trim((string) $key_to_dismiss);
        $blog_id        = (integer) $blog_id; // For multisite compat.
        $notices        = $enqueued_notices = $this->getNotices($blog_id);

        if (!$key_to_dismiss) {
            return $notices; // Nothing to do.
        }
        foreach ($notices as $_key => $_notice) {
            if ($_key === $key_to_dismiss) {
                unset($notices[$_key]); // A specific key.
            } elseif ($_notice['persistent_key'] === $key_to_dismiss) {
                unset($notices[$_key]); // All matching keys.
            }
        } // ↑ Dismisses all matching keys.
        unset($_key, $_notice); // Housekeeping.

        if ($notices !== $enqueued_notices) { // Something changed?
            $this->updateNotices($notices, $blog_id); // Update.
        }
        return $notices; // All remaining notices.
    }

    /*
     * Enqueue an administrative error notice.
     *
     * @since 150422 Rewrite. Improved 151002.
     */
    public function enqueueError($notice, array $args = [], $blog_id = 0)
    {
        return $this->enqueueNotice($notice, array_merge($args, ['class' => 'error']), $blog_id);
    }

    /*
     * Enqueue an administrative notice (main site).
     *
     * @since 151002. Improving multisite compat.
     */
    public function enqueueMainNotice($notice, array $args = [])
    {
        return $this->enqueueNotice($notice, $args, -1);
    }

    /*
     * Enqueue an administrative error notice (main site).
     *
     * @since 151002. Improving multisite compat.
     */
    public function enqueueMainError($notice, array $args = [])
    {
        return $this->enqueueNotice($notice, array_merge($args, ['class' => 'error']), -1);
    }

    /*
     * Dismiss an administrative notice (main site).
     *
     * @since 151002 Improving multisite compat.
     */
    public function dismissMainNotice($key_to_dismiss)
    {
        return $this->dismissNotice($key_to_dismiss, -1);
    }

    /*
     * Notice display handler.
     */

    /*
     * Render admin notices.
     *
     * @since 150422 Rewrite. Improved 151002.
     *
     * @attaches-to `all_admin_notices` hook.
     */
    public function allAdminNotices()
    {
        $notices = $enqueued_notices = $this->getNotices();

        foreach ($notices as $_key => $_notice) {
            # Always dismiss all non-persistent transients.

            if ($_notice['is_transient'] && !$_notice['persistent_key']) {
                unset($notices[$_key]); // Dismiss.
            }
            # Current user can see this notice?

            if (!current_user_can($this->cap)) {
                continue; // Current user unable to see.
            }
            if ($_notice['cap_required'] && !current_user_can($_notice['cap_required'])) {
                continue; // Current user unable to see this notice.
            }
            # Current URI matches a limited scope/context for this notice?

            if ($_notice['only_on_uris'] && !@preg_match($_notice['only_on_uris'], $_SERVER['REQUEST_URI'])) {
                continue; // Not in the right context at the moment; i.e., does not regex.
            }
            # If persistent, allow a site owner to dismiss.

            $_dismiss = ''; // Reset this to its default state.
            if ($_notice['persistent_key'] && $_notice['dismissable']) { // See above. The `dismissNotice()` action requires `$this->cap` always.
                $_dismiss = add_query_arg(urlencode_deep([GLOBAL_NS => ['dismissNotice' => ['key' => $_key]], '_wpnonce' => wp_create_nonce()]));
                $_dismiss = '<a style="display:inline-block; float:right; margin:0 0 0 15px; text-decoration:none; font-weight:bold;" href="'.esc_attr($_dismiss).'">'.__('dismiss &times;', SLUG_TD).'</a>';
            }
            # Display this notice. If not persistent, we can dismiss it too.

            echo '<div class="'.esc_attr($_notice['class']).'"><p>'.$_notice['notice'].$_dismiss.'</p></div>';

            if (!$_notice['persistent_key']) { // If not persistent, dismiss.
                unset($notices[$_key]); // Dismiss; this notice has been displayed now.
            }
        }
        unset($_key, $_notice, $_dismiss); // Housekeeping.

        # Update notices if something changed above.

        if ($notices !== $enqueued_notices) { // Something changed?
            $this->updateNotices($notices); // Update.
        }
    }

    /*
     * Notice getter/setter.
     */

    /*
     * Get admin notices.
     *
     * @since 151002 Improving multisite compat.
     *
     * @param integer $blog_id Optional. Defaults to the current blog ID.
     *  Use any value `< 0` to indicate the main site.
     *
     * @return array All notices.
     */
    public function getNotices($blog_id = 0)
    {
        if (is_multisite()) {
            if (!($blog_id = (integer) $blog_id)) {
                $blog_id = (integer) get_current_blog_id();
            }
            if ($blog_id < 0) { // Blog for main site.
                $blog_id = (integer) get_current_site()->blog_id;
            }
            $blog_suffix = '_'.$blog_id; // Site option suffix.
            $notices     = get_site_option(GLOBAL_NS.$blog_suffix.'_notices');
        } else {
            $notices = get_site_option(GLOBAL_NS.'_notices');
        }
        if (!is_array($notices)) {
            $notices = []; // Force array.
            // Prevent multiple DB queries by adding this key.
            $this->updateNotices($notices, $blog_id);
        }
        foreach ($notices as $_key => &$_notice) {
            if (!is_string($_key) || !is_array($_notice) || empty($_notice['notice'])) {
                unset($notices[$_key]); // Old notice.
                continue; // Bypass; i.e., do not normalize.
            }
            $_notice = $this->normalizeNotice($_notice);
        } // ↑ Typecast/normalized each of the array elements.
        unset($_key, $_notice); // Housekeeping.

        return $notices;
    }

    /*
     * Update admin notices.
     *
     * @since 151002 Improving multisite compat.
     *
     * @param array $notices New array of notices.
     *
     * @param integer $blog_id Optional. Defaults to the current blog ID.
     *  Use any value `< 0` to indicate the main site.
     *
     * @return array All notices.
     */
    public function updateNotices(array $notices, $blog_id = 0)
    {
        if (is_multisite()) {
            if (!($blog_id = (integer) $blog_id)) {
                $blog_id = (integer) get_current_blog_id();
            }
            if ($blog_id < 0) { // Blog for main site.
                $blog_id = (integer) get_current_site()->blog_id;
            }
            $blog_suffix = '_'.$blog_id; // Site option suffix.
            update_site_option(GLOBAL_NS.$blog_suffix.'_notices', $notices);
        } else {
            update_site_option(GLOBAL_NS.'_notices', $notices);
        }
        return $notices;
    }

    /*
     * Notice property utilities.
     */

    /*
    * Normalize notice elements.
    *
    * @since 151002 Improving multisite compat.
    *
    * @param array $notice Notice array elements.
    * @param array $args Any additional array elements.
    *
    * @return array Normalized notice array elements.
    */
    public function normalizeNotice(array $notice, array $args = [])
    {
        $notice_defaults = [
            'notice'         => '',
            'only_on_uris'   => '',
            'persistent_key' => '',
            'dismissable'    => true,
            'is_transient'   => true,
            'push_to_top'    => false,
            'class'          => 'updated',
            'cap_required'   => '', // `$this->cap` always.
            // i.e., this cap is in addition to `$this->cap`.
        ];
        $notice          = array_merge($notice_defaults, $notice, $args);
        $notice          = array_intersect_key($notice, $notice_defaults);

        foreach ($notice as $_key => &$_value) {
            switch ($_key) {
                case 'notice':
                case 'only_on_uris':
                case 'persistent_key':
                    $_value = trim((string) $_value);
                    break; // Stop here.

                case 'is_transient':
                case 'push_to_top':
                case 'dismissable':
                    $_value = (boolean) $_value;
                    break; // Stop here.

                case 'class':
                case 'cap_required':
                    $_value = trim((string) $_value);
                    break; // Stop here.
            }
        } // ↑ Typecast each of the array elements.
        unset($_key, $_value); // A little housekeeping.

        ksort($notice); // For more accurate comparison in other routines.

        return $notice; // Normalized.
    }
}
