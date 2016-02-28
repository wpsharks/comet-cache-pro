<?php
/*[pro strip-from="lite"]*/
namespace WebSharks\CometCache\Pro\Classes;

/**
 * Cache directory stats.
 *
 * @since 151002 Directory stats.
 */
class DirStats extends AbsBase
{
    /**
     * Cache key.
     *
     * @since 151002 Directory stats.
     *
     * @type string Cache key.
     */
    protected $cache_key = '';

    /**
     * History cache key.
     *
     * @since 151002 Directory stats.
     *
     * @type string History cache key.
     */
    protected $history_cache_key = '';

    /**
     * Allowed history cache keys.
     *
     * @since 151002 Directory stats.
     *
     * @type string Allowed history cache keys.
     */
    protected $allowed_history_cache_keys = array();

    /**
     * Allowed history cache keys (current host).
     *
     * @since 151002 Directory stats.
     *
     * @type string Allowed history cache keys (current host).
     */
    protected $allowed_host_history_cache_keys = array();

    /**
     * Class constructor.
     *
     * @since 151002 Directory stats.
     */
    public function __construct()
    {
        parent::__construct();

        $this->cache_key         = GLOBAL_NS.'_dir_stats';
        $this->history_cache_key = GLOBAL_NS.'_h_dir_stats';

        $this->allowed_history_cache_keys      = array(md5('forCache0'), md5('forHtmlCCache0'));
        $this->allowed_host_history_cache_keys = array(md5('forHostCache0'), md5('forHtmlCHostCache0'));
    }

    /**
     * Directory stats cache.
     *
     * @since 151002 Directory stats.
     *
     * @return array Directory stats cache.
     */
    protected function getCache()
    {
        if (!is_array($cache = get_site_option($this->cache_key))) {
            update_site_option($this->cache_key, ($cache = array()));
        }
        return $cache;
    }

    /**
     * Directory stats cache (current host).
     *
     * @since 151002 Directory stats.
     *
     * @return array Directory stats cache (current host).
     */
    protected function getHostCache()
    {
        $host_cache_key = $this->cacheKeyForBlog();

        if (!is_array($host_cache = get_site_option($host_cache_key))) {
            update_site_option($host_cache_key, ($host_cache = array()));
        }
        return $host_cache;
    }

    /**
     * Directory stats history cache.
     *
     * @since 151002 Directory stats.
     *
     * @return array Directory stats history cache.
     */
    protected function getHistoryCache()
    {
        if (!is_array($cache = get_site_option($this->history_cache_key))) {
            update_site_option($this->history_cache_key, ($cache = array()));
        }
        return $cache;
    }

    /**
     * Directory stats history cache (current host).
     *
     * @since 151002 Directory stats.
     *
     * @return array Directory stats history cache (current host).
     */
    protected function getHostHistoryCache()
    {
        $host_history_cache_key = $this->historyCacheKeyForBlog();

        if (!is_array($host_history_cache = get_site_option($host_history_cache_key))) {
            update_site_option($host_history_cache_key, ($host_history_cache = array()));
        }
        return $host_history_cache;
    }

    /**
     * Update directory stats cache.
     *
     * @since 151002 Directory stats.
     *
     * @param string    $key   Cache key.
     * @param \stdClass $stats Stats to update.
     */
    protected function updateCache($key, \stdClass $stats)
    {
        $cache = $this->getCache();
        $cache = array_merge($cache, array((string) $key => $stats));
        update_site_option($this->cache_key, $cache);
        $this->updateHistoryCache($key, $stats);
    }

    /**
     * Update directory stats cache (current host).
     *
     * @since 151002 Directory stats.
     *
     * @param string    $key   Cache key.
     * @param \stdClass $stats Stats to update.
     */
    protected function updateHostCache($key, \stdClass $stats)
    {
        $host_cache     = $this->getHostCache();
        $host_cache_key = $this->cacheKeyForBlog();
        $host_cache     = array_merge($host_cache, array((string) $key => $stats));
        update_site_option($host_cache_key, $host_cache);
        $this->updateHostHistoryCache($key, $stats);
    }

