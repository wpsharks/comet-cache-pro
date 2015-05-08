<?php
namespace WebSharks\ZenCache\Pro;

/*
 * Wipes out all cache files in the cache directory.
 *
 * @since 150422 Rewrite.
 *
 * @param bool   $manually      Defaults to a `FALSE` value.
 *                              Pass as TRUE if the wipe is done manually by the site owner.
 * @param string $also_wipe_dir Defaults to an empty string.
 *                              By default (i.e. when this is empty) we only wipe {@link $cache_sub_dir} files.
 *
 * @throws \Exception If a wipe failure occurs.
 *
 * @return int Total files wiped by this routine (if any).
 */
$self->wipeCache = function ($manually = false, $also_wipe_dir = '') use ($self) {
    $counter = 0; // Initialize.

    $also_wipe_dir = trim((string) $also_wipe_dir);

    if (!$manually && $this->disableAutoWipeCacheRoutines()) {
        return $counter; // Nothing to do.
    }
    @set_time_limit(1800); // @TODO Display a warning.

    if (is_dir($cache_dir = $this->cacheDir())) {
        $counter += $this->deleteAllFilesDirsIn($cache_dir);
    }
    if ($also_wipe_dir && is_dir($also_wipe_dir)) {
        $counter += $this->deleteAllFilesDirsIn($also_wipe_dir);
    }
    $counter += $this->wipeHtmlCCache($manually);

    return $counter;
};

/*
 * Wipes out all HTML Compressor cache files.
 *
 * @since 150422 Rewrite.
 *
 * @param bool $manually Defaults to a `FALSE` value.
 *                       Pass as TRUE if the wiping is done manually by the site owner.
 *
 * @throws \Exception If a wipe failure occurs.
 *
 * @return int Total files wiped by this routine (if any).
 */
$self->wipeHtmlCCache = function ($manually = false) use ($self) {
    $counter = 0; // Initialize.

    if (!$manually && $this->disableAutoWipeCacheRoutines()) {
        return $counter; // Nothing to do.
    }
    @set_time_limit(1800); // @TODO Display a warning.

    $htmlc_cache_dirs[] = $this->wpContentBaseDirTo($this->htmlc_cache_sub_dir_public);
    $htmlc_cache_dirs[] = $this->wpContentBaseDirTo($this->htmlc_cache_sub_dir_private);

    foreach ($htmlc_cache_dirs as $_htmlc_cache_dir) {
        $counter += $this->deleteAllFilesDirsIn($_htmlc_cache_dir);
    }
    unset($_htmlc_cache_dir); // Just a little housekeeping.

    return $counter;
};

/*
 * Clears cache files for the current host|blog.
 *
 * @since 150422 Rewrite.
 *
 * @param bool $manually Defaults to a `FALSE` value.
 *                       Pass as TRUE if the clearing is done manually by the site owner.
 *
 * @throws \Exception If a clearing failure occurs.
 *
 * @return int Total files cleared by this routine (if any).
 */
$self->clearCache = function ($manually = false) use ($self) {
    $counter = 0; // Initialize.

    if (!$manually && $this->disableAutoClearCacheRoutines()) {
        return $counter; // Nothing to do.
    }
    if (!is_dir($cache_dir = $this->cacheDir())) {
        return ($counter += $this->clearHtmlCCache($manually));
    }
    @set_time_limit(1800); // @TODO Display a warning.

    $regex = $this->buildHostCachePathRegex('', '.+');
    $counter += $this->clearFilesFromHostCacheDir($regex);
    $counter += $this->clearHtmlCCache($manually);

    return $counter;
};

/*
 * Clear all HTML Compressor cache files for the current blog.
 *
 * @since 150422 Rewrite.
 *
 * @param bool $manually Defaults to a `FALSE` value.
 *                       Pass as TRUE if the clearing is done manually by the site owner.
 *
 * @throws \Exception If a clearing failure occurs.
 *
 * @return int Total files cleared by this routine (if any).
 */
$self->clearHtmlCCache = function ($manually = false) use ($self) {
    $counter = 0; // Initialize.

    if (!$manually && $this->disableAutoClearCacheRoutines()) {
        return $counter; // Nothing to do.
    }
    @set_time_limit(1800); // @TODO Display a warning.

    $host_token = $this->hostToken(true);
    if (($host_dir_token = $this->hostDirToken(true)) === '/') {
        $host_dir_token = ''; // Not necessary in this case.
    }
    $htmlc_cache_dirs[] = $this->wpContentBaseDirTo($this->htmlc_cache_sub_dir_public.$host_dir_token.'/'.$host_token);
    $htmlc_cache_dirs[] = $this->wpContentBaseDirTo($this->htmlc_cache_sub_dir_private.$host_dir_token.'/'.$host_token);

    foreach ($htmlc_cache_dirs as $_htmlc_cache_dir) {
        $counter += $this->deleteAllFilesDirsIn($_htmlc_cache_dir);
    }
    unset($_htmlc_cache_dir); // Just a little housekeeping.

    return $counter;
};

/*
 * Purges expired cache files for the current host|blog.
 *
 * @since 150422 Rewrite.
 *
 * @param bool $manually Defaults to a `FALSE` value.
 *                       Pass as TRUE if the purging is done manually by the site owner.
 *
 * @throws \Exception If a purge failure occurs.
 *
 * @return int Total files purged by this routine (if any).
 *
 * @attaches-to `'_cron_'.__NAMESPACE__.'_cleanup'` via CRON job.
 */
$self->purgeCache = function ($manually = false) use ($self) {
    $counter = 0; // Initialize.

    if (!is_dir($cache_dir = $this->cacheDir())) {
        return $counter; // Nothing to do.
    }
    @set_time_limit(1800); // @TODO Display a warning.

    $regex = $this->buildHostCachePathRegex('', '.+');
    $counter += $this->purgeFilesFromHostCacheDir($regex);

    return $counter;
};

/*
 * Automatically wipes out all cache files in the cache directory.
 *
 * @since 150422 Rewrite.
 *
 * @return int Total files wiped by this routine (if any).
 *
 * @note Unlike many of the other `auto_` methods, this one is NOT currently attached to any hooks.
 *    This is called upon whenever QC options are saved and/or restored though.
 */
$self->autoWipeCache = function () use ($self) {
    $counter = 0; // Initialize.

    if (!is_null($done = &$self->cacheKey('autoWipeCache'))) {
        return $counter; // Already did this.
    }
    $done = true; // Flag as having been done.

    if (!$this->options['enable']) {
        return $counter; // Nothing to do.
    }
    if ($this->disableAutoWipeCacheRoutines()) {
        return $counter; // Nothing to do.
    }
    $counter += $this->wipeCache();

    if ($counter && is_admin() && $this->options['change_notifications_enable']) {
        $this->enqueueNotice('<img src="'.esc_attr($this->url('/client-s/images/wipe.png')).'" style="float:left; margin:0 10px 0 0; border:0;" />'.
                              sprintf(__('<strong>%1$s:</strong> detected significant changes. Found %2$s in the cache; auto-wiping.', $this->text_domain), esc_html($this->name), esc_html($this->i18nFiles($counter))));
    }
    return $counter;
};

