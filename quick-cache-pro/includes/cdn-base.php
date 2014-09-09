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
		 * Class constructor.
		 */
		public function __construct()
		{
			$this->plugin = plugin();
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

		/** @TODO Filter URLs that should be served by the CDN.
		 *
		 * @param string $url
		 *
		 * @return string The URL after having been filtered.
		 */
		public function filter_url($url)
		{
		}

		/** @TODO Filter content for URLs that should be served by the CDN.
		 *
		 * @param string $string
		 *
		 * @return string The content string after having been filtered.
		 */
		public function filter_content($string)
		{
		}
	}
}