    /**
     * Update directory stats history cache.
     *
     * @since 151002 Directory stats.
     *
     * @param string    $key   Cache key.
     * @param \stdClass $stats Stats to update.
     */
    protected function updateHistoryCache($key, \stdClass $stats)
    {
        $history_injected      = false; // Initialize.
        $history_cache         = $this->getHistoryCache();
        $history_max_time_keys = max(1, $this->plugin->options['dir_stats_history_days']) * 5; // 30 x 5 = 150
        $history_max_age       = strtotime('-'.max(1, $this->plugin->options['dir_stats_history_days']).' days');

        // Note: Time keys are based on the hour they were pulled in; i.e., `$stats->time`.
        // This time-rounding prevents any single day from adding more than 24 times.

        // In addition, we don't overwrite stats in any hour that had a larger size previously.
        // This gives us a better look at what a load average looks like overall throughout a day.

        if (in_array($key, $this->allowed_history_cache_keys, true) && isset($stats->stats, $stats->time) && is_object($stats->stats)) {
            $stats_hour = $stats->time - ($stats->time % 3600); // Stats hour; i.e., based on the stats time.
            if (!isset($history_cache[$key][$stats_hour]->stats->total_size) || $stats->stats->total_size > $history_cache[$key][$stats_hour]->stats->total_size) {
                $history_cache[$key][$stats_hour] = $stats; // Keep the largest size in this hour.
                $history_injected                 = true; // Flag as true.
            }
        }
        if (!$history_injected) { // If not injected we can stop here.
            return; // Nothing more to do; sub-routine below is not necessary.
        }
        $history_cache = array_slice($history_cache, 0, count($this->allowed_history_cache_keys), true);

        foreach ($history_cache as $_key => $_key_stats) { // Each string key.
            if (!$_key || !is_string($_key) || !$_key_stats || !is_array($_key_stats)) {
                unset($history_cache[$_key]); // Delete key.
                continue; // All done for this key.
            }
            foreach ($_key_stats as $_time => $_stats) { // Each time in this key.
                if (!$_time || !isset($_stats->stats, $_stats->time) || !is_object($_stats->stats)) {
                    unset($history_cache[$_key][$_time]); // Delete time key.
                    continue; // All done for this time key.
                }
                if ($_time < $history_max_age || $_stats->time < $history_max_age) {
                    unset($history_cache[$_key][$_time]); // Delete time key.
                    continue; // All done for this time key.
                }
            }
            if (!$history_cache[$_key]) {
                unset($history_cache[$_key]);
            } else { // Newest to oldest sorting + slicer.
                krsort($history_cache[$_key], SORT_NUMERIC);
                $history_cache[$_key] = array_slice($history_cache[$_key], 0, $history_max_time_keys, true);
            }
        }
        unset($_key, $_key_stats, $_time, $_stats); // Housekeeping.

        update_site_option($this->history_cache_key, $history_cache);
    }

    /**
     * Update directory stats history cache (current host).
     *
     * @since 151002 Directory stats.
     *
     * @param string    $key   Cache key (optional).
     * @param \stdClass $stats Stats to update (optional).
     */
    protected function updateHostHistoryCache($key = '', \stdClass $stats = null)
    {
        $host_history_injected      = false; // Initialize.
        $host_history_cache         = $this->getHostHistoryCache();
        $host_history_cache_key     = $this->historyCacheKeyForBlog();
        $host_history_max_time_keys = max(1, $this->plugin->options['dir_stats_history_days']) * 5; // 30 x 5 = 150
        $host_history_max_age       = strtotime('-'.max(1, $this->plugin->options['dir_stats_history_days']).' days');

        // Note: Time keys are based on the hour they were pulled in; i.e., `$stats->time`.
        // This time-rounding prevents any single day from adding more than 24 times.

        // In addition, we don't overwrite stats in any hour that had a larger size previously.
        // This gives us a better look at what a load average looks like overall throughout a day.

        if (in_array($key, $this->allowed_host_history_cache_keys, true) && isset($stats->stats, $stats->time) && is_object($stats->stats)) {
            $stats_hour = $stats->time - ($stats->time % 3600); // Stats hour; i.e., based on the stats time.
            if (!isset($host_history_cache[$key][$stats_hour]->stats->total_size) || $stats->stats->total_size > $host_history_cache[$key][$stats_hour]->stats->total_size) {
                $host_history_cache[$key][$stats_hour] = $stats; // Keep the largest size in this hour.
                $host_history_injected                 = true; // Flag as true.
            }
        }
        if (!$host_history_injected) { // If not injected we can stop here.
            return; // Nothing more to do; sub-routine below is not necessary.
        }
        $host_history_cache = array_slice($host_history_cache, 0, count($this->allowed_host_history_cache_keys), true);

        foreach ($host_history_cache as $_key => $_key_stats) { // Each string key.
            if (!$_key || !is_string($_key) || !$_key_stats || !is_array($_key_stats)) {
                unset($host_history_cache[$_key]); // Delete key.
                continue; // All done for this key.
            }
            foreach ($_key_stats as $_time => $_stats) { // Each time in this key.
                if (!$_time || !isset($_stats->stats, $_stats->time) || !is_object($_stats->stats)) {
                    unset($host_history_cache[$_key][$_time]); // Delete time key.
                    continue; // All done for this time key.
                }
                if ($_time < $host_history_max_age || $_stats->time < $host_history_max_age) {
                    unset($host_history_cache[$_key][$_time]); // Delete time key.
                    continue; // All done for this time key.
                }
            }
            if (!$host_history_cache[$_key]) {
                unset($host_history_cache[$_key]);
            } else { // Newest to oldest sorting + slicer.
                krsort($host_history_cache[$_key], SORT_NUMERIC);
                $host_history_cache[$_key] = array_slice($host_history_cache[$_key], 0, $host_history_max_time_keys, true);
            }
        }
        unset($_key, $_key_stats, $_time, $_stats); // Housekeeping.

        update_site_option($host_history_cache_key, $host_history_cache);
    }

