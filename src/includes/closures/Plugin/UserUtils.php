<?php
namespace WebSharks\ZenCache\Pro;

/*
 * Current user can clear the cache?
 *
 * @since 15xxxx Enhancing user permissions.
 *
 * @return boolean Current user can clear the cache?
 */
$self->currentUserCanClearCache = function () use ($self) {
    if (!is_null($can = &$self->cacheKey('currentUserCanClearCache'))) {
        return $can; // Already cached this.
    }
    $is_multisite = is_multisite();

    if (!$is_multisite && current_user_can($self->cap)) {
        return ($can = true); // Plugin admin.
    }
    if ($is_multisite && current_user_can($self->network_cap)) {
        return ($can = true); // Plugin admin.
    }
    /*[pro strip-from="lite"]*/
    if (current_user_can($self->cache_clear_min_cap)) { // Might be a privileged user?
        foreach (preg_split('/,+/', $self->options['cache_clear_admin_bar_roles_caps'], null, PREG_SPLIT_NO_EMPTY) as $_role_cap) {
            if ($_role_cap && current_user_can($_role_cap)) {
                return ($can = true); // Privileged user.
            }
        }
        unset($_role_cap); // Housekeeping.
    }
    /*[/pro]*/
    return ($can = false);
};

/*
 * Current user can see stats?
 *
 * @since 15xxxx Enhancing user permissions.
 *
 * @return boolean Current user can see stats?
 */
$self->currentUserCanSeeStats = function () use ($self) {
    if (!is_null($can = &$self->cacheKey('currentUserCanClearCache'))) {
        return $can; // Already cached this.
    }
    $is_multisite = is_multisite();

    if (!$is_multisite && current_user_can($self->cap)) {
        return ($can = true); // Plugin admin.
    }
    if ($is_multisite && current_user_can($self->network_cap)) {
        return ($can = true); // Plugin admin.
    }
    /*[pro strip-from="lite"]*/
    if (current_user_can($self->stats_min_cap)) { // Might be a privileged user?
        foreach (preg_split('/,+/', $self->options['stats_admin_bar_roles_caps'], null, PREG_SPLIT_NO_EMPTY) as $_role_cap) {
            if ($_role_cap && current_user_can($_role_cap)) {
                return ($can = true); // Privileged user.
            }
        }
        unset($_role_cap); // Housekeeping.
    }
    /*[/pro]*/
    return ($can = false);
};
