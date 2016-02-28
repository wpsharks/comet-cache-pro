<?php
/*[pro strip-from="lite"]*/
namespace WebSharks\CometCache\Pro\Traits\Plugin;

trait WcpUserUtils {
    /*
     * Clears cache files associated with a particular user.
     *
     * @since 150422 Rewrite.
     *
     * @attaches-to `profile_update` hook.
     * @attaches-to `add_user_metadata` filter.
     * @attaches-to `updated_user_meta` hook.
     * @attaches-to `delete_user_metadata` filter.
     * @attaches-to `set_auth_cookie` hook.
     * @attaches-to `clear_auth_cookie` hook.
     *
     * @param int $user_id A WordPress user ID.
     *
     * @return int Total files cleared.
     */
    public function autoClearUserCache($user_id)
    {
        $counter = 0; // Initialize.

        if (!($user_id = (integer) $user_id)) {
            return $counter; // Nothing to do.
        }
        if (!is_null($done = &$this->cacheKey('autoClearUserCache', $user_id))) {
            return $counter; // Already did this.
        }
        $done = true; // Flag as having been done.

        if (!$this->options['enable']) {
            return $counter; // Nothing to do.
        }
        if ($this->options['when_logged_in'] !== 'postload') {
            return $counter; // Nothing to do.
        }
        $regex = $this->assembleCachePathRegex('', '.*?\.u\/'.preg_quote($user_id, '/').'[.\/]');
        $counter += $this->wipeFilesFromCacheDir($regex); // Clear matching files.

        if ($counter && is_admin() && (!IS_PRO || $this->options['change_notifications_enable'])) {
            $this->enqueueNotice(
                '<img src="'.esc_attr($this->url('/src/client-s/images/clear.png')).'" style="float:left; margin:0 10px 0 0; border:0;" />'.
                sprintf(__('<strong>%1$s:</strong> detected changes. Found %2$s in the cache for user ID: <code>%3$s</code>; auto-clearing.', SLUG_TD), esc_html(NAME), esc_html($this->i18nFiles($counter)), esc_html($user_id))
            );
        }
        return $counter;
    }

    /*
    * Back compat. alias for autoClearUserCache()
    */
    public function auto_clear_user_cache()
    {
        return call_user_func_array([$this, 'autoClearUserCache'], func_get_args());
    }

    /*
     * Automatically clears cache files associated with a particular user.
     *
     * @since 150422 Rewrite.
     *
     * @attaches-to `profile_update` hook.
     *
     * @param int $user_id A WordPress user ID.
     */
    public function autoClearUserCacheA1($user_id)
    {
        $this->autoClearUserCache($user_id);
    }

    /*
    * Automatically clears cache files associated with a particular user.
    *
    * @since 151220 Using `updated_user_meta` instead of `update_user_metadata`
    *
    * @attaches-to `updated_user_meta` hook.
    *
    * @param int    $meta_id    ID of updated metadata entry.
    * @param int    $object_id  Object ID.
    */
    public function autoClearUserCacheA2($meta_id, $object_id)
    {
        $this->autoClearUserCache($object_id);
    }

    /*
     * Automatically clears cache files associated with a particular user.
     *
     * @since 150422 Rewrite.
     *
     * @attaches-to `add_user_metadata` filter.
     * @attaches-to `updated_user_meta` hook.
     * @attaches-to `delete_user_metadata` filter.
     *
     * @param mixed $value   Filter value (passes through).
     * @param int   $user_id A WordPress user ID.
     *
     * @return mixed The same `$value` (passes through).
     */
    public function autoClearUserCacheFA2($value, $user_id)
    {
        $this->autoClearUserCache($user_id);
        return $value; // Filter.
    }

    /*
     * Automatically clears cache files associated with a particular user.
     *
     * @since 150422 Rewrite.
     *
     * @attaches-to `set_auth_cookie` hook.
     *
     * @param mixed $_       Irrelevant hook argument value.
     * @param mixed $__      Irrelevant hook argument value.
     * @param mixed $___     Irrelevant hook argument value.
     * @param int   $user_id A WordPress user ID.
     */
    public function autoClearUserCacheA4($_, $__, $___, $user_id)
    {
        $this->autoClearUserCache($user_id);
    }

    /*
     * Automatically clears cache files associated with current user.
     *
     * @since 150422 Rewrite.
     *
     * @attaches-to `clear_auth_cookie` hook.
     */
    public function autoClearUserCacheCur()
    {
        $this->autoClearUserCache(get_current_user_id());
    }

    /*
    * Back compat. alias for autoClearUserCache()
    */
    public function auto_clear_user_cache_cur()
    {
        return call_user_func_array([$this, 'autoClearUserCacheCur'], func_get_args());
    }
}
/*[/pro]*/
