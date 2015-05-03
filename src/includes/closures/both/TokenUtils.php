<?php
namespace WebSharks\ZenCache\Pro;

// @TODO Review these methods.

/*
 * Produces a token based on the current `$_SERVER['HTTP_HOST']`.
 *
 * @since 140422 First documented version.
 *
 * @param boolean $dashify Optional, defaults to a `FALSE` value.
 *    If `TRUE`, the token is returned with dashes in place of `[^a-z0-9\/]`.
 *
 * @return string Token based on the current `$_SERVER['HTTP_HOST']`.
 *
 * @note The return value of this function is cached to reduce overhead on repeat calls.
 */
$self->hostToken = function ($dashify = false) use ($self) {
    $dashify = (integer) $dashify;

    if (isset(static::$static[__FUNCTION__][$dashify])) {
        return static::$static[__FUNCTION__][$dashify];
    }
    $host        = strtolower($_SERVER['HTTP_HOST']);
    $token_value = $dashify ? trim(preg_replace('/[^a-z0-9\/]/i', '-', $host), '-') : $host;

    return (static::$static[__FUNCTION__][$dashify] = $token_value);
};

/*
 * Produces a token based on the current site's base directory.
 *
 * @since 140605 First documented version.
 *
 * @param boolean $dashify Optional, defaults to a `FALSE` value.
 *    If `TRUE`, the token is returned with dashes in place of `[^a-z0-9\/]`.
 *
 * @return string Produces a token based on the current site's base directory;
 *    (i.e. in the case of a sub-directory multisite network).
 *
 * @note The return value of this function is cached to reduce overhead on repeat calls.
 *
 * @see plugin::clear_cache()
 * @see plugin::update_blog_paths()
 */
$self->hostBaseToken = function ($dashify = false) use ($self) {
    $dashify = (integer) $dashify;

    if (isset(static::$static[__FUNCTION__][$dashify])) {
        return static::$static[__FUNCTION__][$dashify];
    }
    $host_base_token = '/'; // Assume NOT multisite; or own domain.

    if (is_multisite() && (!defined('SUBDOMAIN_INSTALL') || !SUBDOMAIN_INSTALL)) {
        if (defined('PATH_CURRENT_SITE')) {
            $host_base_token = PATH_CURRENT_SITE;
        } elseif (!empty($GLOBALS['base'])) {
            $host_base_token = $GLOBALS['base'];
        }
        $host_base_token = trim($host_base_token, '\\/'." \t\n\r\0\x0B");
        $host_base_token = isset($host_base_token[0]) ? '/'.$host_base_token.'/' : '/';
    }
    $token_value = $dashify ? trim(preg_replace('/[^a-z0-9\/]/i', '-', $host_base_token), '-') : $host_base_token;

    return (static::$static[__FUNCTION__][$dashify] = $token_value);
};

/*
 * Produces a token based on the current blog's sub-directory.
 *
 * @since 140422 First documented version.
 *
 * @param boolean $dashify Optional, defaults to a `FALSE` value.
 *    If `TRUE`, the token is returned with dashes in place of `[^a-z0-9\/]`.
 *
 * @return string Produces a token based on the current blog sub-directory
 *    (i.e. in the case of a sub-directory multisite network).
 *
 * @note The return value of this function is cached to reduce overhead on repeat calls.
 *
 * @see plugin::clear_cache()
 * @see plugin::update_blog_paths()
 */
