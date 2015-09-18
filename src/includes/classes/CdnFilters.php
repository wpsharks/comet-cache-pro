<?php
/*[pro strip-from="lite"]*/
namespace WebSharks\ZenCache\Pro;

/**
 * CDN Filters.
 *
 * @since 150422 Rewrite.
 */
class CdnFilters extends AbsBase
{
    /**
     * @since 150422 Rewrite.
     *
     * @type string Local host name.
     */
    protected $local_host;

    /**
     * @since 150422 Rewrite.
     *
     * @type bool Enable CDN filters?
     */
    protected $cdn_enable;

    /**
     * @since 150409 Improving CDN support.
     *
     * @type bool Enable CDN filters in HTML Compressor?
     */
    protected $htmlc_enable;

    /**
     * @since 150422 Rewrite.
     *
     * @type string CDN serves files from this host.
     */
    protected $cdn_host;

    /**
     * @since 150626 Improving CDN host parsing.
     *
     * @type array An array of all CDN host mappings.
     */
    protected $cdn_hosts;

    /**
     * @since 150422 Rewrite.
     *
     * @type bool CDN over SSL connections?
     */
    protected $cdn_over_ssl;

    /**
     * @since 150422 Rewrite.
     *
     * @type string Invalidation variable name.
     */
    protected $cdn_invalidation_var;

    /**
     * @since 150422 Rewrite.
     *
     * @type int Invalidation counter.
     */
    protected $cdn_invalidation_counter;

    /**
     * @since 150422 Rewrite.
     *
     * @type array Array of whitelisted extensions.
     */
    protected $cdn_whitelisted_extensions;

    /**
     * @since 150422 Rewrite.
     *
     * @type array Array of blacklisted extensions.
     */
    protected $cdn_blacklisted_extensions;

    /**
     * @since 150422 Rewrite.
     *
     * @type string|null CDN whitelisted URI patterns.
     */
    protected $cdn_whitelisted_uri_patterns;

    /**
     * @since 150422 Rewrite.
     *
     * @type string|null CDN blacklisted URI patterns.
     */
    protected $cdn_blacklisted_uri_patterns;

    /**
     * @since 150626 Improving CDN host parsing.
     *
     * @type bool Did the `wp_head` action hook yet?
     *
     * @note This is only public for PHP v5.3 compat.
     */
    public $completed_wp_head_action_hook = false;

    /**
     * @since 150626 Improving CDN host parsing.
     *
     * @type bool Did the `wp_footer` action hook yet?
     *
     * @note This is only public for PHP v5.3 compat.
     */
    public $started_wp_footer_action_hook = false;

