<?php
/**
 * Auto-Cache Engine.
 *
 * @since 140420 Adding auto-cache engine.
 * @package zencache\auto_cache_engine
 * @copyright WebSharks, Inc. <http://www.websharks-inc.com>
 * @license GNU General Public License, version 2
 */
namespace zencache // Root namespace.
{
	if(!defined('WPINC')) // MUST have WordPress.
		exit('Do NOT access this file directly: '.basename(__FILE__));

	/**
	 * Auto-Cache Engine.
	 */
	class auto_cache
	{
		/**
		 * @var plugin ZenCache instance.
		 */
		protected $plugin; // Set by constructor.

		/**
		 * Class constructor.
		 */
		public function __construct()
		{
			$this->plugin = plugin();
			$this->run(); // Run!
		}

		/**
		 * Public runner; attach to WP-Cron.
		 */
		public function run()
		{
			if(!$this->plugin->options['enable'])
				return; // Nothing to do.

			if(!$this->plugin->options['auto_cache_enable'])
				return; // Nothing to do.

			if(!$this->plugin->options['auto_cache_sitemap_url'])
				if(!$this->plugin->options['auto_cache_other_urls'])
					return; // Nothing to do.

			$cache_dir = $this->plugin->cache_dir();
			if(!is_dir($cache_dir) || !is_writable($cache_dir))
				return; // Not possible in this case.

			$max_time = (integer)$this->plugin->options['auto_cache_max_time'];
			$max_time = $max_time > 60 ? $max_time : 900; // 60+ seconds.

			@set_time_limit($max_time); // Max time.
			// @TODO When disabled, display a warning.
			ignore_user_abort(TRUE); // Keep running.

			$micro_start_time = microtime(TRUE);
			$start_time       = time(); // Initialize.
			$total_urls       = $total_time = 0; // Initialize.

			$network_home_url  = rtrim(network_home_url(), '/');
			$network_home_host = parse_url($network_home_url, PHP_URL_HOST);
			$network_home_path = parse_url($network_home_url, PHP_URL_PATH);

			$delay = (integer)$this->plugin->options['auto_cache_delay']; // In milliseconds.
			$delay = $delay > 0 ? $delay * 1000 : 0; // Convert delay to microseconds for `usleep()`.

			if(($other_urls = preg_split('/\s+/', $this->plugin->options['auto_cache_other_urls'], NULL, PREG_SPLIT_NO_EMPTY)))
				$blogs = array((object)array('domain' => $network_home_host, 'path' => $network_home_path, 'other' => $other_urls));
			else $blogs = array((object)array('domain' => $network_home_host, 'path' => $network_home_path));

			if(is_multisite()) // If this is a network, including child blogs also.
			{
				$wpdb = $this->plugin->wpdb(); // WordPress DB object instance.
				if(($_child_blogs = $wpdb->get_results("SELECT `domain`, `path` FROM `".esc_sql($wpdb->blogs)."` WHERE `deleted` <= '0'")))
					$blogs = array_merge($blogs, $_child_blogs);
				unset($_child_blogs); // Housekeeping.
			}
			shuffle($blogs); // Randomize the order; i.e. don't always start from the top.

			foreach($blogs as $_blog) // Auto-cache sitemap URLs for each of the blogs.
			{
				$_blog_sitemap_urls = $_blog_other_urls = $_blog_urls = array();
				$_blog_url          = 'http://'.$_blog->domain.'/'.trim($_blog->path, '/');

				if($this->plugin->options['auto_cache_sitemap_url']) // Memory-optimized routine.
					$_blog_sitemap_urls = $this->get_sitemap_urls_deep($_blog_url.'/'.$this->plugin->options['auto_cache_sitemap_url']);
				if(!empty($_blog->other)) $_blog_other_urls = array_merge($_blog_urls, $_blog->other);

				$_blog_urls = array_merge($_blog_sitemap_urls, $_blog_other_urls);
				$_blog_urls = array_unique($_blog_urls); // Unique URLs only.
				shuffle($_blog_urls); // Randomize the order.

				foreach($_blog_urls as $_url)
				{
					$total_urls++;
					$this->auto_cache_url($_url);

					if((time() - $start_time) > ($max_time - 30))
						break 2; // Stop now.

					if($delay) usleep($delay);
				}
				unset($_url); // A little housekeeping.
			}
			unset($_blog, $_blog_sitemap_urls, $_blog_other_urls, $_blog_urls, $_blog_url);

			$total_time = number_format(microtime(TRUE) - $micro_start_time, 5, '.', '').' seconds';

			$this->log_auto_cache_run($total_urls, $total_time);
		}

