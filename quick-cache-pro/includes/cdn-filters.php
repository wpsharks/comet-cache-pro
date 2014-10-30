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
	abstract class cdn_filters
	{
		/**
		 * @since 14xxxx Adding CDN support.
		 * @var plugin Quick Cache instance.
		 */
		protected $plugin;

		/**
		 * @since 14xxxx Adding CDN support.
		 * @var string CDN is for this host name.
		 */
		protected $host;

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
		 * @var array Array of CDN extensions.
		 */
		protected $cdn_extensions;

		/**
		 * @since 14xxxx Adding CDN support.
		 * @var string CDN blacklisted patterns.
		 */
		protected $cdn_blacklist;

		/**
		 * Class constructor.
		 *
		 * @since 14xxxx Adding CDN support.
		 */
		public function __construct()
		{
			$this->plugin = plugin();

			// We use the network host here; since this only works from a single host name.
			// If CDN filters are used in a network, the network MUST use a sub-directory install.
			$this->host = strtolower((string)parse_url(network_home_url(), PHP_URL_HOST));

			$this->cdn_host = strtolower($this->plugin->options['cdn_host']);

			$this->cdn_over_ssl = (boolean)$this->plugin->options['cdn_over_ssl'];

			$this->cdn_extensions = trim(strtolower($this->plugin->options['cdn_extensions']), "\r\n\t\0\x0B".' ;,');
			$this->cdn_extensions = preg_split('/[|;,\s]+/', $this->cdn_extensions, NULL, PREG_SPLIT_NO_EMPTY);
			$this->cdn_extensions = array_unique($this->cdn_extensions);

			foreach($this->cdn_extensions as $_key => $_extension)
				if(in_array($_extension, array('php'), TRUE))
					unset($this->cdn_extensions[$_key]);
			unset($_key, $_extension);

			$this->cdn_blacklist = '/(?:'.implode('|', array_map(function ($pattern)
				{
					return preg_replace('/\\\\\*/', '.*?', preg_quote('/'.ltrim($pattern, '/'), '/'));
				}, preg_split('/['."\r\n".']+/', $this->plugin->options['cdn_blacklist'], NULL, PREG_SPLIT_NO_EMPTY))).')/';
		}

		/**
		 * Setup URL and content filters.
		 *
		 * @since 14xxxx Adding CDN support.
		 */
		public function setup_filters()
		{
			if(!$this->cdn_over_ssl && is_ssl())
				return; // Disable in this case.

			add_filter('home_url', array($this, 'url_filter'), PHP_INT_MAX - 10, 4);
			add_filter('site_url', array($this, 'url_filter'), PHP_INT_MAX - 10, 4);

			add_filter('network_home_url', array($this, 'url_filter'), PHP_INT_MAX - 10, 3);
			add_filter('network_site_url', array($this, 'url_filter'), PHP_INT_MAX - 10, 3);

			add_filter('content_url', array($this, 'url_filter'), PHP_INT_MAX - 10, 2);
			add_filter('plugins_url', array($this, 'url_filter'), PHP_INT_MAX - 10, 2);

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
				return $string; // Nothing to do here.

			if(!$this->host) // Missing host name?
				return $string; // Not possible.

			if(strpos($string, '<') === FALSE)
				return $string; // Save some time.

			$_this           = $this; // Reference needed by closures below.
			$regex_url_attrs = '/'. // HTML attributes containing a URL value.
			                   '(\<)'. // Open tag; group #1.
			                   '([\w\-]+)'. // Tag name; group #2.
			                   '([^>]+?)'. // Others before; group #3.
			                   '((?:href|src)\s*\=\s*)'. // attribute=; group #4.
			                   '(["\'])'. // Open quote; group #5.
			                   '([^"\'>]+?)'. // Local URL; group #6.
			                   '(\\5)'. // Close quote; group #7.
			                   '([^>]*)'. // Others after; group #8.
			                   '(\>)'. // Tag close; group #9.
			                   '/i';
			$orig_string     = $string; // In case of regex errors.
			$string          = preg_replace_callback($regex_url_attrs, function ($m) use ($_this)
			{
				$m[6] = $_this->filter_url($m[6], NULL, TRUE);
				return implode('', $m); // Concatenate all parts.
			}, $string);

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
				return $orig_url_uri_query; // Not local.

			if(!in_array($local_file->extension, $this->cdn_extensions, TRUE))
				return $orig_url_uri_query; // Not in the list of CDN extensions.

			if(preg_match('/\/wp\-admin(?:[\/?#]|$)/i', $local_file->uri))
				return $orig_url_uri_query; // Exclude `wp-admin` URIs.

			if(preg_match($this->cdn_blacklist, $local_file->uri))
				return $orig_url_uri_query; // Exclude blacklisted URIs.

			if(!isset($scheme) && isset($local_file->scheme))
				$scheme = $local_file->scheme; // Use original scheme.

			$url = set_url_scheme('//'.$this->cdn_host.$local_file->uri, $scheme);

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

			if(!$this->host) // Missing host name?
				return NULL; // Not possible.

			if(!($parsed = @parse_url($url_uri_query)))
				return NULL; // Unparseable.

			if(!empty($parsed['host']) && strcasecmp($parsed['host'], $this->host) !== 0)
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