    /**
     * Class constructor.
     *
     * @since 150422 Rewrite.
     */
    public function __construct()
    {
        parent::__construct();

        // Primary switch; CDN filters enabled?

        $this->cdn_enable = (boolean) $this->plugin->options['cdn_enable'];

        // Another switch; HTML Compressor enabled?

        $this->htmlc_enable = (boolean) $this->plugin->options['htmlc_enable'];

        // Host-related properties.

        $this->local_host = strtolower($this->plugin->hostToken());
        $this->cdn_host   = strtolower($this->plugin->options['cdn_host']);
        $this->cdn_hosts  = strtolower($this->plugin->options['cdn_hosts']);
        $this->parseCdnHosts(); // Convert CDN hosts to an array.

        // Configure invalidation-related properties.

        $this->cdn_invalidation_var     = (string) $this->plugin->options['cdn_invalidation_var'];
        $this->cdn_invalidation_counter = (integer) $this->plugin->options['cdn_invalidation_counter'];

        // CDN supports SSL connections?

        $this->cdn_over_ssl = (boolean) $this->plugin->options['cdn_over_ssl'];

        // Whitelisted extensions; MUST have these at all times.

        if (!($cdn_whitelisted_extensions = trim($this->plugin->options['cdn_whitelisted_extensions']))) {
            $cdn_whitelisted_extensions = implode('|', static::defaultWhitelistedExtensions());
        }
        $this->cdn_whitelisted_extensions = trim(strtolower($cdn_whitelisted_extensions), "\r\n\t\0\x0B".' |;,');
        $this->cdn_whitelisted_extensions = preg_split('/[|;,\s]+/', $this->cdn_whitelisted_extensions, null, PREG_SPLIT_NO_EMPTY);
        $this->cdn_whitelisted_extensions = array_unique($this->cdn_whitelisted_extensions);

        // Blacklisted extensions; if applicable.

        $cdn_blacklisted_extensions = $this->plugin->options['cdn_blacklisted_extensions'];

        $this->cdn_blacklisted_extensions   = trim(strtolower($cdn_blacklisted_extensions), "\r\n\t\0\x0B".' |;,');
        $this->cdn_blacklisted_extensions   = preg_split('/[|;,\s]+/', $this->cdn_blacklisted_extensions, null, PREG_SPLIT_NO_EMPTY);
        $this->cdn_blacklisted_extensions[] = 'php'; // Always exclude.

        $this->cdn_blacklisted_extensions = array_unique($this->cdn_blacklisted_extensions);

        // Whitelisted URI patterns; if applicable.

        $cdn_whitelisted_uri_patterns = trim(strtolower($this->plugin->options['cdn_whitelisted_uri_patterns']));
        $cdn_whitelisted_uri_patterns = preg_split('/['."\r\n".']+/', $cdn_whitelisted_uri_patterns, null, PREG_SPLIT_NO_EMPTY);
        $cdn_whitelisted_uri_patterns = array_unique($cdn_whitelisted_uri_patterns);

        if ($cdn_whitelisted_uri_patterns) {
            $this->cdn_whitelisted_uri_patterns = '/(?:'.implode('|', array_map(function ($pattern) {
                return preg_replace(array('/\\\\\*/', '/\\\\\^/'), array('.*?', '[^\/]*?'), preg_quote('/'.ltrim($pattern, '/'), '/'));
            }, $cdn_whitelisted_uri_patterns)).')/i'; // CaSe inSensitive.
        }
        // Blacklisted URI patterns; if applicable.

        $cdn_blacklisted_uri_patterns   = trim(strtolower($this->plugin->options['cdn_blacklisted_uri_patterns']));
        $cdn_blacklisted_uri_patterns   = preg_split('/['."\r\n".']+/', $cdn_blacklisted_uri_patterns, null, PREG_SPLIT_NO_EMPTY);
        $cdn_blacklisted_uri_patterns[] = '*/wp-admin/*'; // Always.

        if (is_multisite()) {
            $cdn_blacklisted_uri_patterns[] = '/^/files/*';
        }
        if (defined('WS_PLUGIN__S2MEMBER_VERSION')) {
            $cdn_blacklisted_uri_patterns[] = '*/s2member-files/*';
        }
        $cdn_blacklisted_uri_patterns = array_unique($cdn_blacklisted_uri_patterns);

        if ($cdn_blacklisted_uri_patterns) {
            $this->cdn_blacklisted_uri_patterns = '/(?:'.implode('|', array_map(function ($pattern) {
                return preg_replace(array('/\\\\\*/', '/\\\\\^/'), array('.*?', '[^\/]*?'), preg_quote('/'.ltrim($pattern, '/'), '/'));
            }, $cdn_blacklisted_uri_patterns)).')/i'; // CaSe inSensitive.
        }
        // Maybe attach filters.

        $this->maybeSetupFilters();
    }