    /**
     * Wipes directory stats cache.
     *
     * @since 151002 Directory stats.
     *
     * @param bool $include_child_blogs Include?
     */
    public function wipeCache($include_child_blogs = true)
    {
        if ($include_child_blogs && is_multisite()) {
            $wpdb    = $this->plugin->wpdb();
            $site_id = get_current_site()->id;
            $like    = '%'.$wpdb->esc_like($this->cache_key).'%';
            $wpdb->query('DELETE FROM `'.$wpdb->sitemeta.'` WHERE `site_id` = \''.esc_sql($site_id).'\' AND `meta_key` LIKE \''.esc_sql($like).'\'');

            if (is_array($child_blogs = wp_get_sites())) {
                foreach ($child_blogs as $_child_blog) {
                    $_host_cache_key = $this->cacheKeyForBlog($_child_blog['blog_id']);
                    wp_cache_delete($site_id.':'.$_host_cache_key, 'site-options');
                }
                unset($_child_blog, $_host_cache_key); // Housekeeping.
            }
        } // ↓ Even if clearing child blogs.
        // This makes sure the option cache is updated also.
        update_site_option($this->cache_key, array()); // Always.

        // Clear network and current blog.
        // If different; or in case `$include_child_blogs=false`.
        $host_cache_key = $this->cacheKeyForBlog(); // If not the same.
        if ($this->cache_key !== $host_cache_key) { // Clear host?
            update_site_option($host_cache_key, array());
        }
    }

    /**
     * Clear directory stats cache (current host).
     *
     * @since 151002 Directory stats.
     *
     * @param bool $consider_network_cap Consider?
     */
    public function clearHostCache($consider_network_cap = true)
    {
        if ($consider_network_cap && is_multisite() && current_user_can($this->plugin->network_cap)) {
            $this->wipeCache(false); // Wipe network and current blog.
        } else {
            $host_cache_key = $this->cacheKeyForBlog();
            update_site_option($host_cache_key, array());
        }
    }

    /**
     * Wipe directory stats history cache.
     *
     * @since 151002 Directory stats.
     *
     * @param bool $include_child_blogs Include?
     */
    public function wipeHistoryCache($include_child_blogs = true)
    {
        if ($include_child_blogs && is_multisite()) {
            $wpdb    = $this->plugin->wpdb();
            $site_id = get_current_site()->id;
            $like    = '%'.$wpdb->esc_like($this->history_cache_key).'%';
            $wpdb->query('DELETE FROM `'.$wpdb->sitemeta.'` WHERE `site_id` = \''.esc_sql($site_id).'\' AND `meta_key` LIKE \''.esc_sql($like).'\'');

            if (is_array($child_blogs = wp_get_sites())) {
                foreach ($child_blogs as $_child_blog) {
                    $_host_history_cache_key = $this->historyCacheKeyForBlog($_child_blog['blog_id']);
                    wp_cache_delete($site_id.':'.$_host_history_cache_key, 'site-options');
                }
                unset($_child_blog, $_host_history_cache_key); // Housekeeping.
            }
        } // ↓ Even if clearing child blogs.
        // This makes sure the option cache is updated also.
        update_site_option($this->history_cache_key, array()); // Always.

        // Clear network and current blog.
        // If different; or in case `$include_child_blogs=false`.
        $host_history_cache_key = $this->historyCacheKeyForBlog(); // If not the same.
        if ($this->history_cache_key !== $host_history_cache_key) { // Clear host?
            update_site_option($host_history_cache_key, array());
        }
    }

    /**
     * Clear directory stats history cache (current host).
     *
     * @since 151002 Directory stats.
     *
     * @param bool $consider_network_cap Consider?
     */
    public function clearHostHistoryCache($consider_network_cap = true)
    {
        if ($consider_network_cap && is_multisite() && current_user_can($this->plugin->network_cap)) {
            $this->wipeHistoryCache(false); // Wipe network and current blog.
        } else {
            $host_history_cache_key = $this->historyCacheKeyForBlog();
            update_site_option($host_history_cache_key, array());
        }
    }

