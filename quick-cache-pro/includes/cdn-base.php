<?php
/**
 * CDN Base Class.
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
	 * CDN Base Class.
	 */
	abstract class cdn_base
	{
		/**
		 * @var plugin Quick Cache instance.
		 */
		protected $plugin; // Set by constructor.

		/**
		 * @var string CDN is for this host name.
		 */
		protected $host; // Set by constructor.

		/**
		 * @var string CDN serves files from this host.
		 */
		protected $cdn_host; // Set by constructor.

		/**
		 * @var array Array of CDN extensions.
		 */
		protected $cdn_extensions; // Set by constructor.

		/**
		 * Class constructor.
		 */
		public function __construct()
		{
			$this->plugin = plugin();

			$this->host     = strtolower((string)parse_url(network_home_url(), PHP_URL_HOST));
			$this->cdn_host = strtolower($this->plugin->options['cdn_host']);

			$this->cdn_extensions = trim(strtolower($this->plugin->options['cdn_extensions']), "\r\n\t\0\x0B".' ;,');
			$this->cdn_extensions = preg_split('/[|;,\s]+/', $this->cdn_extensions, NULL, PREG_SPLIT_NO_EMPTY);
			$this->cdn_extensions = array_unique($this->cdn_extensions);

			foreach($this->cdn_extensions as $_key => $_extension)
				if(in_array($_extension, array('php'), TRUE))
					unset($this->cdn_extensions[$_key]);
			unset($_key, $_extension);
		}

		/**
		 * @TODO Get existing distro.
		 */
		abstract public function get_distro();

		/**
		 * @TODO Create a new distro.
		 */
		abstract public function create_distro();

		/**
		 * @TODO Update existing distro.
		 */
		abstract public function update_distro();

		/**
		 * Setup URL and content filters. @TODO add content filters.
		 */
		public function setup_filters()
		{
			add_filter('home_url', array($this, 'url_filter'), PHP_INT_MAX - 10, 4);
			add_filter('site_url', array($this, 'url_filter'), PHP_INT_MAX - 10, 4);

			add_filter('network_home_url', array($this, 'url_filter'), PHP_INT_MAX - 10, 3);
			add_filter('network_site_url', array($this, 'url_filter'), PHP_INT_MAX - 10, 3);

			add_filter('content_url', array($this, 'url_filter'), PHP_INT_MAX - 10, 2);
			add_filter('plugins_url', array($this, 'url_filter'), PHP_INT_MAX - 10, 2);
		}

		/**
		 * Filter home/site URLs that should be served by the CDN.
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
		 * @param string $string Input content string to filter; i.e. HTML code.
		 *
		 * @return string The content string after having been filtered.
		 */
		public function content_filter($string)
		{
			if(!$this->host) // Missing host name?
				return $string; // Not possible.

			if(!($string = (string)$string))
				return $string; // Nothing to do here.

			$regex = '/(["\'])(\/\/'.preg_quote($this->host, '/').'\/.+?)(\\1)/';
			// @TODO Finish regex pattern(s). May need to look at relative paths also.
			// @TODO Also need to do a better job of finding true URLs within certain contexts.

			return preg_replace_callback($regex, array($this, '_content_filter_cb'), $string);
		}

		protected function _content_filter_cb(array $m)
		{
			return $m[1].$this->filter_url($m[2]).$m[3]; // @TODO Deal with HTML entities here.
		}

		/**
		 * Filter URLs that should be served by the CDN.
		 *
		 * @param string      $url_uri_query Input URL|URI|query.
		 * @param string|null $scheme `NULL`, `http`, `https`, `login`, `login_post`, `admin`, or `relative`.
		 *
		 * @return string The URL after having been filtered.
		 */
		protected function filter_url($url_uri_query, $scheme = NULL)
		{
			if(!($local_file = $this->local_file($url_uri_query)))
				return $url_uri_query; // Not local.

			if(!in_array($local_file->extension, $this->cdn_extensions, TRUE))
				return $url_uri_query; // Not in the list of CDN extensions.

			if(preg_match('/\/wp\-admin(?:[\/?#]|$)/i', $local_file->uri))
				return $url_uri_query; // Exclude `wp-admin` URIs.

			return set_url_scheme('//'.$this->cdn_host.$local_file->uri, $scheme);
		}

		/**
		 * Parse a URL|URI|query into a local file array.
		 *
		 * @param string $url_uri_query Input URL|URI|query.
		 *
		 * @return object|null An object with two properties: `uri` and `extension`.
		 *    This returns NULL for any file that is not local, or not a file.
		 */
		protected function local_file($url_uri_query)
		{
			if(!$this->host) // Missing host name?
				return NULL; // Not possible.

			if(!($url_uri_query = trim((string)$url_uri_query)))
				return NULL; // Unparseable.

			if(!($parsed = @parse_url($url_uri_query)))
				return NULL; // Unparseable.

			if(!empty($parsed['host']) && strcasecmp($parsed['host'], $this->host) !== 0)
				return NULL; // Not on this host name.

			if(!isset($parsed['path'][0]) || substr($parsed['path'], -1) === '/')
				return NULL; // No path. Or, not a file; i.e. a directory.

			if(!($extension = $this->extension($parsed['path'])))
				return NULL; // No extension; i.e. not a file.

			$uri = $parsed['path']; // Put URI together.
			if(strpos($uri, '/') !== 0) $uri = '/'.$uri;
			if(!empty($parsed['query'])) $uri .= '?'.$parsed['query'];
			if(!empty($parsed['fragment'])) $uri .= '#'.$parsed['fragment'];

			return (object)compact('uri', 'extension');
		}

		protected function extension($path)
		{
			if(!($path = (string)$path))
				return ''; // No path.

			return strtolower(ltrim((string)strrchr(basename($path), '.'), '.'));
		}
	}
}