    /**
     * Setup URL and content filters.
     *
     * @since 150422 Rewrite.
     */
    protected function maybeSetupFilters()
    {
        if (is_admin()) {
            return; // Not applicable.
        }
        if (!$this->cdn_enable) {
            return; // Disabled currently.
        }
        if (!$this->local_host) {
            return; // Not possible.
        }
        if (!$this->cdn_hosts) {
            return; // Not possible.
        }
        if (!$this->cdn_over_ssl && is_ssl()) {
            return; // Disable in this case.
        }
        $_this = $this; // Needed for closures below.

        add_action('wp_head', function () use ($_this) {
            $_this->completed_wp_head_action_hook = true;
        }, PHP_INT_MAX); // The very last hook, ideally.

        add_action('wp_footer', function () use ($_this) {
            $_this->started_wp_footer_action_hook = true;
        }, -PHP_INT_MAX); // The very first hook, ideally.

        add_filter('home_url', array($this, 'urlFilter'), PHP_INT_MAX - 10, 4);
        add_filter('site_url', array($this, 'urlFilter'), PHP_INT_MAX - 10, 4);

        add_filter('network_home_url', array($this, 'urlFilter'), PHP_INT_MAX - 10, 3);
        add_filter('network_site_url', array($this, 'urlFilter'), PHP_INT_MAX - 10, 3);

        add_filter('content_url', array($this, 'urlFilter'), PHP_INT_MAX - 10, 2);
        add_filter('plugins_url', array($this, 'urlFilter'), PHP_INT_MAX - 10, 2);

        add_filter('wp_get_attachment_url', array($this, 'urlFilter'), PHP_INT_MAX - 10, 1);

        add_filter('script_loader_src', array($this, 'urlFilter'), PHP_INT_MAX - 10, 1);
        add_filter('style_loader_src', array($this, 'urlFilter'), PHP_INT_MAX - 10, 1);

        add_filter('the_content', array($this, 'contentFilter'), PHP_INT_MAX - 10, 1);
        add_filter('get_the_excerpt', array($this, 'contentFilter'), PHP_INT_MAX - 10, 1);
        add_filter('widget_text', array($this, 'contentFilter'), PHP_INT_MAX - 10, 1);

        if ($this->htmlc_enable) {
            // If the HTML Compressor is enabled, attach early hook. Runs later.
            if (empty($GLOBALS['WebSharks\\HtmlCompressor_early_hooks']) || !is_array($GLOBALS['WebSharks\\HtmlCompressor_early_hooks'])) {
                $GLOBALS['WebSharks\\HtmlCompressor_early_hooks'] = array(); // Initialize.
            }
            $GLOBALS['WebSharks\\HtmlCompressor_early_hooks'][] = array(
                'hook'          => 'css_url()', // Filters CSS `url()`s.
                'function'      => array($this, 'htmlCUrlFilter'),
                'priority'      => PHP_INT_MAX - 10,
                'accepted_args' => 1,
            );
            $GLOBALS['WebSharks\\HtmlCompressor_early_hooks'][] = array(
                'hook'          => 'part_url', // Filters JS/CSS parts.
                'function'      => array($this, 'htmlCUrlFilter'),
                'priority'      => PHP_INT_MAX - 10,
                'accepted_args' => 2,
            );
        }
    }

    /**
     * Filter home/site URLs that should be served by the CDN.
     *
     * @since 150422 Rewrite.
     *
     * @param string      $url     Input URL|URI|query; passed by filter.
     * @param string      $path    The path component(s) passed through by the filter.
     * @param string|null $scheme  `NULL`, `http`, `https`, `login`, `login_post`, `admin`, or `relative`.
     * @param int|null    $blog_id Blog ID; passed only by non-`network_` filter variations.
     *
     * @return string The URL after having been filtered.
     */
    public function urlFilter($url, $path = '', $scheme = null, $blog_id = null)
    {
        return $this->filterUrl($url, $scheme, false, null);
    }

    /**
     * Filter URLs that should be served by the CDN.
     *
     * @since 150626 Improving CDN host parsing.
     *
     * @param string $url Input URL|URI|query; passed by filter.
     * @param string $for One of `head`, `body`, `foot`. Defaults to `body`.
     *
     * @return string The URL after having been filtered.
     */
    public function htmlCUrlFilter($url, $for = 'body')
    {
        return $this->filterUrl($url, null, false, $for);
    }

    /**
     * Filter content for URLs that should be served by the CDN.
     *
     * @since 150422 Rewrite.
     *
     * @param string $string Input content string to filter; i.e. HTML code.
     *
     * @return string The content string after having been filtered.
     */
    public function contentFilter($string)
    {
        if (!($string = (string) $string)) {
            return $string; // Nothing to do.
        }
        if (strpos($string, '<') === false) {
            return $string; // Nothing to do.
        }
        $_this = $this; // Reference needed by closures below.

        $regex_url_attrs = '/'.// HTML attributes containing a URL.

                           '(\<)'.// Open tag; group #1.
                           '([\w\-]+)'.// Tag name; group #2.

                           '([^>]*?)'.// Others before; group #3.

                           '(\s(?:href|src)\s*\=\s*)'.// ` attribute=`; group #4.
                           '(["\'])'.// Open double or single; group #5.
                           '([^"\'>]+?)'.// Possible URL; group #6.
                           '(\\5)'.// Close quote; group #7.

                           '([^>]*?)'.// Others after; group #8.

                           '(\>)'.// Tag close; group #9.

                           '/i'; // End regex pattern; case insensitive.

        $orig_string = $string; // In case of regex errors.
        $string      = preg_replace_callback($regex_url_attrs, function ($m) use ($_this) {
            unset($m[0]); // Discard full match.
            $m[6] = $_this->filterUrl($m[6], null, true, null);
            return implode('', $m); // Concatenate all parts.
        }, $string); // End content filter.

        return $string ? $string : $orig_string;
    }

