<?php
namespace WebSharks\CometCache\Pro;

/*[pro strip-from="lite"]*/
/*
 * A simple utility flag.
 *
 * @since 150422 Rewrite.
 *
 * @type bool `TRUE` if expired or invalid.
 */
$self->user_login_cookie_expired_or_invalid = false;
/*[/pro]*/

/*
 * Current host.
 *
 * @since 150422 Rewrite.
 *
 * @param boolean $dashify Optional, defaults to a `FALSE` value.
 *    If `TRUE`, the token is returned with dashes in place of `[^a-z0-9]`.
 *
 * @param boolean $consider_domain_mapping Consider?
 *
 * @param string $consider_domain_mapping_domain A specific domain?
 *
 * @return string Current host.
 *
 * @note The return value of this function is cached to reduce overhead on repeat calls.
 */
$self->hostToken = function ($dashify = false, $consider_domain_mapping = false, $consider_domain_mapping_domain = '') use ($self) {
    if (!is_null($token = &$self->staticKey('hostToken', array($dashify, $consider_domain_mapping, $consider_domain_mapping_domain)))) {
        return $token; // Already cached this.
    }
    $token = ''; // Initialize token value.

    if (!is_multisite() || $self->isAdvancedCache()) {
        $token = (string) $_SERVER['HTTP_HOST'];
    } elseif ($consider_domain_mapping && $self->canConsiderDomainMapping()) {
        if (($consider_domain_mapping_domain = trim((string) $consider_domain_mapping_domain))) {
            $token = $consider_domain_mapping_domain;
        } elseif ($self->isDomainMapping()) {
            $token = (string) $_SERVER['HTTP_HOST'];
        } else { // For the current blog ID.
            $token = $self->domainMappingUrlFilter($self->currentUrl());
            $token = $self->parseUrl($token, PHP_URL_HOST);
        }
    }
    if (!$token) { // Use default?
        $token = (string) $_SERVER['HTTP_HOST'];
    }
    if ($token) { // Have token?
        $token = strtolower($token);
        if ($dashify) { // Dashify it?
            $token = preg_replace('/[^a-z0-9]/i', '-', $token);
            $token = trim($token, '-');
        }
    }
    return $token;
};

/*
 * Host for a specific blog.
 *
 * @since 150821 Improving multisite compat.
 *
 * @param boolean $dashify Optional, defaults to a `FALSE` value.
 *    If `TRUE`, the token is returned with dashes in place of `[^a-z0-9]`.
 *
 * @param boolean $consider_domain_mapping Consider?
 *
 * @param string $consider_domain_mapping_domain A specific domain?
 *
 * @param boolean $fallback Fallback on blog's domain when mapping?
 *
 * @param integer $blog_id For which blog ID?
 *
 * @return string Host for a specific blog.
 *
 * @note The return value of this function is NOT cached in support of `switch_to_blog()`.
 */
$self->hostTokenForBlog = function ($dashify = false, $consider_domain_mapping = false, $consider_domain_mapping_domain = '', $fallback = false, $blog_id = 0) use ($self) {
    if (!is_multisite() || $self->isAdvancedCache()) {
        return $self->hostToken($dashify, $consider_domain_mapping, $consider_domain_mapping_domain);
    }
    $token = ''; // Initialize token value.

    if ($consider_domain_mapping && $self->canConsiderDomainMapping()) {
        if (($consider_domain_mapping_domain = trim((string) $consider_domain_mapping_domain))) {
            $token = $consider_domain_mapping_domain; // Force this value.
        } else {
            $token = $self->domainMappingBlogDomain($blog_id, $fallback);
        }
    } elseif (($blog_details = $self->blogDetails($blog_id))) {
        $token = $blog_details->domain; // Unmapped domain.
    }
    if ($token) { // Have token?
        $token = strtolower($token);
        if ($dashify) { // Dashify it?
            $token = preg_replace('/[^a-z0-9]/i', '-', $token);
            $token = trim($token, '-');
        }
    }
    return $token;
};

/*
 * Current site's base directory.
 *
 * @since 150422 Rewrite.
 *
 * @param boolean $dashify Optional, defaults to a `FALSE` value.
 *    If `TRUE`, the token is returned with dashes in place of `[^a-z0-9\/]`.
 *
 * @param boolean $consider_domain_mapping Consider?
 *
 * @return string Current site's base directory.
 *
 * @note The return value of this function is cached to reduce overhead on repeat calls.
 */
