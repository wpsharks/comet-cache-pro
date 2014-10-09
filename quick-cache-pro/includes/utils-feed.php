<?php
/**
 * Feed Utilities
 *
 * @package quick_cache\auto_cache_engine
 * @since 14xxxx Refactoring cache clear/purge routines.
 * @copyright WebSharks, Inc. <http://www.websharks-inc.com>
 * @license GNU General Public License, version 2
 */
namespace quick_cache // Root namespace.
{
	if(!defined('WPINC')) // MUST have WordPress.
		exit('Do NOT access this file directly: '.basename(__FILE__));

	/**
	 * Feed Utilities
	 */
	class auto_cache
	{
		/**
		 * @var plugin Quick Cache instance.
		 *
		 * @since 14xxxx Refactoring cache clear/purge routines.
		 */
		protected $plugin;

		/**
		 * @var string WordPress `home_url('/')`.
		 *
		 * @since 14xxxx Refactoring cache clear/purge routines.
		 */
		protected $home_url;

		/**
		 * @var string Default feed type; e.g. `rss2`.
		 *
		 * @since 14xxxx Refactoring cache clear/purge routines.
		 */
		protected $default_feed;

		/**
		 * @var boolean Using SEO-friendly permalinks?
		 *
		 * @since 14xxxx Refactoring cache clear/purge routines.
		 */
		protected $seo_friendly_permalinks;

		/**
		 * @var array All unique feed types.
		 *
		 * @since 14xxxx Refactoring cache clear/purge routines.
		 */
		protected $feed_types;

		/**
		 * Class constructor.
		 *
		 * @since 14xxxx Refactoring cache clear/purge routines.
		 */
		public function __construct()
		{
			$this->plugin = plugin();

			$this->home_url                = home_url('/');
			$this->default_feed            = get_default_feed();
			$this->seo_friendly_permalinks = (boolean)get_option('permalink_structure');
			$this->feed_types              = array_unique(array($this->default_feed, 'rdf', 'rss', 'rss2', 'atom'));
		}

		/**
		 * Feed link variations.
		 *
		 * @since 14xxxx Refactoring cache clear/purge routines.
		 *
		 * @param string $type_prefix A feed type prefix; optional.
		 *
		 * @return array An array of all feed link variations.
		 */
		public function feed_link_variations($type_prefix = '')
		{
			$variations = array(); // Initialize.

			foreach($this->feed_types as $_feed_type)
				$variations[] = get_feed_link((string)$type_prefix.$_feed_type);
			unset($_feed_type); // Housekeeping.

			return $variations;
		}

		/**
		 * Post comments; feed link variations.
		 *
		 * @since 14xxxx Refactoring cache clear/purge routines.
		 *
		 * @param \WP_Post A WordPress post class instance.
		 *
		 * @return array An array of all feed link variations.
		 */
		public function post_comments_feed_link_variations(\WP_Post $post)
		{
			$variations = array(); // Initialize.

			foreach($this->feed_types as $_feed_type)
				$variations[] = get_post_comments_feed_link($post->ID, $_feed_type);
			unset($_feed_type); // Housekeeping.

			return $variations;
		}

		/**
		 * Post author; feed link variations.
		 *
		 * @since 14xxxx Refactoring cache clear/purge routines.
		 *
		 * @param \WP_Post A WordPress post class instance.
		 *
		 * @return array An array of all feed link variations.
		 */
		public function author_feed_link_variations(\WP_Post $post)
		{
			$variations = array(); // Initialize.

			foreach($this->feed_types as $_feed_type)
				$variations[] = get_author_feed_link($post->post_author, $_feed_type);

			if($this->seo_friendly_permalinks && ($post_author = get_userdata($post->post_author)))
				foreach($this->feed_types as $_feed_type)
				{
					$variations[] = add_query_arg(urlencode_deep(array('author' => $post->post_author)), $this->home_url.'feed/'.urlencode($_feed_type).'/');
					$variations[] = add_query_arg(urlencode_deep(array('author' => $post_author->user_nicename)), $this->home_url.'feed/'.urlencode($_feed_type).'/');
				}
			unset($_feed_type); // Housekeeping.

			return $variations;
		}

		/**
		 * Post type archive; feed link variations.
		 *
		 * @since 14xxxx Refactoring cache clear/purge routines.
		 *
		 * @param \WP_Post A WordPress post class instance.
		 *
		 * @return array An array of all feed link variations.
		 */
		public function post_type_archive_link_variations(\WP_Post $post)
		{
			$variations = array(); // Initialize.

			foreach($this->feed_types as $_feed_type)
				$variations[] = get_post_type_archive_feed_link($post->post_type, $_feed_type);
			unset($_feed_type); // Housekeeping.

			return $variations;
		}