		/**
		 * Auto-cache a specific URL.
		 *
		 * @param string $url The URL to auto-cache.
		 */
		protected function auto_cache_url($url)
		{
			if(!($url = trim((string)$url)))
				return; // Nothing to do.

			if(!$this->plugin->options['get_requests'] && strpos($url, '?') !== FALSE)
				return; // We're NOT caching URLs with a query string.

			$cache_path      = $this->plugin->build_cache_path($url);
			$cache_file_path = $this->plugin->cache_dir($cache_path);

			if(is_file($cache_file_path)) // If it's already cached (and still fresh); just bypass silently.
				if(filemtime($cache_file_path) >= strtotime('-'.$this->plugin->options['cache_max_age']))
					return; // Cached already.

			$this->log_auto_cache_url($url, wp_remote_get($url, array('blocking'   => FALSE, // Non-blocking for speedy auto-caching.
			                                                          'user-agent' => $this->plugin->options['auto_cache_user_agent'].'; '.__NAMESPACE__.' '.$this->plugin->version)));
		}

		/**
		 * Logs an attempt to auto-cache a specific URL.
		 *
		 * @param string    $url The URL we attempted to auto-cache.
		 * @param \WP_Error $wp_remote_get_response For IDEs.
		 *
		 * @throws \exception If log file exists already; but is NOT writable.
		 */
		protected function log_auto_cache_url($url, $wp_remote_get_response)
		{
			$cache_dir           = $this->plugin->cache_dir();
			$cache_lock          = $this->plugin->cache_lock();
			$auto_cache_log_file = $cache_dir.'/zc-auto-cache.log';

			if(is_file($auto_cache_log_file) && !is_writable($auto_cache_log_file))
				throw new \exception(sprintf(__('Auto-cache log file is NOT writable: `%1$s`. Please set permissions to `644` (or higher). `666` might be needed in some cases.', $this->plugin->text_domain), $auto_cache_log_file));

			if(is_wp_error($wp_remote_get_response)) // Log HTTP communication errors.
				$log_entry = 'Time: '.date(DATE_RFC822)."\n".'URL: '.$url."\n".'Error: '.$wp_remote_get_response->get_error_message()."\n\n";
			else $log_entry = 'Time: '.date(DATE_RFC822)."\n".'URL: '.$url."\n\n";

			file_put_contents($auto_cache_log_file, $log_entry, FILE_APPEND);
			if(filesize($auto_cache_log_file) > 2097152) // 2MB is the maximum log file size.
				rename($auto_cache_log_file, substr($auto_cache_log_file, 0, -4).'-archived-'.time().'.log');

			$this->plugin->cache_unlock($cache_lock); // Unlock cache directory.
		}

		/**
		 * Logs auto-cache run totals.
		 *
		 * @param integer $total_urls Total URLs processed by the run.
		 * @param string  $total_time Total time it took to complete processing.
		 *
		 * @throws \exception If log file exists already; but is NOT writable.
		 */
		protected function log_auto_cache_run($total_urls, $total_time)
		{
			$cache_dir           = $this->plugin->cache_dir();
			$cache_lock          = $this->plugin->cache_lock();
			$auto_cache_log_file = $cache_dir.'/zc-auto-cache.log';

			if(is_file($auto_cache_log_file) && !is_writable($auto_cache_log_file))
				throw new \exception(sprintf(__('Auto-cache log file is NOT writable: `%1$s`. Please set permissions to `644` (or higher). `666` might be needed in some cases.', $this->plugin->text_domain), $auto_cache_log_file));

			$log_entry = 'Run Completed: '.date(DATE_RFC822)."\n".'Total URLs: '.$total_urls."\n".'Total Time: '.$total_time."\n\n";

			file_put_contents($auto_cache_log_file, $log_entry, FILE_APPEND);
			if(filesize($auto_cache_log_file) > 2097152) // 2MB is the maximum log file size.
				rename($auto_cache_log_file, substr($auto_cache_log_file, 0, -4).'-archived-'.time().'.log');

			$this->plugin->cache_unlock($cache_lock); // Unlock cache directory.
		}