/*
 * Allows a site owner to disable the wipe cache routines.
 *
 * This is done by filtering `zencache_disable_auto_wipe_cache_routines` to return TRUE,
 *    in which case this method returns TRUE, otherwise it returns FALSE.
 *
 * @since 150422 Rewrite.
 *
 * @return bool `TRUE` if disabled; and this also creates a dashboard notice in some cases.
 */
$self->disableAutoWipeCacheRoutines = function () use ($self) {
    $is_disabled = (boolean) $this->applyWpFilters(GLOBAL_NS.'_disable_auto_wipe_cache_routines', false);

    if ($is_disabled && is_admin() && $this->options['change_notifications_enable']) {
        $this->enqueueNotice('<img src="'.esc_attr($this->url('/client-s/images/clear.png')).'" style="float:left; margin:0 10px 0 0; border:0;" />'.
                              sprintf(__('<strong>%1$s:</strong> detected significant changes that would normally trigger a wipe cache routine, however wipe cache routines have been disabled by a site administrator. [<a href="http://zencache.com/r/kb-clear-and-wipe-cache-routines/" target="_blank">?</a>]', $this->text_domain), esc_html($this->name)));
    }
    return $is_disabled;
};

/*
 * Automatically clears all cache files for the current blog.
 *
 * @attaches-to `switch_theme` hook.
 *
 * @attaches-to `wp_create_nav_menu` hook.
 * @attaches-to `wp_update_nav_menu` hook.
 * @attaches-to `wp_delete_nav_menu` hook.
 *
 * @attaches-to `create_term` hook.
 * @attaches-to `edit_terms` hook.
 * @attaches-to `delete_term` hook.
 *
 * @attaches-to `add_link` hook.
 * @attaches-to `edit_link` hook.
 * @attaches-to `delete_link` hook.
 *
 * @since 150422 Rewrite.
 *
 * @return int Total files cleared by this routine (if any).
 *
 * @note This is also called upon during plugin activation.
 */
$self->autoClearCache = function () use ($self) {
    $counter = 0; // Initialize.

    if (!is_null($done = &$self->cacheKey('autoClearCache'))) {
        return $counter; // Already did this.
    }
    $done = true; // Flag as having been done.

    if (!$this->options['enable']) {
        return $counter; // Nothing to do.
    }
    if ($this->disableAutoClearCacheRoutines()) {
        return $counter; // Nothing to do.
    }
    $counter += $this->clearCache();

    if ($counter && is_admin() && $this->options['change_notifications_enable']) {
        $this->enqueueNotice('<img src="'.esc_attr($this->url('/client-s/images/clear.png')).'" style="float:left; margin:0 10px 0 0; border:0;" />'.
                              sprintf(__('<strong>%1$s:</strong> detected important site changes. Found %2$s in the cache for this site; auto-clearing.', $this->text_domain), esc_html($this->name), esc_html($this->i18nFiles($counter))));
    }
    return $counter;
};

/*
 * Allows a site owner to disable the clear and wipe cache routines.
 *
 * This is done by filtering `zencache_disable_auto_clear_cache_routines` to return TRUE,
 *    in which case this method returns TRUE, otherwise it returns FALSE.
 *
 * @since 150422 Rewrite.
 *
 * @return bool `TRUE` if disabled; and this also creates a dashboard notice in some cases.
 */
$self->disableAutoClearCacheRoutines = function () use ($self) {
    $is_disabled = (boolean) $this->applyWpFilters(GLOBAL_NS.'_disable_auto_clear_cache_routines', false);

    if ($is_disabled && is_admin() && $this->options['change_notifications_enable']) {
        $this->enqueueNotice('<img src="'.esc_attr($this->url('/client-s/images/clear.png')).'" style="float:left; margin:0 10px 0 0; border:0;" />'.
                              sprintf(__('<strong>%1$s:</strong> detected important site changes that would normally trigger a clear cache routine. However, clear cache routines have been disabled by a site administrator. [<a href="http://zencache.com/r/kb-clear-and-wipe-cache-routines/" target="_blank">?</a>]', $this->text_domain), esc_html($this->name)));
    }
    return $is_disabled;
};

/*
 * Automatically clears cache files for a particular post.
 *
 * @attaches-to `save_post` hook.
 * @attaches-to `delete_post` hook.
 * @attaches-to `clean_post_cache` hook.
 *
 * @since 150422 Rewrite.
 *
 * @param int  $post_id A WordPress post ID.
 * @param bool $force   Defaults to a `FALSE` value.
 *                      Pass as TRUE if clearing should be done for `draft`, `pending`,
 *                      `future`, or `trash` post statuses.
 *
 * @throws \Exception If a clear failure occurs.
 *
 * @return int Total files cleared by this routine (if any).
 *
 *
 * @note This is also called upon by other routines which listen for
 *    events that are indirectly associated with a post ID.
 */
$self->autoClearPostCache = function ($post_id, $force = false) use ($self) {
    $counter = 0; // Initialize.

    if (!($post_id = (integer) $post_id)) {
        return $counter; // Nothing to do.
    }
    if (isset($this->cache[__FUNCTION__][$post_id][(integer) $force])) {
        return $counter; // Already did this.
    }
    $this->cache[__FUNCTION__][$post_id][(integer) $force] = -1;

    if (isset(static::$static['___allow_auto_clear_post_cache']) && static::$static['___allow_auto_clear_post_cache'] === false) {
        static::$static['___allow_auto_clear_post_cache'] = true; // Reset state.
        return $counter; // Nothing to do.
    }
    if (!$this->options['enable']) {
        return $counter; // Nothing to do.
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $counter; // Nothing to do.
    }
    if (!is_dir($cache_dir = $this->cache_dir())) {
        return $counter; // Nothing to do.
    }
    if (!empty($this->pre_post_update_post_permalink[$post_id]) && ($permalink = $this->pre_post_update_post_permalink[$post_id])) {
        $this->pre_post_update_post_permalink[$post_id] = ''; // Reset; only used for post status transitions
    } elseif (!($permalink = get_permalink($post_id))) {
        return $counter; // Nothing we can do.
    }
    if (!($post_status = get_post_status($post_id))) {
        return $counter; // Nothing to do.
    }
    if ($post_status === 'auto-draft') {
        return $counter; // Nothing to do.
    }
    if ($post_status === 'draft' && !$force) {
        return $counter; // Nothing to do.
    }
    if ($post_status === 'pending' && !$force) {
        return $counter; // Nothing to do.
    }
    if ($post_status === 'future' && !$force) {
        return $counter; // Nothing to do.
    }
    if ($post_status === 'trash' && !$force) {
        return $counter; // Nothing to do.
    }
    if (($type = get_post_type($post_id)) && ($type = get_post_type_object($type)) && !empty($type->labels->singular_name)) {
        $type_singular_name = $type->labels->singular_name; // Singular name for the post type.
    } else {
        $type_singular_name = __('Post', $this->text_domain); // Default value.
    }
    $regex = $this->build_host_cache_path_regex($permalink);
    $counter += $this->clear_files_from_host_cache_dir($regex);

    if ($counter && is_admin() && $this->options['change_notifications_enable']) {
        $this->enqueue_notice('<img src="'.esc_attr($this->url('/client-s/images/clear.png')).'" style="float:left; margin:0 10px 0 0; border:0;" />'.
                              sprintf(__('<strong>%1$s:</strong> detected changes. Found %2$s in the cache for %3$s ID: <code>%4$s</code>; auto-clearing.', $this->text_domain),
                                      esc_html($this->name), esc_html($this->i18n_files($counter)), esc_html($type_singular_name), esc_html($post_id)));
    }
    $counter += $this->autoClearXmlFeedsCache('blog');
    $counter += $this->autoClearXmlFeedsCache('post-terms', $post_id);
    $counter += $this->autoClearXmlFeedsCache('post-authors', $post_id);

    $counter += $this->autoClearXmlSitemapsCache();
    $counter += $this->autoClearHomePageCache();
    $counter += $this->autoClearPostsPageCache();
    $counter += $this->autoClearPostTermsCache($post_id, $force);
    $counter += $this->autoClearCustomPostTypeArchiveCache($post_id);

    return $counter;
};