    /**
     * Filter URLs that should be served by the CDN.
     *
     * @since 150422 Rewrite.
     *
     * @param string      $url_uri_qsl Input URL, URI, or query string w/ a leading `?`.
     * @param string|null $scheme      `NULL`, `http`, `https`, `login`, `login_post`, `admin`, or `relative`.
     * @param bool        $esc         Defaults to a FALSE value; do not deal with HTML entities.
     * @param string|null $for         One of `null`, `head`, `body`, `foot`.
     *
     * @return string The URL after having been filtered.
     *
     * @note This is only public for PHP v5.3 compat.
     */
    public function filterUrl($url_uri_qsl, $scheme = null, $esc = false, $for = null)
    {
        if (!($url_uri_qsl = trim((string) $url_uri_qsl))) {
            return; // Unparseable.
        }
        $orig_url_uri_qsl = $url_uri_qsl; // Needed below.

        if ($esc) { // If escaping, unescape the input value also.
            $url_uri_qsl = wp_specialchars_decode($url_uri_qsl, ENT_QUOTES);
        }
        if (!($local_file = $this->localFile($url_uri_qsl))) {
            return $orig_url_uri_qsl; // Not a local file.
        }
        if (empty($this->cdn_hosts[$local_file->host])) {
            return $orig_url_uri_qsl; // Exclude; no host mapping.
        }
        if (!in_array($local_file->extension, $this->cdn_whitelisted_extensions, true)) {
            return $orig_url_uri_qsl; // Not a whitelisted extension.
        }
        if ($this->cdn_blacklisted_extensions && in_array($local_file->extension, $this->cdn_blacklisted_extensions, true)) {
            return $orig_url_uri_qsl; // Exclude; it's a blacklisted extension.
        }
        if ($this->cdn_whitelisted_uri_patterns && !preg_match($this->cdn_whitelisted_uri_patterns, $local_file->uri)) {
            return $orig_url_uri_qsl; // Exclude; not a whitelisted URI pattern.
        }
        if ($this->cdn_blacklisted_uri_patterns && preg_match($this->cdn_blacklisted_uri_patterns, $local_file->uri)) {
            return $orig_url_uri_qsl; // Exclude; it's a blacklisted URI pattern.
        }
        if (!isset($scheme) && isset($local_file->scheme) && $local_file->scheme !== '//') {
            $scheme = $local_file->scheme; // Use original scheme.
        }
        $cdn_host = $this->cdn_hosts[$local_file->host][0];

        if (!isset($for)) {
            if (!$this->completed_wp_head_action_hook) {
                $for = 'head'; // This will go into the <head>.
            } elseif ($this->started_wp_footer_action_hook) {
                $for = 'foot'; // This goes in the footer.
            }
        }
        if ($for === 'head') {
            $cdn_host = $this->cdn_hosts[$local_file->host][0];
        } elseif ($for === 'foot') {
            $cdn_host = end($this->cdn_hosts[$local_file->host]);
        } elseif (($total_cdn_hosts = count($this->cdn_hosts[$local_file->host])) > 1) {
            $cdn_host = $this->cdn_hosts[$local_file->host][mt_rand(0, $total_cdn_hosts - 1)];
        }
        $url = set_url_scheme('//'.$cdn_host.$local_file->uri, $scheme);

        if ($this->cdn_invalidation_var && $this->cdn_invalidation_counter) {
            $url = add_query_arg($this->cdn_invalidation_var, $this->cdn_invalidation_counter, $url);
        }
        return $esc ? esc_attr($url) : $url;
    }

