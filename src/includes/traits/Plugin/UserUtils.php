<?php
namespace WebSharks\CometCache\Pro\Traits\Plugin;

trait UserUtils {
    /*
     * Current user can clear the cache?
     *
     * @since 151002 Enhancing user permissions.
     *
     * @return boolean Current user can clear the cache?
     */
    public function currentUserCanClearCache()
    {
        if (!is_null($can = &$this->cacheKey('currentUserCanClearCache'))) {
            return $can; // Already cached this.
        }
        $is_multisite = is_multisite();

        if (!$is_multisite && current_user_can($this->cap)) {
            return ($can = true); // Plugin admin.
        }
        if ($is_multisite && current_user_can($this->network_cap)) {
            return ($can = true); // Plugin admin.
        }
        /*[pro strip-from="lite"]*/
        if (current_user_can($this->clear_min_cap)) { // Might be a privileged user?
            foreach (preg_split('/,+/', $this->options['cache_clear_admin_bar_roles_caps'], -1, PREG_SPLIT_NO_EMPTY) as $_role_cap) {
                if ($_role_cap && current_user_can($_role_cap)) {
                    return ($can = true); // Privileged user.
                }
            }
            unset($_role_cap); // Housekeeping.
        }
        /*[/pro]*/
        return ($can = false);
    }

    /*
     * Alias for currentUserCanClearCache()
     *
     * @since 151002 Enhancing user permissions.
     *
     * @return boolean Current user can clear the cache?
     */
    public function currentUserCanWipeCache()
    {
        return call_user_func_array([$this, 'currentUserCanClearCache'], func_get_args());
    }

    /*
     * Current user can clear the opcache?
     *
     * @since 151114 Enhancing user permissions.
     *
     * @return boolean Current user can clear the opcache?
     */
    public function currentUserCanClearOpCache()
    {
        if (!is_null($can = &$this->cacheKey('currentUserCanClearOpCache'))) {
            return $can; // Already cached this.
        }
        $is_multisite = is_multisite();

        if (!$is_multisite && current_user_can($this->cap)) {
            return ($can = true); // Plugin admin.
        }
        if ($is_multisite && current_user_can($this->network_cap)) {
            return ($can = true); // Plugin admin.
        }
        return ($can = false);
    }

    /*
     * Alias for currentUserCanClearOpCache()
     *
     * @since 151114 Enhancing user permissions.
     *
     * @return boolean Current user can clear the opcache?
     */
    public function currentUserCanWipeOpCache()
    {
        return call_user_func_array([$this, 'currentUserCanClearOpCache'], func_get_args());
    }

    /*
     * Current user can clear the CDN cache?
     *
     * @since 151114 Enhancing user permissions.
     *
     * @return boolean Current user can clear the CDN cache?
     */
    public function currentUserCanClearCdnCache()
    {
        if (!is_null($can = &$this->cacheKey('currentUserCanClearCdnCache'))) {
            return $can; // Already cached this.
        }
        $is_multisite = is_multisite();

        if (!$is_multisite && current_user_can($this->cap)) {
            return ($can = true); // Plugin admin.
        }
        if ($is_multisite && current_user_can($this->network_cap)) {
            return ($can = true); // Plugin admin.
        }
        return ($can = false);
    }

    /*
     * Alias for currentUserCanClearCdnCache()
     *
     * @since 151114 Enhancing user permissions.
     *
     * @return boolean Current user can clear the CDN cache?
     */
    public function currentUserCanWipeCdnCache()
    {
        return call_user_func_array([$this, 'currentUserCanClearCdnCache'], func_get_args());
    }

    /*
    * Current user can clear expired transients?
    *
    * @since 151220 Enhancing user permissions.
    *
    * @return boolean Current user can clear expired transients?
    */
    public function currentUserCanClearExpiredTransients()
    {
        if (!is_null($can = &$this->cacheKey('currentUserCanClearExpiredTransients'))) {
            return $can; // Already cached this.
        }
        $is_multisite = is_multisite();

        if (!$is_multisite && current_user_can($this->cap)) {
            return ($can = true); // Plugin admin.
        }
        if ($is_multisite && current_user_can($this->network_cap)) {
            return ($can = true); // Plugin admin.
        }
        return ($can = false);
    }

    /*
    * Alias for currentUserCanClearExpiredTransients()
    *
    * @since 151220 Enhancing user permissions.
    *
    * @return boolean Current user can clear expired transients?
    */
    public function currentUserCanWipeExpiredTransients()
    {
        return call_user_func_array([$this, 'currentUserCanClearExpiredTransients'], func_get_args());
    }

    /*
     * Current user can see stats?
     *
     * @since 151002 Enhancing user permissions.
     *
     * @return boolean Current user can see stats?
     */
    public function currentUserCanSeeStats()
    {
        if (!is_null($can = &$this->cacheKey('currentUserCanSeeStats'))) {
            return $can; // Already cached this.
        }
        $is_multisite = is_multisite();

        if (!$is_multisite && current_user_can($this->cap)) {
            return ($can = true); // Plugin admin.
        }
        if ($is_multisite && current_user_can($this->network_cap)) {
            return ($can = true); // Plugin admin.
        }
        /*[pro strip-from="lite"]*/
        if (current_user_can($this->stats_min_cap)) { // Might be a privileged user?
            foreach (preg_split('/,+/', $this->options['stats_admin_bar_roles_caps'], -1, PREG_SPLIT_NO_EMPTY) as $_role_cap) {
                if ($_role_cap && current_user_can($_role_cap)) {
                    return ($can = true); // Privileged user.
                }
            }
            unset($_role_cap); // Housekeeping.
        }
        /*[/pro]*/
        return ($can = false);
    }
}