/*
 * Automatically clears cache files for a particular post when transitioning
 *    from `publish` or `private` post status to `draft`, `future`, `private`, or `trash`.
 *
 * @attaches-to `pre_post_update` hook.
 *
 * @since 150422 Rewrite.
 *
 * @param int   $post_ID Post ID.
 * @param array $data    Array of unslashed post data.
 *
 * @throws \Exception If a clear failure occurs.
 *
 * @return int Total files cleared by this routine (if any).
 *
 *
 * @note This is also called upon by other routines which listen for
 *    events that are indirectly associated with a post ID.
 *
 * @see auto_clear_post_cache()
 */
$self->autoClearPostCacheTransition = function ($post_ID, $data) use ($self) {
    $old_status = (string) get_post_status($post_ID);
    $new_status = (string) $data['post_status'];

    /*
     * When a post has a status of `pending` or `draft`, the `get_permalink()` function
     * does not return a friendly permalink and therefore `auto_clear_post_cache()` will
     * have no way of building a path to the cache file that should be cleared as part of
     * this post status transition. To get around this, we temporarily store the permalink
     * in $this->pre_post_update_post_permalink for `auto_clear_post_cache()` to use.
     *
     * See also: https://github.com/websharks/zencache/issues/441
     */
    if ($old_status === 'publish' && in_array($data['post_status'], array('pending', 'draft', true), true)) {
        $this->pre_post_update_post_permalink[$post_ID] = get_permalink($post_ID);
    }

    $counter = 0; // Initialize.

    if (isset($this->cache[__FUNCTION__][$new_status][$old_status][$post_ID])) {
        return $counter;
    } // Already did this.
    $this->cache[__FUNCTION__][$new_status][$old_status][$post_ID] = -1;

    if (!$this->options['enable']) {
        return $counter;
    } // Nothing to do.

    if ($old_status !== 'publish' && $old_status !== 'private') {
        return $counter;
    } // Nothing to do. We MUST be transitioning FROM one of these statuses.

    if (in_array($new_status, array('draft', 'future', 'pending', 'private', 'trash'), true)) {
        $counter = $this->autoClearPostCache($post_ID, true);
    }

    return $this->apply_wp_filters(__METHOD__, $counter, get_defined_vars());
};

/*
 * Automatically clears cache files related to XML feeds.
 *
 * @since 150422 Rewrite.
 *
 * @param string $type    Type of feed(s) to auto-clear.
 * @param int    $post_id A Post ID (when applicable).
 *
 * @throws \Exception If a clear failure occurs.
 *
 * @return int Total files cleared by this routine (if any).
 *
 *
 * @note Unlike many of the other `auto_` methods, this one is NOT currently
 *    attached to any hooks. However, it is called upon by other routines attached to hooks.
 */
$self->autoClearXmlFeedsCache = function ($type, $post_id = 0) use ($self) {
    $counter = 0; // Initialize.

    if (!($type = (string) $type)) {
        return $counter;
    } // Nothing we can do.
    $post_id = (integer) $post_id; // Force integer.

    if (isset($this->cache[__FUNCTION__][$type][$post_id])) {
        return $counter;
    } // Already did this.
    $this->cache[__FUNCTION__][$type][$post_id] = -1;

    if (!$this->options['enable']) {
        return $counter;
    } // Nothing to do.

    if (!$this->options['feeds_enable']) {
        return $counter;
    } // Nothing to do.

    if (!$this->options['cache_clear_xml_feeds_enable']) {
        return $counter;
    } // Nothing to do.

    if (!is_dir($cache_dir = $this->cache_dir())) {
        return $counter;
    } // Nothing to do.

    $variations = $variation_regex_frags = array(); // Initialize.
    require_once dirname(__FILE__).'/includes/utils-feed.php';
    $utils = new utils_feed(); // Feed utilities.

    switch ($type) {// Handle clearing based on the `$type`.

        case 'blog': // The blog feed; i.e. `/feed/` on most WP installs.

            $variations = array_merge($variations, $utils->feed_link_variations());
            break; // Break switch handler.

        case 'blog-comments': // The blog comments feed; i.e. `/comments/feed/` on most WP installs.

            $variations = array_merge($variations, $utils->feed_link_variations('comments_'));
            break; // Break switch handler.

        case 'post-comments': // Feeds related to comments that a post has.

            if (!$post_id) {
                break;
            } // Nothing to do.
            if (!($post = get_post($post_id))) {
                break;
            }
            $variations = array_merge($variations, $utils->post_comments_feed_link_variations($post));
            break; // Break switch handler.

        case 'post-authors': // Feeds related to authors that a post has.

            if (!$post_id) {
                break;
            } // Nothing to do.
            if (!($post = get_post($post_id))) {
                break;
            }
            $variations = array_merge($variations, $utils->post_author_feed_link_variations($post));
            break; // Break switch handler.

        case 'post-terms': // Feeds related to terms that a post has.

            if (!$post_id) {
                break;
            } // Nothing to do.
            if (!($post = get_post($post_id))) {
                break;
            }
            $variations = array_merge($variations, $utils->post_term_feed_link_variations($post, true));
            break; // Break switch handler.

        case 'custom-post-type': // Feeds related to a custom post type archive view.

            if (!$post_id) {
                break;
            } // Nothing to do.
            if (!($post = get_post($post_id))) {
                break;
            }
            $variations = array_merge($variations, $utils->post_type_archive_link_variations($post));
            break; // Break switch handler.

        // @TODO Possibly consider search-related feeds in the future.
        //    See: <http://codex.wordpress.org/WordPress_Feeds#Categories_and_Tags>
    }
    $variation_regex_frags = $utils->convert_variations_to_host_cache_path_regex_frags($variations);

    if (!$variation_regex_frags // Have regex pattern variations?
       || !($variation_regex_frags = array_unique($variation_regex_frags))
    ) {
        return $counter;
    } // Nothing to do here.

    $in_sets_of = $this->apply_wp_filters(__METHOD__.'__in_sets_of', 10, get_defined_vars());
    for ($_i = 0; $_i < count($variation_regex_frags); $_i = $_i + $in_sets_of) {
        $_variation_regex_frags = array_slice($variation_regex_frags, $_i, $in_sets_of);
        $_regex                 = '/^\/(?:'.implode('|', $_variation_regex_frags).')\./i';
        $counter += $this->clear_files_from_host_cache_dir($_regex);
    }
    unset($_i, $_variation_regex_frags, $_regex); // Housekeeping.

    if ($counter && is_admin() && $this->options['change_notifications_enable']) {
        $this->enqueue_notice('<img src="'.esc_attr($this->url('/client-s/images/clear.png')).'" style="float:left; margin:0 10px 0 0; border:0;" />'.
                              sprintf(__('<strong>%1$s:</strong> detected changes. Found %2$s in the cache, for XML feeds of type: <code>%3$s</code>; auto-clearing.', $this->text_domain),
                                      esc_html($this->name), esc_html($this->i18n_files($counter)), esc_html($type)));
    }
    return $this->apply_wp_filters(__METHOD__, $counter, get_defined_vars());
};