$self->hostBaseToken = function ($dashify = false, $consider_domain_mapping = false) use ($self) {
    if (!is_null($token = &$self->staticKey('hostBaseToken', array($dashify, $consider_domain_mapping)))) {
        return $token; // Already cached this.
    }
    $token = '/'; // Assume NOT multisite; or own domain.

    if (!is_multisite()) {
        return $token; // Not applicable.
    }
    if (defined('SUBDOMAIN_INSTALL') && SUBDOMAIN_INSTALL) {
        return $token; // Not applicable.
    }
    if ($consider_domain_mapping && $self->canConsiderDomainMapping()) {
        return $token; // Not applicable.
    }
    if (defined('PATH_CURRENT_SITE')) {
        $token = (string) PATH_CURRENT_SITE;
    }
    $token = trim($token, '\\/'." \t\n\r\0\x0B");
    $token = isset($token[0]) ? '/'.$token.'/' : '/';

    if ($token !== '/' && $dashify) {
        $token = preg_replace('/[^a-z0-9\/]/i', '-', $token);
        $token = trim($token, '-');
    }
    return $token;
};

/*
 * Current blog's sub-directory.
 *
 * @since 150422 Rewrite.
 *
 * @param boolean $dashify Optional, defaults to a `FALSE` value.
 *    If `TRUE`, the token is returned with dashes in place of `[^a-z0-9\/]`.
 *
 * @param boolean $consider_domain_mapping Consider?
 *
 * @param string $path Defaults to the current URI path.
 *
 * @return string Current blog's sub-directory.
 *
 * @note The return value of this function is cached to reduce overhead on repeat calls.
 */
