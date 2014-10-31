<?php
/**
 * CDN Filters
 *
 * @package quick_cache\cdn
 * @since 14xxxx Adding CDN support.
 * @copyright WebSharks, Inc. <http://www.websharks-inc.com>
 * @license GNU General Public License, version 2
 */
namespace quick_cache // Root namespace.
{
	if(!defined('WPINC')) // MUST have WordPress.
		exit('Do NOT access this file directly: '.basename(__FILE__));

	/**
	 * CDN Filters
	 *
	 * @since 14xxxx Adding CDN support.
	 */
	class cdn_filters
	{
		/**
		 * @since 14xxxx Adding CDN support.
		 * @var plugin Quick Cache instance.
		 */
		protected $plugin;

		/**
		 * @since 14xxxx Adding CDN support.
		 * @var string Local host name.
		 */
		protected $local_host;

		/**
		 * @since 14xxxx Adding CDN support.
		 * @var boolean Enable CDN filters?
		 */
		protected $cdn_enable;

		/**
		 * @since 14xxxx Adding CDN support.
		 * @var string CDN serves files from this host.
		 */
		protected $cdn_host;

		/**
		 * @since 14xxxx Adding CDN support.
		 * @var boolean CDN over SSL connections?
		 */
		protected $cdn_over_ssl;

		/**
		 * @since 14xxxx Adding CDN support.
		 * @var string Invalidation variable name.
		 */
		protected $cdn_invalidation_var;

		/**
		 * @since 14xxxx Adding CDN support.
		 * @var integer Invalidation counter.
		 */
		protected $cdn_invalidation_counter;

		/**
		 * @since 14xxxx Adding CDN support.
		 * @var array Array of whitelisted extensions.
		 */
		protected $cdn_whitelisted_extensions;

		/**
		 * @since 14xxxx Adding CDN support.
		 * @var array Array of blacklisted extensions.
		 */
		protected $cdn_blacklisted_extensions;

		/**
		 * @since 14xxxx Adding CDN support.
		 * @var string|null CDN whitelisted URI patterns.
		 */
		protected $cdn_whitelisted_uri_patterns;

		/**
		 * @since 14xxxx Adding CDN support.
		 * @var string|null CDN blacklisted URI patterns.
		 */
		protected $cdn_blacklisted_uri_patterns;

