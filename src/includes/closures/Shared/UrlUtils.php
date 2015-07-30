<?php
namespace WebSharks\ZenCache\Pro;

/*
 * Parses a URL.
 *
 * @since 15xxxx Improving multisite compat.
 *
 * @param string $url_uri_qsl Input URL, URI, or query string w/ a leading `?`.
 * @param int    $component   Optional component to retrieve.
 *
 * @return array|string|int|null Array, else `string|int|null` component value.
 */
$self->parseUrl = function ($url_uri_qsl, $component = -1) use ($self) {
    $url_uri_qsl = (string) $url_uri_qsl;
    $component   = (integer) $component;
    ${'//'}      = strpos($url_uri_qsl, '//') === 0;

    if ($url_uri_qsl && strpos($url_uri_qsl, '&amp;') !== false) {
        $url_uri_qsl = str_replace('&amp;', '&', $url_uri_qsl);
    }
    if ($component > -1) {
        if (${'//'} && $component === PHP_URL_SCHEME) {
            return ($part = '//');
        }
        return ($part = parse_url($url_uri_qsl, $component));
    } else {
        if (!is_array($parts = parse_url($url_uri_qsl))) {
            return ($parts = array());
        }
        if (${'//'}) {
            $parts['scheme'] = '//';
        }
        return $parts;
    }
};

/*
 * Unparses a URL.
 *
 * @since 15xxxx Improving multisite compat.
 *
 * @param array $parts Input URL parts.
 *
 * @return string Unparsed URL in string format.
 */
$self->unParseUrl = function (array $parts) use ($self) {
    $scheme   = '';
    $host     = '';
    $port     = '';
    $user     = '';
    $pass     = '';
    $path     = '';
    $query    = '';
    $fragment = '';

    if (!empty($parts['scheme'])) {
        if ($parts['scheme'] === '//') {
            $scheme = $parts['scheme'];
        } else {
            $scheme = $parts['scheme'].'://';
        }
    }
    if (!empty($parts['host'])) {
        $host = $parts['host'];
    }
    if (!empty($parts['port'])) {
        $port = ':'.$parts['port'];
    }
    if (!empty($parts['user'])) {
        $user = $parts['user'];
    }
    if (!empty($parts['pass'])) {
        $pass = $parts['pass'];
    }
    if ($user || $pass) {
        $pass .= '@';
    }
    if (!empty($parts['path'])) {
        $path = '/'.ltrim($parts['path'], '/');
    }
    if (!empty($parts['query'])) {
        $query = '?'.$parts['query'];
    }
    if (!empty($parts['fragment'])) {
        $fragment = '#'.$parts['fragment'];
    }
    return $scheme.$user.$pass.$host.$port.$path.$query.$fragment;
};