/*
 * Automatically clears cache files related to XML sitemaps.
 *
 * @since 150422 Rewrite.
 *
 * @throws \Exception If a clear failure occurs.
 *
 * @return int Total files cleared by this routine (if any).
 *
 *
 * @note Unlike many of the other `auto_` methods, this one is NOT currently
 *    attached to any hooks. However, it is called upon by {@link auto_clear_post_cache()}.
 *
 * @see auto_clear_post_cache()
 */
$self->autoClearXmlSitemapsCache = function () use ($self) {
    $counter = 0; // Initialize.

    if (isset($this->cache[__FUNCTION__])) {
        return $counter;
    } // Already did this.
    $this->cache[__FUNCTION__] = -1;

    if (!$this->options['enable']) {
        return $counter;
    } // Nothing to do.

    if (!$this->options['cache_clear_xml_sitemaps_enable']) {
        return $counter;
    } // Nothing to do.

    if (!$this->options['cache_clear_xml_sitemap_patterns']) {
        return $counter;
    } // Nothing to do.

    if (!is_dir($cache_dir = $this->cache_dir())) {
        return $counter;
    } // Nothing to do.

    if (!($regex_frags = $this->build_host_cache_path_regex_frags_from_wc_uris($this->options['cache_clear_xml_sitemap_patterns'], ''))) {
        return $counter;
    } // There are no patterns to look for.

    $regex = $this->build_host_cache_path_regex('', '\/'.$regex_frags.'\.');
    $counter += $this->clear_files_from_host_cache_dir($regex);

    if ($counter && is_admin() && $this->options['change_notifications_enable']) {
        $this->enqueue_notice('<img src="'.esc_attr($this->url('/client-s/images/clear.png')).'" style="float:left; margin:0 10px 0 0; border:0;" />'.
                              sprintf(__('<strong>%1$s:</strong> detected changes. Found %2$s in the cache for XML sitemaps; auto-clearing.', $this->text_domain),
                                      esc_html($this->name), esc_html($this->i18n_files($counter))));
    }
    return $this->apply_wp_filters(__METHOD__, $counter, get_defined_vars());
};

/*
 * Automatically clears cache files for the home page.
 *
 * @since 150422 Rewrite.
 *
 * @throws \Exception If a clear failure occurs.
 *
 * @return int Total files cleared by this routine (if any).
 *
 *
 * @note Unlike many of the other `auto_` methods, this one is NOT currently
 *    attached to any hooks. However, it is called upon by {@link auto_clear_post_cache()}.
 *
 * @see auto_clear_post_cache()
 */
$self->autoClearHomePageCache = function () use ($self) {
    $counter = 0; // Initialize.

    if (isset($this->cache[__FUNCTION__])) {
        return $counter;
    } // Already did this.
    $this->cache[__FUNCTION__] = -1;

    if (!$this->options['enable']) {
        return $counter;
    } // Nothing to do.

    if (!$this->options['cache_clear_home_page_enable']) {
        return $counter;
    } // Nothing to do.

    if (!is_dir($cache_dir = $this->cache_dir())) {
        return $counter;
    } // Nothing to do.

    $regex = $this->build_host_cache_path_regex(home_url('/'));
    $counter += $this->clear_files_from_host_cache_dir($regex);

    if ($counter && is_admin() && $this->options['change_notifications_enable']) {
        $this->enqueue_notice('<img src="'.esc_attr($this->url('/client-s/images/clear.png')).'" style="float:left; margin:0 10px 0 0; border:0;" />'.
                              sprintf(__('<strong>%1$s:</strong> detected changes. Found %2$s in the cache for the designated "Home Page"; auto-clearing.', $this->text_domain),
                                      esc_html($this->name), esc_html($this->i18n_files($counter))));
    }
    $counter += $this->autoClearXmlFeedsCache('blog');

    return $this->apply_wp_filters(__METHOD__, $counter, get_defined_vars());
};

/*
 * Automatically clears cache files for the posts page.
 *
 * @since 150422 Rewrite.
 *
 * @throws \Exception If a clear failure occurs.
 *
 * @return int Total files cleared by this routine (if any).
 *
 *
 * @note Unlike many of the other `auto_` methods, this one is NOT currently
 *    attached to any hooks. However, it is called upon by {@link auto_clear_post_cache()}.
 *
 * @see auto_clear_post_cache()
 */
$self->autoClearPostsPageCache = function () use ($self) {
    $counter = 0; // Initialize.

    if (isset($this->cache[__FUNCTION__])) {
        return $counter;
    } // Already did this.
    $this->cache[__FUNCTION__] = -1;

    if (!$this->options['enable']) {
        return $counter;
    } // Nothing to do.

    if (!$this->options['cache_clear_posts_page_enable']) {
        return $counter;
    } // Nothing to do.

    if (!is_dir($cache_dir = $this->cache_dir())) {
        return $counter;
    } // Nothing to do.

    $show_on_front  = get_option('show_on_front');
    $page_for_posts = get_option('page_for_posts');

    if (!in_array($show_on_front, array('posts', 'page'), true)) {
        return $counter;
    } // Nothing we can do in this case.

    if ($show_on_front === 'page' && !$page_for_posts) {
        return $counter;
    } // Nothing we can do.

    if ($show_on_front === 'posts') {
        $posts_page = home_url('/');
    } elseif ($show_on_front === 'page') {
        $posts_page = get_permalink($page_for_posts);
    }
    if (empty($posts_page)) {
        return $counter;
    } // Nothing we can do.

    $regex = $this->build_host_cache_path_regex($posts_page);
    $counter += $this->clear_files_from_host_cache_dir($regex);

    if ($counter && is_admin() && $this->options['change_notifications_enable']) {
        $this->enqueue_notice('<img src="'.esc_attr($this->url('/client-s/images/clear.png')).'" style="float:left; margin:0 10px 0 0; border:0;" />'.
                              sprintf(__('<strong>%1$s:</strong> detected changes. Found %2$s in the cache for the designated "Posts Page"; auto-clearing.', $this->text_domain),
                                      esc_html($this->name), esc_html($this->i18n_files($counter))));
    }
    $counter += $this->autoClearXmlFeedsCache('blog');

    return $this->apply_wp_filters(__METHOD__, $counter, get_defined_vars());
};