		/**
		 * Class constructor.
		 *
		 * @since 14xxxx Adding CDN support.
		 */
		public function __construct()
		{
			$this->plugin = plugin(); // Plugin class.

			/* Primary switch; enabled? */

			$this->cdn_enable = (boolean)$this->plugin->options['cdn_enable'];

			/* Host-related properties. */

			$this->local_host = strtolower((string)parse_url(network_home_url(), PHP_URL_HOST));
			$this->cdn_host   = strtolower($this->plugin->options['cdn_host']);

			/* Configure invalidation-related properties. */

			$this->cdn_invalidation_var     = (string)$this->plugin->options['cdn_invalidation_var'];
			$this->cdn_invalidation_counter = (integer)$this->plugin->options['cdn_invalidation_counter'];

			/* CDN supports SSL connections? */

			$this->cdn_over_ssl = (boolean)$this->plugin->options['cdn_over_ssl'];

			/* Whitelisted extensions; MUST have these at all times. */

			if(!($cdn_whitelisted_extensions = trim($this->plugin->options['cdn_whitelisted_extensions'])))
				$cdn_whitelisted_extensions = implode('|', array_keys(wp_get_mime_types()));

			$this->cdn_whitelisted_extensions = trim(strtolower($cdn_whitelisted_extensions), "\r\n\t\0\x0B".' |;,');
			$this->cdn_whitelisted_extensions = preg_split('/[|;,\s]+/', $this->cdn_whitelisted_extensions, NULL, PREG_SPLIT_NO_EMPTY);
			$this->cdn_whitelisted_extensions = array_unique($this->cdn_whitelisted_extensions);

			/* Blacklisted extensions; if applicable. */

			$cdn_blacklisted_extensions = $this->plugin->options['cdn_blacklisted_extensions'];

			$this->cdn_blacklisted_extensions = trim(strtolower($cdn_blacklisted_extensions), "\r\n\t\0\x0B".' |;,');
			$this->cdn_blacklisted_extensions = preg_split('/[|;,\s]+/', $this->cdn_blacklisted_extensions, NULL, PREG_SPLIT_NO_EMPTY);

			$this->cdn_blacklisted_extensions[] = 'php'; // Always exclude.

			$this->cdn_blacklisted_extensions = array_unique($this->cdn_blacklisted_extensions);

			/* Whitelisted URI patterns; if applicable. */

			$cdn_whitelisted_uri_patterns = trim(strtolower($this->plugin->options['cdn_whitelisted_uri_patterns']));
			$cdn_whitelisted_uri_patterns = preg_split('/['."\r\n".']+/', $cdn_whitelisted_uri_patterns, NULL, PREG_SPLIT_NO_EMPTY);
			$cdn_whitelisted_uri_patterns = array_unique($cdn_whitelisted_uri_patterns);

			if($cdn_whitelisted_uri_patterns) $this->cdn_whitelisted_uri_patterns = '/(?:'.implode('|', array_map(function ($pattern)
				{
					return preg_replace(array('/\\\\\*/', '/\\\\\^/'), array('.*?', '[^\/]*?'), preg_quote('/'.ltrim($pattern, '/'), '/')); #

				}, $cdn_whitelisted_uri_patterns)).')/i'; // CaSe inSensitive.

			/* Blacklisted URI patterns; if applicable. */

			$cdn_blacklisted_uri_patterns = trim(strtolower($this->plugin->options['cdn_blacklisted_uri_patterns']));
			$cdn_blacklisted_uri_patterns = preg_split('/['."\r\n".']+/', $cdn_blacklisted_uri_patterns, NULL, PREG_SPLIT_NO_EMPTY);

			$cdn_blacklisted_uri_patterns[] = '*/wp-admin/*'; // Always.

			if(is_multisite()) // Auto-exclude multisite rewrites.
				$cdn_blacklisted_uri_patterns[] = '/^/files/*'; // Uses rewrite.

			if(defined('WS_PLUGIN__S2MEMBER_VERSION')) // Auto-exclude s2Member rewrites.
				$cdn_blacklisted_uri_patterns[] = '*/s2member-files/*';

			$cdn_blacklisted_uri_patterns = array_unique($cdn_blacklisted_uri_patterns);

			if($cdn_blacklisted_uri_patterns) $this->cdn_blacklisted_uri_patterns = '/(?:'.implode('|', array_map(function ($pattern)
				{
					return preg_replace(array('/\\\\\*/', '/\\\\\^/'), array('.*?', '[^\/]*?'), preg_quote('/'.ltrim($pattern, '/'), '/')); #

				}, $cdn_blacklisted_uri_patterns)).')/i'; // CaSe inSensitive.

			/* Maybe attach filters. */

			$this->maybe_setup_filters();
		}