    /**
     * Cache key for a specific blog ID.
     *
     * @since 151002 Directory stats.
     *
     * @param int $blog_id The blog ID.
     *
     * @return string Cache key for a specific blog ID.
     */
    protected function cacheKeyForBlog($blog_id = 0)
    {
        if (is_multisite()) {
            if (($blog_id = (integer) $blog_id) < 0) {
                $blog_id = (integer) get_current_site()->blog_id;
            }
            if (!$blog_id) {
                $blog_id = (integer) get_current_blog_id();
            }
            return $this->cache_key.'_'.$blog_id;
        }
        return $this->cache_key;
    }

    /**
     * History cache key for a specific blog ID.
     *
     * @since 151002 Directory stats.
     *
     * @param int $blog_id The blog ID.
     *
     * @return string History cache key for a specific blog ID.
     */
    protected function historyCacheKeyForBlog($blog_id = 0)
    {
        if (is_multisite()) {
            if (($blog_id = (integer) $blog_id) < 0) {
                $blog_id = (integer) get_current_site()->blog_id;
            }
            if (!$blog_id) {
                $blog_id = (integer) get_current_blog_id();
            }
            return $this->history_cache_key.'_'.$blog_id;
        }
        return $this->history_cache_key;
    }

    /**
     * Cache directory stats.
     *
     * @since 151002 Adding cache directory statistics.
     *
     * @param bool $no_cache      Do not read a cache entry?
     * @param bool $include_paths Include array of all scanned file paths?
     *
     * @return \stdClass Cache directory stats.
     *
     * @TODO Optimize this for multisite networks w/ a LOT of child blogs.
     * @TODO Optimize this for extremely large sites. A LOT of files here could slow things down.
     *  See also: <https://codex.wordpress.org/Function_Reference/wp_is_large_network>
     */
    public function forCache($no_cache = false, $include_paths = false)
    {
        $cache_key = md5(__FUNCTION__.(integer) $include_paths);

        if (!$no_cache && ($cache = $this->getCache()) && isset($cache[$cache_key])) {
            if (isset($cache[$cache_key]->stats, $cache[$cache_key]->time) && is_object($cache[$cache_key]->stats)
                    && $cache[$cache_key]->stats->total_resources > $this->plugin->options['dir_stats_auto_refresh_max_resources']
                    && $cache[$cache_key]->time >= strtotime('-'.$this->plugin->options['dir_stats_refresh_time'])) {
                return $cache[$cache_key]; // Cached stats.
            }
        } // Otherwise, we need to pull a fresh set of stats.

        $stats = (object) $this->plugin->getDirRegexStats($this->plugin->cacheDir(), '', $include_paths, true, true);
        $stats = (object) array('stats' => $stats, 'time' => time());

        $this->updateCache($cache_key, $stats);

        return $stats;
    }

    /**
     * HTML compressor cache directory stats.
     *
     * @since 151002 Adding cache directory statistics.
     *
     * @param bool $no_cache      Do not read a cache entry?
     * @param bool $include_paths Include array of all scanned file paths?
     *
     * @return \stdClass HTML compressor cache directory stats.
     *
     * @TODO Optimize this for multisite networks w/ a LOT of child blogs.
     * @TODO Optimize this for extremely large sites. A LOT of files here could slow things down.
     *  See also: <https://codex.wordpress.org/Function_Reference/wp_is_large_network>
     */
    public function forHtmlCCache($no_cache = false, $include_paths = false)
    {
        $cache_key = md5(__FUNCTION__.(integer) $include_paths);

        if (!$no_cache && ($cache = $this->getCache()) && isset($cache[$cache_key])) {
            if (isset($cache[$cache_key]->stats, $cache[$cache_key]->time) && is_object($cache[$cache_key]->stats)
                    && $cache[$cache_key]->stats->total_resources > $this->plugin->options['dir_stats_auto_refresh_max_resources']
                    && $cache[$cache_key]->time >= strtotime('-'.$this->plugin->options['dir_stats_refresh_time'])) {
                return $cache[$cache_key]; // Cached stats.
            }
        } // Otherwise, we need to pull a fresh set of stats.

        $stats = new \stdClass(); // Initialize stats object instance.

        $htmlc_cache_dirs   = array(); // Initialize directories.
        $htmlc_cache_dirs[] = $this->plugin->wpContentBaseDirTo($this->plugin->htmlc_cache_sub_dir_public);
        $htmlc_cache_dirs[] = $this->plugin->wpContentBaseDirTo($this->plugin->htmlc_cache_sub_dir_private);

        foreach (array_unique($htmlc_cache_dirs) as $_htmlc_cache_dir) {
            $_check_disk_stats = isset($stats->disk_total_space) ? false : true;

            foreach ($this->plugin->getDirRegexStats($_htmlc_cache_dir, '', $include_paths, $_check_disk_stats, true) as $_key => $_value) {
                if (is_integer($_value) || is_float($_value)) {
                    $stats->{$_key} = isset($stats->{$_key}) ? $stats->{$_key} + $_value : $_value;
                } elseif (is_array($_value)) {
                    $stats->{$_key} = isset($stats->{$_key}) ? array_merge($stats->{$_key}, $_value) : $_value;
                } else {
                    throw new \Exception(__('Unexpected data type.', SLUG_TD));
                }
            }
            unset($_key, $_value); // Housekeeping.
        }
        unset($_htmlc_cache_dir, $_check_disk_stats); // Housekeeping.

        $stats = (object) array('stats' => $stats, 'time' => time());

        $this->updateCache($cache_key, $stats);

        return $stats;
    }