		/**
		 * Collects all URLs from an XML sitemap deeply.
		 *
		 * @param string  $sitemap A URL to an XML sitemap file.
		 *    This supports nested XML sitemap index files too; i.e. `<sitemapindex>`.
		 *    Note that GZIP files are NOT supported at this time.
		 *
		 * @param boolean $___recursive For internal use only.
		 *
		 * @return array URLs from an XML sitemap deeply.
		 *
		 * @throws \exception If `$sitemap` is NOT actually a sitemap.
		 */
		protected function get_sitemap_urls_deep($sitemap, $___recursive = FALSE)
		{
			$urls       = array();
			$xml_reader = new \XMLReader();

			if(!($sitemap = trim((string)$sitemap)))
				goto finale; // Nothing we can do.

			if(is_wp_error($head = wp_remote_head($sitemap, array('redirection' => 5))))
			{
				if($___recursive) goto finale; // Fail silently on recursive calls.
				throw new \exception(sprintf(__('Invalid XML sitemap. Unreachable URL: `%1$s`. %2$s', $this->plugin->text_domain),
				                             $sitemap, $head->get_error_message())); // Include the WP error message too.
			}
			if(empty($head['response']['code']) || (integer)$head['response']['code'] >= 400)
			{
				if($___recursive) goto finale; // Fail silently on recursive calls.
				throw new \exception(sprintf(__('Invalid XML sitemap status code at: `%1$s`. Expecting a `200` status. Instead got: `%2$s`.', $this->plugin->text_domain),
				                             $sitemap, !empty($head['response']['code']) ? $head['response']['code'] : ''));
			}
			if(empty($head['headers']['content-type']) || stripos($head['headers']['content-type'], 'xml') === FALSE)
			{
				if($___recursive) goto finale; // Fail silently on recursive calls.
				throw new \exception(sprintf(__('Invalid XML sitemap content type at: `%1$s`. Expecting XML. Instead got: `%2$s`.', $this->plugin->text_domain),
				                             $sitemap, !empty($head['headers']['content-type']) ? $head['headers']['content-type'] : ''));
			}
			if($xml_reader->open($sitemap)) // Attempt to open and read the sitemap.
				while($xml_reader->read()) if($xml_reader->nodeType === $xml_reader::ELEMENT)
				{
					switch($xml_reader->name)
					{
						case 'sitemapindex': // e.g. <http://www.smashingmagazine.com/sitemap_index.xml>
							if(($_sitemapindex_urls = $this->_xml_get_sitemapindex_urls_deep($xml_reader)))
								$urls = array_merge($urls, $_sitemapindex_urls);
							break; // Break switch handler.

						case 'urlset': // e.g. <http://www.smashingmagazine.com/category-sitemap.xml>
							if(($_urlset_urls = $this->_xml_get_urlset_urls($xml_reader)))
								$urls = array_merge($urls, $_urlset_urls);
							break; // Break switch handler.
					}
				}
			unset($_sitemapindex_urls, $_urlset_urls); // Housekeeping.

			finale: // Target point; grand finale.

			return $urls; // A full set of all sitemap URLs; i.e. `<loc>` tags.
		}

		/**
		 * For internal use only.
		 *
		 * @param \XMLReader $xml_reader
		 *
		 * @return array All sitemap URLs from this `<sitemapindex>` node; deeply.
		 */
		protected function _xml_get_sitemapindex_urls_deep(\XMLReader $xml_reader)
		{
			$urls = array(); // Initialize.

			if($xml_reader->name === 'sitemapindex') // Sanity check.
				while($xml_reader->read()) if($xml_reader->nodeType === $xml_reader::ELEMENT)
				{
					switch($xml_reader->name)
					{
						case 'sitemap':
							$is_sitemap_node = TRUE;
							break; // Break switch handler.

						case 'loc': // A URL location.
							if(!empty($is_sitemap_node) && $xml_reader->read() && ($_loc = trim($xml_reader->value)))
								$urls = array_merge($urls, $this->get_sitemap_urls_deep($_loc, TRUE));
							break; // Break switch handler.

						default: // Anything else.
							$is_sitemap_node = FALSE;
							break; // Break switch handler.
					}
				}
			return $urls; // All sitemap URLs from this `<sitemapindex>` node; deeply.
		}

		/**
		 * For internal use only.
		 *
		 * @param \XMLReader $xml_reader
		 *
		 * @return array All sitemap URLs from this `<urlset>` node.
		 */
		protected function _xml_get_urlset_urls(\XMLReader $xml_reader)
		{
			$urls = array(); // Initialize.

			if($xml_reader->name === 'urlset') // Sanity check.
				while($xml_reader->read()) if($xml_reader->nodeType === $xml_reader::ELEMENT)
				{
					switch($xml_reader->name)
					{
						case 'url':
							$is_url_node = TRUE;
							break; // Break switch handler.

						case 'loc': // A URL location.
							if(!empty($is_url_node) && $xml_reader->read() && ($_loc = trim($xml_reader->value)))
								$urls[] = $_loc; // Add this URL to the list :-)
							break; // Break switch handler.

						default: // Anything else.
							$is_url_node = FALSE;
							break; // Break switch handler.
					}
				}
			return $urls; // All sitemap URLs from this `<urlset>` node.
		}
	}
}