		/**
		 * Post terms; feed link variations.
		 *
		 * @since 14xxxx Refactoring cache clear/purge routines.
		 *
		 * @param \WP_Post A WordPress post class instance.
		 *
		 * @return array An array of all feed link variations.
		 */
		public function post_term_feed_link_variations(\WP_Post $post)
		{
			$variations = array(); // Initialize.
			$post_terms = array(); // Initialize.

			if(!is_array($post_taxonomies = get_object_taxonomies($post, 'objects')) || !$post_taxonomies)
				return $variations; // Nothing to do here; post has no terms.

			foreach($post_taxonomies as $_post_taxonomy) // Collect terms for each taxonomy.
				if(is_array($_post_taxonomy_terms = wp_get_post_terms($post->ID, $_post_taxonomy->name)) && $_post_taxonomy_terms)
					$post_terms = array_merge($post_terms, $_post_taxonomy_terms);
			unset($_post_taxonomy, $_post_taxonomy_terms); // Housekeeping.

			foreach($post_terms as $_post_term) // Iterate all post terms.
			{
				foreach($this->feed_types as $_feed_type)
					$variations[] = get_term_feed_link($_post_term->term_id, $_post_term->taxonomy, $_feed_type);
				unset($_feed_type); // Housekeeping.

				if($this->seo_friendly_permalinks && ($_taxonomy = get_taxonomy($_post_term->taxonomy)))
				{
					if($_taxonomy->name === 'category')
						$_taxonomy_query_var = 'cat'; // Special case.
					else $_taxonomy_query_var = $_taxonomy->query_var;

					foreach($this->feed_types as $_feed_type)
						$variations[] = add_query_arg(urlencode_deep(array($_taxonomy_query_var => $_post_term->term_id)), $this->home_url.'feed/'.urlencode($_feed_type).'/');
					unset($_feed_type); // Housekeeping.

					foreach($this->feed_types as $_feed_type)
						$variations[] = add_query_arg(urlencode_deep(array($_taxonomy_query_var => $_post_term->slug)), $this->home_url.'feed/'.urlencode($_feed_type).'/');
					unset($_feed_type); // Housekeeping.
				}
				unset($_taxonomy, $_taxonomy_query_var); // Housekeeping.

				// @TODO Need to finish this part off...

				/* NOTE: We CANNOT reliably include permalink variations here that use query string vars.
								This is because Quick Cache hashes query string variables via MD5 checksums.
								For this reason, we deal with SEO-friendly permalink variations only here. */

				if($post_term_feed_link && strpos($post_term_feed_link, '?') === FALSE
				   && is_object($post_term) && !empty($post_term->term_id) && !empty($post_term->slug)
				)// Create variations that deal with SEO-friendly permalink variations.
				{
					// Quick example: `(?:123|slug)`; to consider both.
					$_term_id_or_slug = '(?:'.preg_quote($post_term->term_id, '/').
					                    '|'.preg_quote(preg_replace('/[^a-z0-9\/.]/i', '-', $post_term->slug), '/').')';

					// Quick example: `http://www.example.com/tax/term/feed`;
					//    with a wildcard this becomes: `http://www.example.com/tax/*/feed`
					$_wildcarded = preg_replace('/\/[^\/]+\/feed([\/?#]|$)/', '/*/feed'.'${1}', $post_term_feed_link);

					// Quick example: `http://www.example.com/tax/*/feed`;
					//   becomes: `www\.example\.com\/tax\/.*?(?=[\/\-]?(?:123|slug)[\/\-]).*?\/feed`
					//    ... this covers variations that use: `/tax/term,term/feed/`
					//    ... also covers variations that use: `/tax/term/tax/term/feed/`
					$variations[] = $build_cache_path_regex($_wildcarded, '.*?(?=[\/\-]?'.$_term_id_or_slug.'[\/\-]).*?');
					// NOTE: This may also pick up false-positives. Not much we can do about this.
					//    For instance, if another feed has the same word/slug in what is actually a longer/different term.
					//    Or, if another feed has the same word/slug in what is actually the name of a taxonomy.

					unset($_term_id_or_slug, $_wildcarded); // Housekeeping.
				}
			}
			unset($_post_term); // Housekeeping.

			return $variations;
		}
	}
}