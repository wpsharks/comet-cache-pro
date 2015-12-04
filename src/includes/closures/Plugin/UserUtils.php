<?php
namespace WebSharks\ZenCache\Pro;

/*
 * Current user can clear the cache?
 *
 * @since 151002 Enhancing user permissions.
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
    if (current_user_can($self->clear_min_cap)) { // Might be a privileged user?
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
$self->currentUserCanWipeCache = $self->currentUserCanClearCache;

/*
 * Current user can clear the opcache?
 *
 * @since 151114 Enhancing user permissions.
 *
 * @return boolean Current user can clear the opcache?
 */
$self->currentUserCanClearOpCache = function () use ($self) {
    if (!is_null($can = &$self->cacheKey('currentUserCanClearOpCache'))) {
        return $can; // Already cached this.
    }
    $is_multisite = is_multisite();

    if (!$is_multisite && current_user_can($self->cap)) {
        return ($can = true); // Plugin admin.
    }
    if ($is_multisite && current_user_can($self->network_cap)) {
        return ($can = true); // Plugin admin.
    }
    return ($can = false);
};
$self->currentUserCanWipeOpCache = $self->currentUserCanClearOpCache;

/*
 * Current user can clear the CDN cache?
 *
 * @since 151114 Enhancing user permissions.
 *
 * @return boolean Current user can clear the CDN cache?
 */
$self->currentUserCanClearCdnCache = function () use ($self) {
    if (!is_null($can = &$self->cacheKey('currentUserCanClearCdnCache'))) {
        return $can; // Already cached this.
    }
    $is_multisite = is_multisite();

    if (!$is_multisite && current_user_can($self->cap)) {
        return ($can = true); // Plugin admin.
    }
    if ($is_multisite && current_user_can($self->network_cap)) {
        return ($can = true); // Plugin admin.
    }
    return ($can = false);
};
$self->currentUserCanWipeCdnCache = $self->currentUserCanClearCdnCache;

/*
* Current user can clear expired transients?
*
* @since 15xxxx Enhancing user permissions.
*
* @return boolean Current user can clear expired transients?
*/
$self->currentUserCanClearExpiredTransients = function () use ($self) {
    if (!is_null($can = &$self->cacheKey('currentUserCanClearExpiredTransients'))) {
        return $can; // Already cached this.
    }
    $is_multisite = is_multisite();

    if (!$is_multisite && current_user_can($self->cap)) {
        return ($can = true); // Plugin admin.
    }
    if ($is_multisite && current_user_can($self->network_cap)) {
        return ($can = true); // Plugin admin.
    }
    return ($can = false);
};
$self->currentUserCanWipeExpiredTransients = $self->currentUserCanClearExpiredTransients;

/*
 * Current user can see stats?
 *
 * @since 151002 Enhancing user permissions.
 *
 * @return boolean Current user can see stats?
 */
$self->currentUserCanSeeStats = function () use ($self) {
    if (!is_null($can = &$self->cacheKey('currentUserCanSeeStats'))) {
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
