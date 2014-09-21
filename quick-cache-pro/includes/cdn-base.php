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
		 * @TODO Setup URL and content filters.
		 */
		public function setup_filters()
		{
		}

		/**
		 * Filter URLs that should be served by the CDN.
		 *
		 * @param string      $url Input URL passed by one of several filters.
		 * @param string|null $scheme Scheme passed by one of several filters.
		 *
		 * @return string The URL after having been filtered.
		 */
		public function filter_url($url, $scheme = NULL)
		{
			if(!($local_file = $this->local_file($url)))
				return $url; // Not local.

			if(!in_array($local_file->extension, $this->cdn_extensions, TRUE))
				return $url; // Not in the list of CDN extensions.

			return set_url_scheme('//'.$this->cdn_host.$local_file->uri, $scheme);
		}

		/** @TODO Filter content for URLs that should be served by the CDN.
		 *
		 * @param string $string
		 *
		 * @return string The content string after having been filtered.
		 */
		public function filter_content($string)
		{
			if(!($string = (string)$string))
				return $string; // Nothing to do here.

			$regex = ''; // @TODO
			return preg_replace_callback($regex, array($this, '_filter_content_cb'), $string);
		}

		protected function _filter_content_cb(array $m)
		{
			return $m[0]; // @TODO
		}

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