    /**
     * Cache directory stats for the current host.
     *
     * @since 151002 Adding cache directory statistics.
     *
     * @param bool $no_cache                                        Do not read a cache entry?
     * @param bool $include_paths                                   Include array of all scanned file paths?
     * @param bool $___considering_domain_mapping                   For internal use only.
     * @param bool $___consider_domain_mapping_host_token           For internal use only.
     * @param bool $___consider_domain_mapping_host_base_dir_tokens For internal use only.
     *
     * @return \stdClass Cache directory stats for the current host.
     */
    public function forHostCache(
        $no_cache = false,
        $include_paths = false,
        $___considering_domain_mapping = false,
        $___consider_domain_mapping_host_token = null,
        $___consider_domain_mapping_host_base_dir_tokens = null
    ) {
        $cache_key = md5(__FUNCTION__.(integer) $include_paths);
        $no_cache  = $___considering_domain_mapping ? true : $no_cache;

        if (!$no_cache && ($cache = $this->getHostCache()) && isset($cache[$cache_key])) {
            if (isset($cache[$cache_key]->stats, $cache[$cache_key]->time) && is_object($cache[$cache_key]->stats)
                    && $cache[$cache_key]->stats->total_resources > $this->plugin->options['dir_stats_auto_refresh_max_resources']
                    && $cache[$cache_key]->time >= strtotime('-'.$this->plugin->options['dir_stats_refresh_time'])) {
                return $cache[$cache_key]; // Cached stats.
            }
        } // Otherwise, we need to pull a fresh set of stats.

        $cache_dir            = $this->plugin->nDirSeps($this->plugin->cacheDir());
        $host_token           = $current_host_token           = $this->plugin->hostToken();
        $host_base_dir_tokens = $current_host_base_dir_tokens = $this->plugin->hostBaseDirTokens();

        if ($___considering_domain_mapping && isset($___consider_domain_mapping_host_token, $___consider_domain_mapping_host_base_dir_tokens)) {
            $host_token           = (string) $___consider_domain_mapping_host_token;
            $host_base_dir_tokens = (string) $___consider_domain_mapping_host_base_dir_tokens;
        }
        if (!$host_token) { // Must have a host in the sub-routine below.
            throw new \Exception(__('Invalid argument; host token empty!', SLUG_TD));
        }
        $stats = new \stdClass(); // Initialize stats object instance.

        foreach (array('http', 'https') as $_host_scheme) {
            $_host_url              = $_host_scheme.'://'.$host_token.$host_base_dir_tokens;
            $_host_cache_path_flags = CACHE_PATH_NO_PATH_INDEX | CACHE_PATH_NO_QUV | CACHE_PATH_NO_EXT;
            $_host_cache_path       = $this->plugin->buildCachePath($_host_url, '', '', $_host_cache_path_flags);
            $_host_cache_dir        = $this->plugin->nDirSeps($cache_dir.'/'.$_host_cache_path); // Normalize path.
            $_check_disk_stats      = $___considering_domain_mapping || isset($stats->disk_total_space) ? false : true;

            foreach ($this->plugin->getDirRegexStats($_host_cache_dir, '', $include_paths, $_check_disk_stats, true) as $_key => $_value) {
                if (is_integer($_value) || is_float($_value)) {
                    $stats->{$_key} = isset($stats->{$_key}) ? $stats->{$_key} + $_value : $_value;
                } elseif (is_array($_value)) {
                    $stats->{$_key} = isset($stats->{$_key}) ? array_merge($stats->{$_key}, $_value) : $_value;
                } else {
                    throw new \Exception(__('Unexpected data type.', SLUG_TD));
                }
            }
            unset($_key, $_value); // Housekeeping.
        }
        unset($_host_scheme, $_host_url, $_host_cache_path_flags, $_host_cache_path, $_host_cache_dir, $_check_disk_stats);

        if (!$___considering_domain_mapping && is_multisite() && $this->plugin->canConsiderDomainMapping()) {
            $domain_mapping_variations = array(); // Initialize array of domain variations.

            if (($_host_token_for_blog = $this->plugin->hostTokenForBlog())) {
                $_host_base_dir_tokens_for_blog = $this->plugin->hostBaseDirTokensForBlog();
                $domain_mapping_variations[]    = array('host_token' => $_host_token_for_blog, 'host_base_dir_tokens' => $_host_base_dir_tokens_for_blog);
            } // The original blog host; i.e., without domain mapping.
            unset($_host_token_for_blog, $_host_base_dir_tokens_for_blog); // Housekeeping.

            foreach ($this->plugin->domainMappingBlogDomains() as $_domain_mapping_blog_domain) {
                if (($_domain_host_token_for_blog = $this->plugin->hostTokenForBlog(false, true, $_domain_mapping_blog_domain))) {
                    $_domain_host_base_dir_tokens_for_blog = $this->plugin->hostBaseDirTokensForBlog(false, true); // This is only a formality.
                    $domain_mapping_variations[]           = array('host_token' => $_domain_host_token_for_blog, 'host_base_dir_tokens' => $_domain_host_base_dir_tokens_for_blog);
                }
            } // This includes all of the domain mappings configured for the current blog ID.
            unset($_domain_mapping_blog_domain, $_domain_host_token_for_blog, $_domain_host_base_dir_tokens_for_blog); // Housekeeping.

            foreach ($domain_mapping_variations as $_domain_mapping_variation) {
                if ($_domain_mapping_variation['host_token'] === $current_host_token && $_domain_mapping_variation['host_base_dir_tokens'] === $current_host_base_dir_tokens) {
                    continue; // Exclude current tokens. They were already iterated above.
                }
                foreach ($this->forHostCache(true, $include_paths, true, $_domain_mapping_variation['host_token'], $_domain_mapping_variation['host_base_dir_tokens'])->stats as $_key => $_value) {
                    if (is_integer($_value) || is_float($_value)) {
                        $stats->{$_key} = isset($stats->{$_key}) ? $stats->{$_key} + $_value : $_value;
                    } elseif (is_array($_value)) {
                        $stats->{$_key} = isset($stats->{$_key}) ? array_merge($stats->{$_key}, $_value) : $_value;
                    } else {
                        throw new \Exception(__('Unexpected data type.', SLUG_TD));
                    }
                }
                unset($_key, $_value); // Housekeeping.
            }
            unset($_domain_mapping_variation); // Housekeeping.
        }
        $stats = (object) array('stats' => $stats, 'time' => time());

        if (!$___considering_domain_mapping) {
            $this->updateHostCache($cache_key, $stats);
        }
        return $stats;
    }

