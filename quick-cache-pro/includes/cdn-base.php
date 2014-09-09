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
	}
}