/*
 * Automatically clears cache files for a custom post type archive view.
 *
 * @since 150422 Rewrite.
 *
 * @param int $post_id A WordPress post ID.
 *
 * @throws \Exception If a clear failure occurs.
 *
 * @return int Total files cleared by this routine (if any).
 *
 *
 * @note Unlike many of the other `auto_` methods, this one is NOT currently
 *    attached to any hooks. However, it is called upon by {@link auto_clear_post_cache()}.
 *
 * @see auto_clear_post_cache()
 */
$self->autoClearCustomPostTypeArchiveCache = function ($post_id) use ($self) {
    $counter = 0; // Initialize.

    if (!($post_id = (integer) $post_id)) {
        return $counter;
    } // Nothing to do.

    if (isset($this->cache[__FUNCTION__][$post_id])) {
        return $counter;
    } // Already did this.
    $this->cache[__FUNCTION__][$post_id] = -1;

    if (!$this->options['enable']) {
        return $counter;
    } // Nothing to do.

    if (!$this->options['cache_clear_custom_post_type_enable']) {
        return $counter;
    } // Nothing to do.

    if (!is_dir($cache_dir = $this->cache_dir())) {
        return $counter;
    } // Nothing to do.

    if (!($post_type = get_post_type($post_id))) {
        return $counter;
    } // Nothing to do.

    if (!($all_custom_post_types = get_post_types(array('_builtin' => false)))) {
        return $counter;
    } // No custom post types.

    if (!in_array($post_type, array_keys($all_custom_post_types), true)) {
        return $counter;
    } // This is NOT a custom post type.

    if (!($custom_post_type = get_post_type_object($post_type))) {
        return $counter;
    } // Unable to retrieve post type.

    if (empty($custom_post_type->labels->name)
       || !($custom_post_type_name = $custom_post_type->labels->name)
    ) {
        $custom_post_type_name = __('Untitled', $this->text_domain);
    }

    if (!($custom_post_type_archive_link = get_post_type_archive_link($post_type))) {
        return $counter;
    } // Nothing to do; no link to work from in this case.

    $regex = $this->build_host_cache_path_regex($custom_post_type_archive_link);
    $counter += $this->clear_files_from_host_cache_dir($regex);

    if ($counter && is_admin() && $this->options['change_notifications_enable']) {
        $this->enqueue_notice('<img src="'.esc_attr($this->url('/client-s/images/clear.png')).'" style="float:left; margin:0 10px 0 0; border:0;" />'.
                              sprintf(__('<strong>%1$s:</strong> detected changes. Found %2$s in the cache for Custom Post Type: <code>%3$s</code>; auto-clearing.', $this->text_domain),
                                      esc_html($this->name), esc_html($this->i18n_files($counter)), esc_html($custom_post_type_name)));
    }
    $counter += $this->autoClearXmlFeedsCache('custom-post-type', $post_id);

    return $this->apply_wp_filters(__METHOD__, $counter, get_defined_vars());
};

/*
 * Automatically clears cache files for the author page(s).
 *
 * @attaches-to `post_updated` hook.
 *
 * @since 150422 Rewrite.
 *
 * @param int      $post_id     A WordPress post ID.
 * @param \WP_Post $post_after  WP_Post object following the update.
 * @param \WP_Post $post_before WP_Post object before the update.
 *
 * @throws \Exception If a clear failure occurs.
 *
 * @return int Total files cleared by this routine (if any).
 *
 *
 * @note If the author for the post is being changed, both the previous author
 *       and current author pages are cleared, if the post status is applicable.
 */
$self->autoClearAuthorPageCache = function ($post_id, \WP_Post $post_after, \WP_Post $post_before) use ($self) {
    $counter          = 0; // Initialize.
    $enqueued_notices = 0; // Initialize.
    $authors          = array(); // Initialize.
    $authors_to_clear = array(); // Initialize.

    if (!($post_id = (integer) $post_id)) {
        return $counter;
    } // Nothing to do.

    if (isset($this->cache[__FUNCTION__][$post_id][$post_after->ID][$post_before->ID])) {
        return $counter;
    } // Already did this.
    $this->cache[__FUNCTION__][$post_id][$post_after->ID][$post_before->ID] = -1;

    if (!$this->options['enable']) {
        return $counter;
    } // Nothing to do.

    if (!$this->options['cache_clear_author_page_enable']) {
        return $counter;
    } // Nothing to do.

    if (!is_dir($cache_dir = $this->cache_dir())) {
        return $counter;
    } // Nothing to do.
    /*
     * If we're changing the post author AND
     *    the previous post status was either 'published' or 'private'
     * then clear the author page for both authors.
     *
     * Else if the old post status was 'published' or 'private' OR
     *    the new post status is 'published' or 'private'
     * then clear the author page for the current author.
     *
     * Else return the counter; post status does not warrant clearing author page cache.
     */
    if ($post_after->post_author !== $post_before->post_author &&
       ($post_before->post_status === 'publish' || $post_before->post_status === 'private')
    ) {
        // Clear both authors in this case.

        $authors[] = (integer) $post_before->post_author;
        $authors[] = (integer) $post_after->post_author;
    } elseif (($post_before->post_status === 'publish' || $post_before->post_status === 'private') ||
            ($post_after->post_status === 'publish' || $post_after->post_status === 'private')
    ) {
        $authors[] = (integer) $post_after->post_author;
    }

    if (!$authors) {
        // Have no authors to clear?
        return $counter;
    } // Nothing to do.

    foreach ($authors as $_author_id) {
        // Get author posts URL and display name.

        $authors_to_clear[$_author_id]['posts_url']    = get_author_posts_url($_author_id);
        $authors_to_clear[$_author_id]['display_name'] = get_the_author_meta('display_name', $_author_id);
    }
    unset($_author_id); // Housekeeping.

    foreach ($authors_to_clear as $_author) {
        $_author_regex   = $this->build_host_cache_path_regex($_author['posts_url']);
        $_author_counter = $this->clear_files_from_host_cache_dir($_author_regex);
        $counter += $_author_counter; // Add to overall counter.

        if ($_author_counter && $enqueued_notices < 100 && is_admin() && $this->options['change_notifications_enable']) {
            $this->enqueue_notice('<img src="'.esc_attr($this->url('/client-s/images/clear.png')).'" style="float:left; margin:0 10px 0 0; border:0;" />'.
                                  sprintf(__('<strong>%1$s:</strong> detected changes. Found %2$s in the cache for Author Page: <code>%3$s</code>; auto-clearing.', $this->text_domain),
                                          esc_html($this->name), esc_html($this->i18n_files($_author_counter)), esc_html($_author['display_name'])));
            $enqueued_notices++; // Increment enqueued notices counter.
        }
    }
    unset($_author, $_author_regex, $_author_counter); // Housekeeping.

    $counter += $this->autoClearXmlFeedsCache('blog');
    $counter += $this->autoClearXmlFeedsCache('post-authors', $post_id);

    return $this->apply_wp_filters(__METHOD__, $counter, get_defined_vars());
};