    /**
     * HTML compressor cache directory stats for the current host.
     *
     * @since 151002 Adding cache directory statistics.
     *
     * @param bool $no_cache      Do not read a cache entry?
     * @param bool $include_paths Include array of all scanned file paths?
     *
     * @return \stdClass HTML compressor cache directory stats for the current host.
     */
    public function forHtmlCHostCache($no_cache = false, $include_paths = false)
    {
        $cache_key = md5(__FUNCTION__.(integer) $include_paths);

        if (!$no_cache && ($cache = $this->getHostCache()) && isset($cache[$cache_key])) {
            if (isset($cache[$cache_key]->stats, $cache[$cache_key]->time) && is_object($cache[$cache_key]->stats)
                    && $cache[$cache_key]->stats->total_resources > $this->plugin->options['dir_stats_auto_refresh_max_resources']
                    && $cache[$cache_key]->time >= strtotime('-'.$this->plugin->options['dir_stats_refresh_time'])) {
                return $cache[$cache_key]; // Cached stats.
            }
        } // Otherwise, we need to pull a fresh set of stats.

        $stats = new \stdClass(); // Initialize stats object instance.

        $host_token           = $this->plugin->hostToken(true); // Dashify.
        $host_base_dir_tokens = $this->plugin->hostBaseDirTokens(true); // Dashify.

        $htmlc_cache_dirs   = array(); // Initialize array of all HTML Compressor directories to clear.
        $htmlc_cache_dirs[] = $this->plugin->wpContentBaseDirTo($this->plugin->htmlc_cache_sub_dir_public.rtrim($host_base_dir_tokens, '/').'/'.$host_token);
        $htmlc_cache_dirs[] = $this->plugin->wpContentBaseDirTo($this->plugin->htmlc_cache_sub_dir_private.rtrim($host_base_dir_tokens, '/').'/'.$host_token);

        if (is_multisite() && $this->plugin->canConsiderDomainMapping()) {
            if (($_host_token_for_blog = $this->plugin->hostTokenForBlog(true))) { // Dashify.
                $_host_base_dir_tokens_for_blog = $this->plugin->hostBaseDirTokensForBlog(true); // Dashify.
                $htmlc_cache_dirs[]             = $this->plugin->wpContentBaseDirTo($this->plugin->htmlc_cache_sub_dir_public.rtrim($_host_base_dir_tokens_for_blog, '/').'/'.$_host_token_for_blog);
                $htmlc_cache_dirs[]             = $this->plugin->wpContentBaseDirTo($this->plugin->htmlc_cache_sub_dir_private.rtrim($_host_base_dir_tokens_for_blog, '/').'/'.$_host_token_for_blog);
            }
            unset($_host_token_for_blog, $_host_base_dir_tokens_for_blog); // Housekeeping.

            foreach ($this->plugin->domainMappingBlogDomains() as $_domain_mapping_blog_domain) {
                if (($_domain_host_token_for_blog = $this->plugin->hostTokenForBlog(true, true, $_domain_mapping_blog_domain))) { // Dashify.
                    $_domain_host_base_dir_tokens_for_blog = $this->plugin->hostBaseDirTokensForBlog(true, true); // Dashify. This is only a formality.
                    $htmlc_cache_dirs[]                    = $this->plugin->wpContentBaseDirTo($this->plugin->htmlc_cache_sub_dir_public.rtrim($_domain_host_base_dir_tokens_for_blog, '/').'/'.$_domain_host_token_for_blog);
                    $htmlc_cache_dirs[]                    = $this->plugin->wpContentBaseDirTo($this->plugin->htmlc_cache_sub_dir_private.rtrim($_domain_host_base_dir_tokens_for_blog, '/').'/'.$_domain_host_token_for_blog);
                }
            }
            unset($_domain_mapping_blog_domain, $_domain_host_token_for_blog, $_domain_host_base_dir_tokens_for_blog); // Housekeeping.
        }
        foreach (array_unique($htmlc_cache_dirs) as $_htmlc_cache_dir) {
            $_check_disk_stats = isset($stats->disk_total_space) ? false : true;

            foreach ($this->plugin->getDirRegexStats($_htmlc_cache_dir, '', $include_paths, $_check_disk_stats, true) as $_key => $_value) {
                if (is_integer($_value) || is_float($_value)) {
                    $stats->{$_key} = isset($stats->{$_key}) ? $stats->{$_key} + $_value : $_value;
                } elseif (is_array($_value)) {
                    $stats->{$_key} = isset($stats->{$_key}) ? array_merge($stats->{$_key}, $_value) : $_value;
                } else {
                    throw new \Exception(__('Unexpected data type.', SLUG_TD));
                }
            }
            unset($_key, $_value); // Housekeeping.
        }
        unset($_htmlc_cache_dir, $_check_disk_stats); // Just a little housekeeping.

        $stats = (object) array('stats' => $stats, 'time' => time());

        $this->updateHostCache($cache_key, $stats);

        return $stats;
    }