    /**
     * Parse a URL|URI|query into a local file array.
     *
     * @since 150422 Rewrite.
     *
     * @param string $url_uri_qsl Input URL, URI, or query string w/ a leading `?`.
     *
     * @return object|null An object with: `scheme`, `host`, `uri`, `extension` properties.
     *                     This returns NULL for any URL that is not local, or does not lead to a file.
     *                     Local, meaning that we have a CDN host mapping for the associated host/domain name.
     */
    protected function localFile($url_uri_qsl)
    {
        if (!($url_uri_qsl = trim((string) $url_uri_qsl))) {
            return; // Unparseable.
        }
        if (!($parsed = @$this->plugin->parseUrl($url_uri_qsl))) {
            return; // Unparseable.
        }
        if (empty($parsed['host']) && empty($this->cdn_hosts[$this->local_host])) {
            return; // Not on this host name.
        }
        if (!empty($parsed['host']) && empty($this->cdn_hosts[strtolower($parsed['host'])])) {
            return; // Not on this host name.
        }
        if (!isset($parsed['path'][0]) || $parsed['path'][0] !== '/') {
            return; // Missing or unexpected path.
        }
        if (substr($parsed['path'], -1) === '/') {
            return; // Directory, not a file.
        }
        if (strpos($parsed['path'], '..') !== false || strpos($parsed['path'], './') !== false) {
            return; // A relative path that is not absolute.
        }
        $scheme = null; // Default scheme handling.
        $host   = $this->local_host; // Default host name.
        $uri    = $parsed['path']; // Put URI together.

        if (!empty($parsed['scheme'])) {
            $scheme = strtolower($parsed['scheme']);
        }
        if (!empty($parsed['host'])) {
            $host = strtolower($parsed['host']);
        }
        if (!empty($parsed['query'])) {
            $uri .= '?'.$parsed['query'];
        }
        if (!empty($parsed['fragment'])) {
            $uri .= '#'.$parsed['fragment'];
        }
        if (!($extension = $this->extension($parsed['path']))) {
            return; // No extension; i.e. not a file.
        }
        return (object) compact('scheme', 'host', 'uri', 'extension');
    }

    /**
     * Get extension from a file path.
     *
     * @since 150422 Rewrite.
     *
     * @param string $path Input file path.
     *
     * @return string File extension (lowercase), else an empty string.
     */
    protected function extension($path)
    {
        if (!($path = trim((string) $path))) {
            return ''; // No path.
        }
        return strtolower(ltrim((string) strrchr(basename($path), '.'), '.'));
    }

    /**
     * Parses a line-delimited list of CDN host mappings.
     *
     * @since 150626 Improving CDN host parsing.
     */
    protected function parseCdnHosts()
    {
        $lines           = (string) $this->cdn_hosts;
        $this->cdn_hosts = array(); // Initialize.

        $lines = str_replace(array("\r\n", "\r"), "\n", $lines);
        $lines = trim(strtolower($lines)); // Force all mappings to lowercase.
        $lines = preg_split('/['."\r\n".']+/', $lines, null, PREG_SPLIT_NO_EMPTY);

        foreach ($lines as $_line) {
            if (!($_line = trim($_line))) {
                continue; // Invalid line.
            }
            if (strpos($_line, '=') !== false) {
                $_parts = explode('=', $_line, 2);
            } else {
                $_parts = array($this->local_host, $_line);
            }
            $_parts = $this->plugin->trimDeep($_parts);

            if (empty($_parts[0]) || empty($_parts[1])) {
                continue; // Invalid line.
            }
            list($_domain, $_cdn_hosts) = $_parts; // e.g., `domain = cdn, cdn, cdn`.
            foreach (preg_split('/,+/', $_cdn_hosts, null, PREG_SPLIT_NO_EMPTY) as $_cdn_host) {
                if (($_cdn_host = trim($_cdn_host))) {
                    $this->cdn_hosts[$_domain][] = $_cdn_host;
                }
            }
        }
        unset($_line, $_parts, $_domain, $_cdn_hosts, $_cdn_host); // Housekeeping.

        $this->cdn_hosts = array_map('array_unique', $this->cdn_hosts);

        if (empty($this->cdn_hosts[$this->local_host])) {
            if ($this->cdn_host && (!is_multisite() || is_main_site())) {
                $this->cdn_hosts[strtolower((string) $this->plugin->parseUrl(network_home_url(), PHP_URL_HOST))][] = $this->cdn_host;
            }
        }
    }

    /**
     * Default whitelisted extensions.
     *
     * @since 150314 Auto-excluding font file extensions.
     *
     * @return array Default whitelisted extensions.
     */
    public static function defaultWhitelistedExtensions()
    {
        $extensions = array_keys(wp_get_mime_types());
        $extensions = array_map('strtolower', $extensions);
        $extensions = array_merge($extensions, array('eot', 'ttf', 'otf', 'woff'));

        if (($permalink_structure = get_option('permalink_structure'))) {
            if (strcasecmp(substr($permalink_structure, -5), '.html') === 0) {
                $extensions = array_diff($extensions, array('html'));
            } elseif (strcasecmp(substr($permalink_structure, -4), '.htm') === 0) {
                $extensions = array_diff($extensions, array('htm'));
            }
        }
        return array_unique($extensions);
    }
}
/*[/pro]*/