$self->hostDirToken = function ($dashify = false) use ($self) {
    $dashify = (integer) $dashify;

    if (isset(static::$static[__FUNCTION__][$dashify])) {
        return static::$static[__FUNCTION__][$dashify];
    }
    $host_dir_token = '/'; // Assume NOT multisite; or own domain.

    if (is_multisite() && (!defined('SUBDOMAIN_INSTALL') || !SUBDOMAIN_INSTALL)) {
        $uri_minus_base = // Supports `/sub-dir/child-blog-sub-dir/` also.
            preg_replace('/^'.preg_quote($self->hostBaseToken(), '/').'/', '', $_SERVER['REQUEST_URI']);

        list($host_dir_token) = explode('/', trim($uri_minus_base, '/'));
        $host_dir_token       = isset($host_dir_token[0]) ? '/'.$host_dir_token.'/' : '/';

        if ($host_dir_token !== '/' // Perhaps NOT the main site?
           && (!is_file(($cache_dir = $self->cacheDir()).'/zc-blog-paths') // NOT a read/valid blog path?
               || !in_array($host_dir_token, unserialize(file_get_contents($cache_dir.'/zc-blog-paths')), true))
        ) {
            $host_dir_token = '/'; // Main site; e.g. this is NOT a real/valid child blog path.
        }
    }
    $token_value = $dashify ? trim(preg_replace('/[^a-z0-9\/]/i', '-', $host_dir_token), '-') : $host_dir_token;

    return (static::$static[__FUNCTION__][$dashify] = $token_value);
};

/*
 * Produces tokens for the current site's base directory & current blog's sub-directory.
 *
 * @since 140422 First documented version.
 *
 * @param boolean $dashify Optional, defaults to a `FALSE` value.
 *    If `TRUE`, the tokens are returned with dashes in place of `[^a-z0-9\/]`.
 *
 * @return string Tokens for the current site's base directory & current blog's sub-directory.
 *
 * @note The return value of this function is cached to reduce overhead on repeat calls.
 *
 * @see plugin::clear_cache()
 * @see plugin::update_blog_paths()
 */
$self->hostBaseDirTokens = function ($dashify = false) use ($self) {
    $dashify = (integer) $dashify;

    if (isset(static::$static[__FUNCTION__][$dashify])) {
        return static::$static[__FUNCTION__][$dashify];
    }
    $tokens = preg_replace('/\/{2,}/', '/', $self->hostBaseToken($dashify).$self->hostDirToken($dashify));

    return (static::$static[__FUNCTION__][$dashify] = $tokens);
};

/*
 * Produces a token based on the current user.
 *
 * @since 140422 First documented version.
 *
 * @return string Produces a token based on the current user;
 *    else an empty string if that's not possible to do.
 *
 * @note The return value of this function is cached to reduce overhead on repeat calls.
 *
 * @note This routine may trigger a flag which indicates that the current user was logged-in at some point,
 *    but now the login cookie can no longer be validated by WordPress; i.e. they are NOT actually logged in any longer.
 *    See {@link $user_login_cookie_expired_or_invalid}
 *
 * @warning Do NOT call upon this method until WordPress reaches it's cache postload phase.
 */
$self->userToken = function () use ($self) {
    if (isset(static::$static[__FUNCTION__])) {
        return static::$static[__FUNCTION__];
    }
    $wp_validate_auth_cookie_possible = $self->functionIsPossible('wp_validate_auth_cookie');
    if ($wp_validate_auth_cookie_possible && ($user_id = (integer) wp_validate_auth_cookie('', 'logged_in'))) {
        return (static::$static[__FUNCTION__] = $user_id); // A real user in this case.
    } elseif (!empty($_COOKIE['comment_author_email_'.COOKIEHASH]) && is_string($_COOKIE['comment_author_email_'.COOKIEHASH])) {
        return (static::$static[__FUNCTION__] = md5(strtolower(stripslashes($_COOKIE['comment_author_email_'.COOKIEHASH]))));
    } elseif (!empty($_COOKIE['wp-postpass_'.COOKIEHASH]) && is_string($_COOKIE['wp-postpass_'.COOKIEHASH])) {
        return (static::$static[__FUNCTION__] = md5(stripslashes($_COOKIE['wp-postpass_'.COOKIEHASH])));
    } elseif (defined('SID') && SID) {
        return (static::$static[__FUNCTION__] = preg_replace('/[^a-z0-9]/i', '', SID));
    }
    if ($wp_validate_auth_cookie_possible // We were unable to validate the login cookie?
       && !empty($_COOKIE['wordpress_logged_in_'.COOKIEHASH]) && is_string($_COOKIE['wordpress_logged_in_'.COOKIEHASH])
    ) {
        $self->user_login_cookie_expired_or_invalid = true;
    }
    return (static::$static[__FUNCTION__] = '');
};