$self->hostDirToken = function ($dashify = false, $consider_domain_mapping = false, $path = null) use ($self) {
    if (!isset($path)) { // Use current/default path?
        $path = (string) $self->parseUrl($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    }
    $path = '/'.ltrim((string) $path, '/'); // Force leading slash.

    if (!is_null($token = &$self->staticKey('hostDirToken', array($dashify, $consider_domain_mapping, $path)))) {
        return $token; // Already cached this.
    }
    $token = '/'; // Assume NOT multisite; or own domain.

    if (!is_multisite()) {
        return $token; // Not applicable.
    }
    if (defined('SUBDOMAIN_INSTALL') && SUBDOMAIN_INSTALL) {
        return $token; // Not applicable.
    }
    if ($consider_domain_mapping && $self->canConsiderDomainMapping()) {
        return $token; // Not applicable.
    }
    if ($path && $path !== '/' && ($host_base_token = trim($self->hostBaseToken(), '/'))) {
        $path_minus_base = preg_replace('/^\/'.preg_quote($host_base_token, '/').'(\/|$)/i', '${1}', $path);
    } else {
        $path_minus_base = $path; // Default value.
    }
    list($token) = explode('/', trim($path_minus_base, '/'));
    $token       = trim($token, '\\/'." \t\n\r\0\x0B");
    $token       = isset($token[0]) ? '/'.$token.'/' : '/';

    if ($token !== '/') { // Perhaps NOT the main site?
        $blog_paths_file = $self->cacheDir().'/'.strtolower(SHORT_NAME).'-blog-paths';
        if (!is_file($blog_paths_file) || !in_array($token, unserialize(file_get_contents($blog_paths_file)), true)) {
            $token = '/'; // NOT a real/valid child blog path.
        }
    }
    if ($token !== '/' && $dashify) {
        $token = preg_replace('/[^a-z0-9\/]/i', '-', $token);
        $token = trim($token, '-');
    }
    return $token;
};

/*
 * A blog's sub-directory.
 *
 * @since 150821 Improving multisite compat.
 *
 * @param boolean $dashify Optional, defaults to a `FALSE` value.
 *    If `TRUE`, the token is returned with dashes in place of `[^a-z0-9]`.
 *
 * @param boolean $consider_domain_mapping Consider?
 *
 * @param integer $blog_id For which blog ID?
 *
 * @return string A blog's sub-directory.
 *
 * @note The return value of this function is NOT cached in support of `switch_to_blog()`.
 */
$self->hostDirTokenForBlog = function ($dashify = false, $consider_domain_mapping = false, $blog_id = 0) use ($self) {
    if (!is_multisite() || $self->isAdvancedCache()) {
        return $self->hostDirToken($dashify, $consider_domain_mapping);
    }
    $token = '/'; // Initialize token value.

    if (defined('SUBDOMAIN_INSTALL') && SUBDOMAIN_INSTALL) {
        return $token; // Not applicable.
    }
    if ($consider_domain_mapping && $self->canConsiderDomainMapping()) {
        return $token; // Not applicable.
    }
    if (($blog_details = $self->blogDetails($blog_id))) {
        $path = $blog_details->path; // e.g., `[/base]/path/` (includes base).
        if ($path && $path !== '/' && ($host_base_token = trim($self->hostBaseToken(), '/'))) {
            $path_minus_base = preg_replace('/^\/'.preg_quote($host_base_token, '/').'(\/|$)/i', '${1}', $path);
        } else {
            $path_minus_base = $path; // Default value.
        }
        list($token) = explode('/', trim($path_minus_base, '/'));
    }
    $token = trim($token, '\\/'." \t\n\r\0\x0B");
    $token = isset($token[0]) ? '/'.$token.'/' : '/';

    if ($token !== '/') { // Perhaps NOT the main site?
        $blog_paths_file = $self->cacheDir().'/'.strtolower(SHORT_NAME).'-blog-paths';
        if (!is_file($blog_paths_file) || !in_array($token, unserialize(file_get_contents($blog_paths_file)), true)) {
            $token = '/'; // NOT a real/valid child blog path.
        }
    }
    if ($token !== '/' && $dashify) {
        $token = preg_replace('/[^a-z0-9\/]/i', '-', $token);
        $token = trim($token, '-');
    }
    return $token;
};

/*
 * Current site's base directory & current blog's sub-directory.
 *
 * @since 150422 Rewrite.
 *
 * @param boolean $dashify Optional, defaults to a `FALSE` value.
 *    If `TRUE`, the tokens are returned with dashes in place of `[^a-z0-9\/]`.
 *
 * @param boolean $consider_domain_mapping Consider?
 *
 * @param string $path Defaults to the current URI path.
 *
 * @return string Current site's base directory & current blog's sub-directory.
 *
 * @note The return value of this function is cached to reduce overhead on repeat calls.
 */
$self->hostBaseDirTokens = function ($dashify = false, $consider_domain_mapping = false, $path = null) use ($self) {
    if (!is_null($tokens = &$self->staticKey('hostBaseDirTokens', array($dashify, $consider_domain_mapping, $path)))) {
        return $tokens; // Already cached this.
    }
    $tokens = $self->hostBaseToken($dashify, $consider_domain_mapping);
    $tokens .= $self->hostDirToken($dashify, $consider_domain_mapping, $path);

    return ($tokens = preg_replace('/\/+/', '/', $tokens));
};

/*
 * A site's base directory & a blog's sub-directory.
 *
 * @since 150821 Improving multisite compat.
 *
 * @param boolean $dashify Optional, defaults to a `FALSE` value.
 *    If `TRUE`, the tokens are returned with dashes in place of `[^a-z0-9\/]`.
 *
 * @param boolean $consider_domain_mapping Consider?
 *
 * @param integer $blog_id For which blog ID?
 *
 * @return string A site's base directory & a blog's sub-directory.
 *
 * @note The return value of this function is NOT cached in support of `switch_to_blog()`.
 */
$self->hostBaseDirTokensForBlog = function ($dashify = false, $consider_domain_mapping = false, $blog_id = 0) use ($self) {
    $tokens = $self->hostBaseToken($dashify, $consider_domain_mapping);
    $tokens .= $self->hostDirTokenForBlog($dashify, $consider_domain_mapping, $blog_id);

    return ($tokens = preg_replace('/\/+/', '/', $tokens));
};

/*[pro strip-from="lite"]*/
/*
 * Produces a token based on the current user.
 *
 * @since 150422 Rewrite.
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
    if (!is_null($token = &$self->staticKey('userToken'))) {
        return $token; // Already cached this.
    }
    if ($self->functionIsPossible('wp_validate_auth_cookie')) {
        if (($user_id = (integer) wp_validate_auth_cookie('', 'logged_in'))) {
            return ($token = (string) $user_id); // A real user in this case.
        } elseif (!empty($_COOKIE['wordpress_logged_in_'.COOKIEHASH])) {
            $self->user_login_cookie_expired_or_invalid = true;
        }
    }
    if (!empty($_COOKIE['comment_author_email_'.COOKIEHASH])) {
        return ($token = md5(strtolower(stripslashes((string) $_COOKIE['comment_author_email_'.COOKIEHASH]))));
    } elseif (!empty($_COOKIE['wp-postpass_'.COOKIEHASH])) {
        return ($token = md5(stripslashes((string) $_COOKIE['wp-postpass_'.COOKIEHASH])));
    } elseif (defined('SID') && SID) {
        return ($token = preg_replace('/[^a-z0-9]/i', '', (string) SID));
    }
    return ($token = '');
};
/*[/pro]*/
