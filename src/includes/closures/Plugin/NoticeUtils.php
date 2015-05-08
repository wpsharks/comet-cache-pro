<?php
namespace WebSharks\ZenCache\Pro;

/**
 * Utilities.
 *
 * @since 150422 Rewrite.
 */
class NoticeUtils extends AbsBase
{
    /**
     * Render admin notices; across all admin dashboard views.
     *
     * @since 140422 First documented version.
     *
     * @attaches-to `all_admin_notices` hook.
     */
    public function all_admin_notices()
    {
        if (($notices = (is_array($notices = get_option(__NAMESPACE__.'_notices'))) ? $notices : array())) {
            $notices = $updated_notices = array_unique($notices); // De-dupe.

            foreach (array_keys($updated_notices) as $_key) {
                if (strpos($_key, 'persistent-') !== 0) {
                    unset($updated_notices[$_key]);
                }
            } // Leave persistent notices; ditch others.
            unset($_key); // Housekeeping after updating notices.

            update_option(__NAMESPACE__.'_notices', $updated_notices);
        }
        if (current_user_can($this->cap)) {
            foreach ($notices as $_key => $_notice) {
                if ($_key === 'persistent-new-pro-version-available') {
                    if (!current_user_can($this->update_cap)) {
                        continue;
                    }
                } // Not applicable.

            $_dismiss = ''; // Initialize empty string; e.g. reset value on each pass.
            if (strpos($_key, 'persistent-') === 0) {
                // A dismissal link is needed in this case?

                $_dismiss_css = 'display:inline-block; float:right; margin:0 0 0 15px; text-decoration:none; font-weight:bold;';
                $_dismiss     = add_query_arg(urlencode_deep(array(__NAMESPACE__ => array('dismiss_notice' => array('key' => $_key)), '_wpnonce' => wp_create_nonce())));
                $_dismiss     = '<a style="'.esc_attr($_dismiss_css).'" href="'.esc_attr($_dismiss).'">'.__('dismiss &times;', $this->text_domain).'</a>';
            }
                if (strpos($_key, 'class-update-nag') !== false) {
                    $_class = 'update-nag';
                } elseif (strpos($_key, 'class-error') !== false) {
                    $_class = 'error';
                } else {
                    $_class = 'updated';
                }
                echo $this->apply_wp_filters(__METHOD__.'__notice', '<div class="'.$_class.'"><p>'.$_notice.$_dismiss.'</p></div>', get_defined_vars());
            }
        }
        unset($_key, $_notice, $_dismiss_css, $_dismiss); // Housekeeping.
    }

    /**
     * Enqueue an administrative notice.
     *
     * @since 140605 Adding enqueue notice/error methods.
     *
     * @param string $notice         HTML markup containing the notice itself.
     * @param string $persistent_key Optional. A unique key which identifies a particular type of persistent notice.
     *                               This defaults to an empty string. If this is passed, the notice is persistent; i.e. it continues to be displayed until dismissed by the site owner.
     * @param bool   $push_to_top    Optional. Defaults to a `FALSE` value.
     *                               If `TRUE`, the notice is pushed to the top of the stack; i.e. displayed above any others.
     */
    public function enqueue_notice($notice, $persistent_key = '', $push_to_top = false)
    {
        $notice         = (string) $notice;
        $persistent_key = (string) $persistent_key;

        $notices = get_option(__NAMESPACE__.'_notices');
        if (!is_array($notices)) {
            $notices = array();
        }

        if ($persistent_key) {
            // A persistent notice?

            if (strpos($persistent_key, 'persistent-') !== 0) {
                $persistent_key = 'persistent-'.$persistent_key;
            }

            if ($push_to_top) {
                // Push this notice to the top?
                $notices = array($persistent_key => $notice) + $notices;
            } else {
                $notices[$persistent_key] = $notice;
            }
        } elseif ($push_to_top) {
            // Push to the top?
            array_unshift($notices, $notice);
        } else {
            $notices[] = $notice;
        } // Default behavior.

        update_option(__NAMESPACE__.'_notices', $notices);
    }

    /**
     * Render admin errors; across all admin dashboard views.
     *
     * @since 140422 First documented version.
     *
     * @attaches-to `all_admin_notices` hook.
     */
    public function all_admin_errors()
    {
        if (($errors = (is_array($errors = get_option(__NAMESPACE__.'_errors'))) ? $errors : array())) {
            $errors = $updated_errors = array_unique($errors); // De-dupe.

            foreach (array_keys($updated_errors) as $_key) {
                if (strpos($_key, 'persistent-') !== 0) {
                    unset($updated_errors[$_key]);
                }
            } // Leave persistent errors; ditch others.
            unset($_key); // Housekeeping after updating notices.

            update_option(__NAMESPACE__.'_errors', $updated_errors);
        }
        if (current_user_can($this->cap)) {
            foreach ($errors as $_key => $_error) {
                $_dismiss = ''; // Initialize empty string; e.g. reset value on each pass.
            if (strpos($_key, 'persistent-') === 0) {
                // A dismissal link is needed in this case?

                $_dismiss_css = 'display:inline-block; float:right; margin:0 0 0 15px; text-decoration:none; font-weight:bold;';
                $_dismiss     = add_query_arg(urlencode_deep(array(__NAMESPACE__ => array('dismiss_error' => array('key' => $_key)), '_wpnonce' => wp_create_nonce())));
                $_dismiss     = '<a style="'.esc_attr($_dismiss_css).'" href="'.esc_attr($_dismiss).'">'.__('dismiss &times;', $this->text_domain).'</a>';
            }
                echo $this->apply_wp_filters(__METHOD__.'__error', '<div class="error"><p>'.$_error.$_dismiss.'</p></div>', get_defined_vars());
            }
        }
        unset($_key, $_error, $_dismiss_css, $_dismiss); // Housekeeping.
    }

    /**
     * Enqueue an administrative error.
     *
     * @since 140605 Adding enqueue notice/error methods.
     *
     * @param string $error          HTML markup containing the error itself.
     * @param string $persistent_key Optional. A unique key which identifies a particular type of persistent error.
     *                               This defaults to an empty string. If this is passed, the error is persistent; i.e. it continues to be displayed until dismissed by the site owner.
     * @param bool   $push_to_top    Optional. Defaults to a `FALSE` value.
     *                               If `TRUE`, the error is pushed to the top of the stack; i.e. displayed above any others.
     */
    public function enqueue_error($error, $persistent_key = '', $push_to_top = false)
    {
        $error          = (string) $error;
        $persistent_key = (string) $persistent_key;

        $errors = get_option(__NAMESPACE__.'_errors');
        if (!is_array($errors)) {
            $errors = array();
        }

        if ($persistent_key) {
            // A persistent notice?

            if (strpos($persistent_key, 'persistent-') !== 0) {
                $persistent_key = 'persistent-'.$persistent_key;
            }

            if ($push_to_top) {
                // Push this notice to the top?
                $errors = array($persistent_key => $error) + $errors;
            } else {
                $errors[$persistent_key] = $error;
            }
        } elseif ($push_to_top) {
            // Push to the top?
            array_unshift($errors, $error);
        } else {
            $errors[] = $error;
        } // Default behavior.

        update_option(__NAMESPACE__.'_errors', $errors);
    }
}