    /**
     * Largest cache size in last X days.
     *
     * @since 151002 Adding cache directory statistics.
     *
     * @param int $last_x_days Last X days (optional).
     *
     * @return \stdClass Largest cache size in last X days.
     */
    public function largestCacheSize($last_x_days = null)
    {
        if (!is_integer($last_x_days) || $last_x_days <= 0) {
            $last_x_days = max(1, $this->plugin->options['dir_stats_history_days']);
        }
        $largest_size  = 0; // Initialize.
        $largest_sizes = array(); // Initialize.

        $history_cache   = $this->getHistoryCache();
        $history_max_age = strtotime('-'.$last_x_days.' days');

        foreach ($this->allowed_history_cache_keys as $_key) {
            if (empty($history_cache[$_key]) || !is_array($history_cache[$_key])) {
                continue; // Not possible at this time.
            }
            $largest_sizes[$_key] = 0; // Initialize largest size for this key.

            foreach ($history_cache[$_key] as $_time => $_stats) { // Each time in this key.
                if (!$_time || !isset($_stats->stats, $_stats->time) || !is_object($_stats->stats)) {
                    continue; // Not possible w/ these stats.
                }
                if ($_time >= $history_max_age && $_stats->time >= $history_max_age && isset($_stats->stats->total_size)) {
                    $largest_sizes[$_key] = max($largest_sizes[$_key], $_stats->stats->total_size);
                }
            }
            $largest_size += $largest_sizes[$_key]; // Collectively.
        }
        unset($_key, $_time, $_stats); // Housekeeping.

        return (object) array('days' => $last_x_days, 'size' => $largest_size);
    }