/*
 * Automatically clears cache files for terms associated with a post.
 *
 * @attaches-to `added_term_relationship` hook.
 * @attaches-to `delete_term_relationships` hook.
 *
 * @since 150422 Rewrite.
 *
 * @param int  $post_id A WordPress post ID.
 * @param bool $force   Defaults to a `FALSE` value.
 *                      Pass as TRUE if clearing should be done for `draft`, `pending`,
 *                      or `future` post statuses.
 *
 * @throws \Exception If a clear failure occurs.
 *
 * @return int Total files cleared by this routine (if any).
 *
 *
 * @note In addition to the hooks this is attached to, it is also
 *    called upon by {@link auto_clear_post_cache()}.
 *
 * @see auto_clear_post_cache()
 */
$self->autoClearPostTermsCache = function ($post_id, $force = false) use ($self) {
    $counter          = 0; // Initialize.
    $enqueued_notices = 0; // Initialize.

    if (!($post_id = (integer) $post_id)) {
        return $counter;
    } // Nothing to do.

    if (isset($this->cache[__FUNCTION__][$post_id][(integer) $force])) {
        return $counter;
    } // Already did this.
    $this->cache[__FUNCTION__][$post_id][(integer) $force] = -1;

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $counter;
    } // Nothing to do.

    if (!$this->options['enable']) {
        return $counter;
    } // Nothing to do.

    if (!$this->options['cache_clear_term_category_enable'] &&
       !$this->options['cache_clear_term_post_tag_enable'] &&
       !$this->options['cache_clear_term_other_enable']
    ) {
        return $counter;
    } // Nothing to do.

    if (!is_dir($cache_dir = $this->cache_dir())) {
        return $counter;
    } // Nothing to do.

    $post_status = get_post_status($post_id); // Cache this.

    if ($post_status === 'draft' && isset($GLOBALS['pagenow'], $_POST['publish'])
       && is_admin() && $GLOBALS['pagenow'] === 'post.php' && current_user_can('publish_posts')
       && strpos(wp_get_referer(), '/post-new.php') !== false
    ) {
        $post_status = 'publish';
    } // A new post being published now.

    if ($post_status === 'auto-draft') {
        return $counter;
    } // Nothing to do.

    if ($post_status === 'draft' && !$force) {
        return $counter;
    } // Nothing to do.

    if ($post_status === 'pending' && !$force) {
        return $counter;
    } // Nothing to do.

    if ($post_status === 'future' && !$force) {
        return $counter;
    } // Nothing to do.
    /*
     * Build an array of available taxonomies for this post (as taxonomy objects).
     */
    $taxonomies = get_object_taxonomies(get_post($post_id), 'objects');

    if (!is_array($taxonomies)) {
        // No taxonomies?
        return $counter;
    } // Nothing to do.
    /*
     * Build an array of terms associated with this post for each taxonomy.
     * Also save taxonomy label information for Dashboard messaging later.
     */
    $terms           = array();
    $taxonomy_labels = array();

    foreach ($taxonomies as $_taxonomy) {
        if (// Check if this is a taxonomy/term that we should clear.
            ($_taxonomy->name === 'category' && !$this->options['cache_clear_term_category_enable'])
            || ($_taxonomy->name === 'post_tag' && !$this->options['cache_clear_term_post_tag_enable'])
            || ($_taxonomy->name !== 'category' && $_taxonomy->name !== 'post_tag' && !$this->options['cache_clear_term_other_enable'])
        ) {
            continue;
        } // Continue; nothing to do for this taxonomy.

        if (is_array($_terms = wp_get_post_terms($post_id, $_taxonomy->name))) {
            $terms = array_merge($terms, $_terms);

            // Improve Dashboard messaging by getting the Taxonomy label (e.g., "Tag" instead of "post_tag")
            // If we don't have a Singular Name for this taxonomy, use the taxonomy name itself
            if (empty($_taxonomy->labels->singular_name) || $_taxonomy->labels->singular_name === '') {
                $taxonomy_labels[$_taxonomy->name] = $_taxonomy->name;
            } else {
                $taxonomy_labels[$_taxonomy->name] = $_taxonomy->labels->singular_name;
            }
        }
    }
    unset($_taxonomy, $_terms);

    if (empty($terms)) {
        // No taxonomy terms?
        return $counter;
    } // Nothing to do.
    /*
     * Build an array of terms with term names,
     * permalinks, and associated taxonomy labels.
     */
    $terms_to_clear = array();
    $_i             = 0;

    foreach ($terms as $_term) {
        if (($_link = get_term_link($_term))) {
            $terms_to_clear[$_i]['permalink'] = $_link; // E.g., "http://jason.websharks-inc.net/category/uncategorized/"
            $terms_to_clear[$_i]['term_name'] = $_term->name; // E.g., "Uncategorized"
            if (!empty($taxonomy_labels[$_term->taxonomy])) {
                // E.g., "Tag" or "Category"
                $terms_to_clear[$_i]['taxonomy_label'] = $taxonomy_labels[$_term->taxonomy];
            } else {
                $terms_to_clear[$_i]['taxonomy_label'] = $_term->taxonomy;
            } // e.g., "post_tag" or "category"
        }
        $_i++; // Array index counter.
    }
    unset($_term, $_link, $_i);

    if (empty($terms_to_clear)) {
        return $counter;
    } // Nothing to do.

    foreach ($terms_to_clear as $_term) {
        $_term_regex   = $this->build_host_cache_path_regex($_term['permalink']);
        $_term_counter = $this->clear_files_from_host_cache_dir($_term_regex);
        $counter += $_term_counter; // Add to overall counter.

        if ($_term_counter && $enqueued_notices < 100 && is_admin() && $this->options['change_notifications_enable']) {
            $this->enqueue_notice('<img src="'.esc_attr($this->url('/client-s/images/clear.png')).'" style="float:left; margin:0 10px 0 0; border:0;" />'.
                                  sprintf(__('<strong>%1$s:</strong> detected changes. Found %2$s in the cache for %3$s: <code>%4$s</code>; auto-clearing.', $this->text_domain),
                                          esc_html($this->name), esc_html($this->i18n_files($_term_counter)), esc_html($_term['taxonomy_label']), esc_html($_term['term_name'])));
            $enqueued_notices++; // Increment enqueued notices counter.
        }
    }
    unset($_term, $_term_regex, $_term_counter); // Housekeeping.

    $counter += $this->autoClearXmlFeedsCache('post-terms', $post_id);

    return $this->apply_wp_filters(__METHOD__, $counter, get_defined_vars());
};