		/**
		 * Setup URL and content filters.
		 *
		 * @since 14xxxx Adding CDN support.
		 */
		public function maybe_setup_filters()
		{
			if(is_admin()) // Not front-end?
				return; // Not applicable.

			if(!$this->cdn_enable)
				return; // Disabled currently.

			if(!$this->local_host)
				return; // Not possible.

			if(!$this->cdn_host)
				return; // Not possible.

			if(!$this->cdn_over_ssl && is_ssl())
				return; // Disable in this case.

			if(is_multisite() && (defined('SUBDOMAIN_INSTALL') && SUBDOMAIN_INSTALL))
				/*
				 * @TODO this is something we need to look at in the future.
				 *
				 * We expect a single local host name at present.
				 *    However, it MIGHT be feasible to allow for wildcarded host names
				 *    in order to support sub-domain installs in the future.
				 *
				 * ~ Domain mapping will be another thing to look at.
				 *    I don't see an easy way to support domain mapping plugins.
				 */
				return; // Not possible; requires a sub-directory install (for now).

			add_filter('home_url', array($this, 'url_filter'), PHP_INT_MAX - 10, 4);
			add_filter('site_url', array($this, 'url_filter'), PHP_INT_MAX - 10, 4);

			add_filter('network_home_url', array($this, 'url_filter'), PHP_INT_MAX - 10, 3);
			add_filter('network_site_url', array($this, 'url_filter'), PHP_INT_MAX - 10, 3);

			add_filter('content_url', array($this, 'url_filter'), PHP_INT_MAX - 10, 2);
			add_filter('plugins_url', array($this, 'url_filter'), PHP_INT_MAX - 10, 2);

			add_filter('wp_get_attachment_url', array($this, 'url_filter'), PHP_INT_MAX - 10, 1);

			add_filter('script_loader_src', array($this, 'url_filter'), PHP_INT_MAX - 10, 1);
			add_filter('style_loader_src', array($this, 'url_filter'), PHP_INT_MAX - 10, 1);

			add_filter('the_content', array($this, 'content_filter'), PHP_INT_MAX - 10, 1);
			add_filter('get_the_excerpt', array($this, 'content_filter'), PHP_INT_MAX - 10, 1);
		}

		/**
		 * Filter home/site URLs that should be served by the CDN.
		 *
		 * @since 14xxxx Adding CDN support.
		 *
		 * @param string       $url Input URL|URI|query; passed by filter.
		 * @param string       $path The path component(s) passed through by the filter.
		 * @param string|null  $scheme `NULL`, `http`, `https`, `login`, `login_post`, `admin`, or `relative`.
		 * @param integer|null $blog_id Blog ID; passed only by non-`network_` filter variations.
		 *
		 * @return string The URL after having been filtered.
		 */
		public function url_filter($url, $path = '', $scheme = NULL, $blog_id = NULL)
		{
			return $this->filter_url($url, $scheme);
		}

		/**
		 * Filter content for URLs that should be served by the CDN.
		 *
		 * @since 14xxxx Adding CDN support.
		 *
		 * @param string $string Input content string to filter; i.e. HTML code.
		 *
		 * @return string The content string after having been filtered.
		 */
		public function content_filter($string)
		{
			if(!($string = (string)$string))
				return $string; // Nothing to do.

			if(strpos($string, '<') === FALSE)
				return $string; // Nothing to do.

			$_this = $this; // Reference needed by closures below.

			$regex_url_attrs = '/'. // HTML attributes containing a URL.

			                   '(\<)'. // Open tag; group #1.
			                   '([\w\-]+)'. // Tag name; group #2.

			                   '([^>]*?)'. // Others before; group #3.

			                   '(\s(?:href|src)\s*\=\s*)'. // ` attribute=`; group #4.
			                   '(["\'])'. // Open double or single; group #5.
			                   '([^"\'>]+?)'. // Possible URL; group #6.
			                   '(\\5)'. // Close quote; group #7.

			                   '([^>]*?)'. // Others after; group #8.

			                   '(\>)'. // Tag close; group #9.

			                   '/i'; // End regex pattern; case insensitive.

			$orig_string = $string; // In case of regex errors.
			$string      = preg_replace_callback($regex_url_attrs, function ($m) use ($_this)
			{
				unset($m[0]); // Discard full match.
				$m[6] = $_this->filter_url($m[6], NULL, TRUE);
				return implode('', $m); // Concatenate all parts.

			}, $string); // End content filter.

			return $string ? $string : $orig_string;
		}