    /**
     * Largest cache size in last X days (current host).
     *
     * @since 151002 Adding cache directory statistics.
     *
     * @param int $last_x_days Last X days (optional).
     *
     * @return \stdClass Largest cache size in last X days (current host).
     */
    public function largestHostCacheSize($last_x_days = null)
    {
        if (!is_integer($last_x_days) || $last_x_days <= 0) {
            $last_x_days = max(1, $this->plugin->options['dir_stats_history_days']);
        }
        $largest_size  = 0; // Initialize.
        $largest_sizes = array(); // Initialize.

        $host_history_cache   = $this->getHostHistoryCache();
        $host_history_max_age = strtotime('-'.$last_x_days.' days');

        foreach ($this->allowed_host_history_cache_keys as $_key) {
            if (empty($host_history_cache[$_key]) || !is_array($host_history_cache[$_key])) {
                continue; // Not possible at this time.
            }
            $largest_sizes[$_key] = 0; // Initialize largest size for this key.

            foreach ($host_history_cache[$_key] as $_time => $_stats) { // Each time in this key.
                if (!$_time || !isset($_stats->stats, $_stats->time) || !is_object($_stats->stats)) {
                    continue; // Not possible w/ these stats.
                }
                if ($_time >= $host_history_max_age && $_stats->time >= $host_history_max_age && isset($_stats->stats->total_size)) {
                    $largest_sizes[$_key] = max($largest_sizes[$_key], $_stats->stats->total_size);
                }
            }
            $largest_size += $largest_sizes[$_key]; // Collectively.
        }
        unset($_key, $_time, $_stats); // Housekeeping.

        return (object) array('days' => $last_x_days, 'size' => $largest_size);
    }

    /**
     * Largest cache count in last X days.
     *
     * @since 151002 Adding cache directory statistics.
     *
     * @param int $last_x_days Last X days (optional).
     *
     * @return \stdClass Largest cache count in last X days.
     */
    public function largestCacheCount($last_x_days = null)
    {
        if (!is_integer($last_x_days) || $last_x_days <= 0) {
            $last_x_days = max(1, $this->plugin->options['dir_stats_history_days']);
        }
        $largest_count  = 0; // Initialize.
        $largest_counts = array(); // Initialize.

        $history_cache   = $this->getHistoryCache();
        $history_max_age = strtotime('-'.$last_x_days.' days');

        foreach ($this->allowed_history_cache_keys as $_key) {
            if (empty($history_cache[$_key]) || !is_array($history_cache[$_key])) {
                continue; // Not possible at this time.
            }
            $largest_counts[$_key] = 0; // Initialize largest count for this key.

            foreach ($history_cache[$_key] as $_time => $_stats) { // Each time in this key.
                if (!$_time || !isset($_stats->stats, $_stats->time) || !is_object($_stats->stats)) {
                    continue; // Not possible w/ these stats.
                }
                if ($_time >= $history_max_age && $_stats->time >= $history_max_age && isset($_stats->stats->total_links_files)) {
                    $largest_counts[$_key] = max($largest_counts[$_key], $_stats->stats->total_links_files);
                }
            }
            $largest_count += $largest_counts[$_key]; // Collectively.
        }
        unset($_key, $_time, $_stats); // Housekeeping.

        return (object) array('days' => $last_x_days, 'count' => $largest_count);
    }

    /**
     * Largest cache count in last X days (current host).
     *
     * @since 151002 Adding cache directory statistics.
     *
     * @param int $last_x_days Last X days (optional).
     *
     * @return \stdClass Largest cache count in last X days (current host).
     */
    public function largestHostCacheCount($last_x_days = null)
    {
        if (!is_integer($last_x_days) || $last_x_days <= 0) {
            $last_x_days = max(1, $this->plugin->options['dir_stats_history_days']);
        }
        $largest_count  = 0; // Initialize.
        $largest_counts = array(); // Initialize.

        $host_history_cache   = $this->getHostHistoryCache();
        $host_history_max_age = strtotime('-'.$last_x_days.' days');

        foreach ($this->allowed_host_history_cache_keys as $_key) {
            if (empty($host_history_cache[$_key]) || !is_array($host_history_cache[$_key])) {
                continue; // Not possible at this time.
            }
            $largest_counts[$_key] = 0; // Initialize largest count for this key.

            foreach ($host_history_cache[$_key] as $_time => $_stats) { // Each time in this key.
                if (!$_time || !isset($_stats->stats, $_stats->time) || !is_object($_stats->stats)) {
                    continue; // Not possible w/ these stats.
                }
                if ($_time >= $host_history_max_age && $_stats->time >= $host_history_max_age && isset($_stats->stats->total_links_files)) {
                    $largest_counts[$_key] = max($largest_counts[$_key], $_stats->stats->total_links_files);
                }
            }
            $largest_count += $largest_counts[$_key]; // Collectively.
        }
        unset($_key, $_time, $_stats); // Housekeeping.

        return (object) array('days' => $last_x_days, 'count' => $largest_count);
    }
}
/*[/pro]*/