/*
 * Automatically clears cache files for a post associated with a particular comment.
 *
 * @since 150422 Rewrite.
 *
 * @attaches-to `trackback_post` hook.
 * @attaches-to `pingback_post` hook.
 * @attaches-to `comment_post` hook.
 *
 * @param int $comment_id A WordPress comment ID.
 *
 * @return int Total files cleared by this routine (if any).
 *
 * @see auto_clear_post_cache()
 */
$self->autoClearCommentPostCache = function ($comment_id) use ($self) {
    $counter = 0; // Initialize.

    if (!($comment_id = (integer) $comment_id)) {
        return $counter;
    } // Nothing to do.

    if (isset($this->cache[__FUNCTION__][$comment_id])) {
        return $counter;
    } // Already did this.
    $this->cache[__FUNCTION__][$comment_id] = -1;

    if (!$this->options['enable']) {
        return $counter;
    } // Nothing to do.

    if (!is_object($comment = get_comment($comment_id))) {
        return $counter;
    } // Nothing we can do.

    if (empty($comment->comment_post_ID)) {
        return $counter;
    } // Nothing we can do.

    if ($comment->comment_approved === 'spam' || $comment->comment_approved === '0') {
        // Don't allow next `auto_clear_post_cache()` call to clear post cache.
        // Also, don't allow spam to clear cache.

        static::$static['___allow_auto_clear_post_cache'] = false;
        return $counter; // Nothing to do here.
    }
    $counter += $this->autoClearXmlFeedsCache('blog-comments');
    $counter += $this->autoClearXmlFeedsCache('post-comments', $comment->comment_post_ID);
    $counter += $this->autoClearPostCache($comment->comment_post_ID);

    return $this->apply_wp_filters(__METHOD__, $counter, get_defined_vars());
};

/*
 * Automatically clears cache files for a post associated with a particular comment.
 *
 * @since 150422 Rewrite.
 *
 * @attaches-to `transition_comment_status` hook.
 *
 * @param string   $new_status New comment status.
 * @param string   $old_status Old comment status.
 * @param \WP_Post $comment    Comment object.
 *
 * @throws \Exception If a clear failure occurs.
 *
 * @return int Total files cleared by this routine (if any).
 *
 *
 * @note This is also called upon by other routines which listen for
 *    events that are indirectly associated with a comment ID.
 *
 * @see auto_clear_comment_post_cache()
 */
$self->autoClearCommentTransition = function ($new_status, $old_status, $comment) use ($self) {
    $counter = 0; // Initialize.

    if (!$this->options['enable']) {
        return $counter;
    } // Nothing to do.

    if (!is_object($comment)) {
        return $counter;
    } // Nothing we can do.

    if (empty($comment->comment_post_ID)) {
        return $counter;
    } // Nothing we can do.

    if (!($old_status === 'approved' || ($old_status === 'unapproved' && $new_status === 'approved'))) {
        // If excluded here, don't allow next `auto_clear_post_cache()` call to clear post cache.

        static::$static['___allow_auto_clear_post_cache'] = false;
        return $counter; // Nothing to do here.
    }
    $counter += $this->autoClearXmlFeedsCache('blog-comments');
    $counter += $this->autoClearXmlFeedsCache('post-comments', $comment->comment_post_ID);
    $counter += $this->autoClearPostCache($comment->comment_post_ID);

    return $this->apply_wp_filters(__METHOD__, $counter, get_defined_vars());
};

/*
 * Clears cache files associated with a particular user.
 *
 * @since 150422 Rewrite.
 *
 * @attaches-to `profile_update` hook.
 * @attaches-to `add_user_metadata` filter.
 * @attaches-to `update_user_metadata` filter.
 * @attaches-to `delete_user_metadata` filter.
 * @attaches-to `set_auth_cookie` hook.
 * @attaches-to `clear_auth_cookie` hook.
 *
 * @param int $user_id A WordPress user ID.
 *
 * @return int Total files cleared.
 *
 * @see auto_clear_user_cache_a1()
 * @see auto_clear_user_cache_fa2()
 * @see auto_clear_user_cache_a4()
 * @see auto_clear_user_cache_cur()
 */
$self->autoClearUserCache = function ($user_id) use ($self) {
    $counter = 0; // Initialize.

    if (!($user_id = (integer) $user_id)) {
        return $counter;
    } // Nothing to do.

    if (isset($this->cache[__FUNCTION__][$user_id])) {
        return $counter;
    } // Already did this.
    $this->cache[__FUNCTION__][$user_id] = -1;

    if (!$this->options['enable']) {
        return $counter;
    } // Nothing to do.

    if ($this->options['when_logged_in'] !== 'postload') {
        return $counter;
    } // Nothing to do.

    $regex = $this->build_cache_path_regex('', '.*?\.u\/'.preg_quote($user_id, '/').'[.\/]');
    // NOTE: this clears the cache network-side; for all cache files associated w/ the user.
    $counter += $this->clear_files_from_cache_dir($regex); // Clear matching files.

    if ($counter && is_admin() && $this->options['change_notifications_enable']) {
        $this->enqueue_notice('<img src="'.esc_attr($this->url('/client-s/images/clear.png')).'" style="float:left; margin:0 10px 0 0; border:0;" />'.
                              sprintf(__('<strong>%1$s:</strong> detected changes. Found %2$s in the cache for user ID: <code>%3$s</code>; auto-clearing.', $this->text_domain),
                                      esc_html($this->name), esc_html($this->i18n_files($counter)), esc_html($user_id)));
    }
    return $this->apply_wp_filters(__METHOD__, $counter, get_defined_vars());
};

/*
 * Automatically clears cache files associated with a particular user.
 *
 * @since 150422 Rewrite.
 *
 * @attaches-to `profile_update` hook.
 *
 * @param int $user_id A WordPress user ID.
 *
 * @see auto_clear_user_cache()
 */
$self->autoClearUserCacheA1 = function ($user_id) use ($self) {
    $this->autoClearUserCache($user_id);
};

/*
 * Automatically clears cache files associated with a particular user.
 *
 * @since 150422 Rewrite.
 *
 * @attaches-to `add_user_metadata` filter.
 * @attaches-to `update_user_metadata` filter.
 * @attaches-to `delete_user_metadata` filter.
 *
 * @param mixed $value   Filter value (passes through).
 * @param int   $user_id A WordPress user ID.
 *
 * @return mixed The same `$value` (passes through).
 *
 * @see auto_clear_user_cache()
 */
$self->autoClearUserCacheFA2 = function ($value, $user_id) use ($self) {
    $this->autoClearUserCache($user_id);

    return $value; // Filter.
};