		/**
		 * Filter URLs that should be served by the CDN.
		 *
		 * @since 14xxxx Adding CDN support.
		 *
		 * @param string      $url_uri_query Input URL|URI|query.
		 * @param string|null $scheme `NULL`, `http`, `https`, `login`, `login_post`, `admin`, or `relative`.
		 * @param boolean     $esc Defaults to a FALSE value; do not deal with HTML entities.
		 *
		 * @return string The URL after having been filtered.
		 */
		protected function filter_url($url_uri_query, $scheme = NULL, $esc = FALSE)
		{
			if(!($url_uri_query = trim((string)$url_uri_query)))
				return NULL; // Unparseable.

			$orig_url_uri_query = $url_uri_query; // Original value.
			if($esc) $url_uri_query = wp_specialchars_decode($url_uri_query, ENT_QUOTES);

			if(!($local_file = $this->local_file($url_uri_query)))
				return $orig_url_uri_query; // Not a local file.

			if(!in_array($local_file->extension, $this->cdn_whitelisted_extensions, TRUE))
				return $orig_url_uri_query; // Not a whitelisted extension.

			if($this->cdn_blacklisted_extensions && in_array($local_file->extension, $this->cdn_blacklisted_extensions, TRUE))
				return $orig_url_uri_query; // Exclude; it's a blacklisted extension.

			if($this->cdn_whitelisted_uri_patterns && !preg_match($this->cdn_whitelisted_uri_patterns, $local_file->uri))
				return $orig_url_uri_query; // Exclude; not a whitelisted URI pattern.

			if($this->cdn_blacklisted_uri_patterns && preg_match($this->cdn_blacklisted_uri_patterns, $local_file->uri))
				return $orig_url_uri_query; // Exclude; it's a blacklisted URI pattern.

			if(!isset($scheme) && isset($local_file->scheme))
				$scheme = $local_file->scheme; // Use original scheme.

			$url = set_url_scheme('//'.$this->cdn_host.$local_file->uri, $scheme);

			if($this->cdn_invalidation_var && $this->cdn_invalidation_counter)
				$url = add_query_arg($this->cdn_invalidation_var, $this->cdn_invalidation_counter, $url);

			return $esc ? esc_attr($url) : $url;
		}

		/**
		 * Parse a URL|URI|query into a local file array.
		 *
		 * @since 14xxxx Adding CDN support.
		 *
		 * @param string $url_uri_query Input URL|URI|query.
		 *
		 * @return object|null An object with: `scheme`, `extension`, `uri` properties.
		 *    This returns NULL for any URL that is not local, or does not lead to a file.
		 */
		protected function local_file($url_uri_query)
		{
			if(!($url_uri_query = trim((string)$url_uri_query)))
				return NULL; // Unparseable.

			if(!($parsed = @parse_url($url_uri_query)))
				return NULL; // Unparseable.

			if(!empty($parsed['host']) && strcasecmp($parsed['host'], $this->local_host) !== 0)
				return NULL; // Not on this host name.

			if(!isset($parsed['path'][0]) || $parsed['path'][0] !== '/')
				return NULL; // Missing or unexpected path.

			if(substr($parsed['path'], -1) === '/')
				return NULL; // Directory, not a file.

			if(strpos($parsed['path'], '..') !== FALSE || strpos($parsed['path'], './') !== FALSE)
				return NULL; // A relative path that is not absolute.

			$scheme = NULL; // Default scheme handling.
			if(!empty($parsed['scheme'])) // A specific scheme?
				$scheme = strtolower($parsed['scheme']);

			if(!($extension = $this->extension($parsed['path'])))
				return NULL; // No extension; i.e. not a file.

			$uri = $parsed['path']; // Put URI together.
			if(!empty($parsed['query'])) $uri .= '?'.$parsed['query'];
			if(!empty($parsed['fragment'])) $uri .= '#'.$parsed['fragment'];

			return (object)compact('scheme', 'extension', 'uri');
		}

		/**
		 * Get extension from a file path.
		 *
		 * @since 14xxxx Adding CDN support.
		 *
		 * @param string $path Input file path.
		 *
		 * @return string File extension (lowercase), else an empty string.
		 */
		protected function extension($path)
		{
			if(!($path = trim((string)$path)))
				return ''; // No path.

			return strtolower(ltrim((string)strrchr(basename($path), '.'), '.'));
		}
	}
}