/*
 * Automatically clears cache files associated with a particular user.
 *
 * @since 150422 Rewrite.
 *
 * @attaches-to `set_auth_cookie` hook.
 *
 * @param mixed $_       Irrelevant hook argument value.
 * @param mixed $__      Irrelevant hook argument value.
 * @param mixed $___     Irrelevant hook argument value.
 * @param int   $user_id A WordPress user ID.
 *
 * @see auto_clear_user_cache()
 */
$self->autoClearUserCacheA4 = function ($_, $__, $___, $user_id) use ($self) {
    $this->autoClearUserCache($user_id);
};

/*
 * Automatically clears cache files associated with current user.
 *
 * @since 150422 Rewrite.
 *
 * @attaches-to `clear_auth_cookie` hook.
 *
 * @see auto_clear_user_cache()
 */
$self->autoClearUserCacheCur = function () use ($self) {
    $this->autoClearUserCache(get_current_user_id());
};

/*
 * Automatically clears all cache files for current blog under various conditions;
 *    used to check for conditions that don't have a hook that we can attach to.
 *
 * @since 150422 Rewrite.
 *
 * @attaches-to `admin_init` hook.
 *
 * @see auto_clear_cache()
 */
$self->maybeAutoClearCache = function () use ($self) {
    $_pagenow = $GLOBALS['pagenow'];
    if (isset($this->cache[__FUNCTION__][$_pagenow])) {
        return;
    } // Already did this.
    $this->cache[__FUNCTION__][$_pagenow] = -1;

    // If Dashboard → Settings → General options are updated
    if ($GLOBALS['pagenow'] === 'options-general.php' && !empty($_REQUEST['settings-updated'])) {
        $this->autoClearCache();
    }

    // If Dashboard → Settings → Reading options are updated
    if ($GLOBALS['pagenow'] === 'options-reading.php' && !empty($_REQUEST['settings-updated'])) {
        $this->autoClearCache();
    }

    // If Dashboard → Settings → Discussion options are updated
    if ($GLOBALS['pagenow'] === 'options-discussion.php' && !empty($_REQUEST['settings-updated'])) {
        $this->autoClearCache();
    }

    // If Dashboard → Settings → Permalink options are updated
    if ($GLOBALS['pagenow'] === 'options-permalink.php' && !empty($_REQUEST['settings-updated'])) {
        $this->autoClearCache();
    }
};

/*
 * Automatically clears all cache files for current blog when JetPack Custom CSS is saved.
 *
 * @since 150422 Rewrite.
 *
 * @attaches-to `safecss_save_pre` hook.
 *
 * @param array $args Args passed in by hook.
 *
 * @see auto_clear_cache()
 */
$self->autoClearJetpackCustomCssCache = function ($args) use ($self) {
    if (class_exists('\\Jetpack') && empty($args['is_preview'])) {
        $this->autoClearCache();
    }
};

/*
 * Automatically clears all cache files for current blog when WordPress core, or an active component, is upgraded.
 *
 * @since 150422 Rewrite.
 *
 * @attaches-to `upgrader_process_complete` hook.
 *
 * @param \WP_Upgrader $upgrader_instance An instance of \WP_Upgrader.
 *                                        Or, any class that extends \WP_Upgrader.
 * @param array        $data              Array of bulk item update data.
 *
 *    This array may include one or more of the following keys:
 *
 *       - `string` `$action` Type of action. Default 'update'.
 *       - `string` `$type` Type of update process; e.g. 'plugin', 'theme', 'core'.
 *       - `boolean` `$bulk` Whether the update process is a bulk update. Default true.
 *       - `array` `$packages` Array of plugin, theme, or core packages to update.
 *
 * @see auto_clear_cache()
 */
$self->autoClearOnUpgraderProcessComplete = function (\WP_Upgrader $upgrader_instance, array $data) use ($self) {
    switch (!empty($data['type']) ? $data['type'] : '') {
        case 'plugin': // Plugin upgrade.

            /** @type $skin \Plugin_Upgrader_Skin * */
            $skin                    = $upgrader_instance->skin;
            $multi_plugin_update     = $single_plugin_update = false;
            $upgrading_active_plugin = false; // Initialize.

            if (!empty($data['bulk']) && !empty($data['plugins']) && is_array($data['plugins'])) {
                $multi_plugin_update = true;
            } elseif (!empty($data['plugin']) && is_string($data['plugin'])) {
                $single_plugin_update = true;
            }

            if ($multi_plugin_update) {
                foreach ($data['plugins'] as $_plugin) {
                    if ($_plugin && is_string($_plugin) && is_plugin_active($_plugin)) {
                        $upgrading_active_plugin = true;
                        break; // Got what we need here.
                    }
                }
                unset($_plugin); // Housekeeping.
            } elseif ($single_plugin_update && $skin->plugin_active === true) {
                $upgrading_active_plugin = true;
            }

            if ($upgrading_active_plugin) {
                $this->autoClearCache();
            } // Yes, clear the cache.

            break; // Break switch.

        case 'theme': // Theme upgrade.

            $current_active_theme          = wp_get_theme();
            $current_active_theme_parent   = $current_active_theme->parent();
            $multi_theme_update            = $single_theme_update = false;
            $upgrading_active_parent_theme = $upgrading_active_theme = false;

            if (!empty($data['bulk']) && !empty($data['themes']) && is_array($data['themes'])) {
                $multi_theme_update = true;
            } elseif (!empty($data['theme']) && is_string($data['theme'])) {
                $single_theme_update = true;
            }

            if ($multi_theme_update) {
                foreach ($data['themes'] as $_theme) {
                    if (!$_theme || !is_string($_theme) || !($_theme_obj = wp_get_theme($_theme))) {
                        continue;
                    } // Unable to acquire theme object instance.

                    if ($current_active_theme_parent && $current_active_theme_parent->get_stylesheet() === $_theme_obj->get_stylesheet()) {
                        $upgrading_active_parent_theme = true;
                        break; // Got what we needed here.
                    } elseif ($current_active_theme->get_stylesheet() === $_theme_obj->get_stylesheet()) {
                        $upgrading_active_theme = true;
                        break; // Got what we needed here.
                    }
                }
                unset($_theme, $_theme_obj); // Housekeeping.
            } elseif ($single_theme_update && ($_theme_obj = wp_get_theme($data['theme']))) {
                if ($current_active_theme_parent && $current_active_theme_parent->get_stylesheet() === $_theme_obj->get_stylesheet()) {
                    $upgrading_active_parent_theme = true;
                } elseif ($current_active_theme->get_stylesheet() === $_theme_obj->get_stylesheet()) {
                    $upgrading_active_theme = true;
                }
            }
            unset($_theme_obj); // Housekeeping.

            if ($upgrading_active_theme || $upgrading_active_parent_theme) {
                $this->autoClearCache();
            } // Yes, clear the cache.

            break; // Break switch.

        case 'core': // Core upgrade.
        default: // Or any other sort of upgrade.

            $this->autoClearCache(); // Yes, clear the cache.

            break; // Break switch.
    }
};
