<?php
namespace WebSharks\ZenCache\Pro;

/**
 * Utilities.
 *
 * @since 150422 Rewrite.
 */
class InstallUtils extends AbsBase
{
    /**
     * Plugin activation hook.
     *
     * @since 140422 First documented version.
     *
     * @attaches-to {@link \register_activation_hook()}
     */
    public function activate()
    {
        $this->setup(); // Setup routines.

        if (!$this->options['enable']) {
            return; // Nothing to do.
        }
        $this->add_wp_cache_to_wp_config();
        $this->add_advanced_cache();
        $this->update_blog_paths();
        $this->auto_clear_cache();
    }

    /**
     * Check current plugin version that installed in WP.
     *
     * @since 140422 First documented version.
     *
     * @attaches-to `admin_init` hook.
     */
    public function check_version()
    {
        $current_version = $prev_version = $this->options['version'];
        if (version_compare($current_version, $this->version, '>=')) {
            return; // Nothing to do; we've already upgraded them.
        }
        $current_version = $this->options['version'] = $this->version;
        update_option(__NAMESPACE__.'_options', $this->options); // Updates version.
        if (is_multisite()) {
            update_site_option(__NAMESPACE__.'_options', $this->options);
        }
        new VsUpgrades($prev_version);

        if ($this->options['enable']) {
            $this->add_wp_cache_to_wp_config();
            $this->add_advanced_cache();
            $this->update_blog_paths();
        }
        $this->wipe_cache(); // Always wipe the cache; unless disabled by site owner; @see disable_wipe_cache_routines()

        $this->enqueue_notice(sprintf(__('<strong>%1$s:</strong> detected a new version of itself. Recompiling w/ latest version... wiping the cache... all done :-)', $this->text_domain), esc_html($this->name)), '', true);
    }

    /**
     * Plugin deactivation hook.
     *
     * @since 140422 First documented version.
     *
     * @attaches-to {@link \register_deactivation_hook()}
     */
    public function deactivate()
    {
        $this->setup(); // Setup routines.

        $this->remove_wp_cache_from_wp_config();
        $this->remove_advanced_cache();
        $this->clear_cache();
    }

    /**
     * Plugin uninstall hook.
     *
     * @since 140829 Adding uninstall handler.
     *
     * @attaches-to {@link \register_uninstall_hook()} ~ via {@link uninstall()}
     */
    public function uninstall()
    {
        $this->setup(); // Setup routines.

        if (!defined('WP_UNINSTALL_PLUGIN')) {
            return;
        } // Disallow.

        if (empty($GLOBALS[__NAMESPACE__.'_uninstalling'])) {
            return;
        } // Not uninstalling.

        if (!class_exists('\\'.__NAMESPACE__.'\\uninstall')) {
            return;
        } // Expecting the uninstall class.

        if (!current_user_can($this->uninstall_cap)) {
            return;
        } // Extra layer of security.

        $this->remove_wp_cache_from_wp_config();
        $this->remove_advanced_cache();
        $this->wipe_cache();

        if (!$this->options['uninstall_on_deletion']) {
            return;
        } // Nothing to do here.

        $this->delete_advanced_cache();
        $this->remove_base_dir();

        delete_option(__NAMESPACE__.'_options');
        if (is_multisite()) {
            // Delete network options too.
            delete_site_option(__NAMESPACE__.'_options');
        }

        delete_option(__NAMESPACE__.'_notices');
        delete_option(__NAMESPACE__.'_errors');

        wp_clear_scheduled_hook('_cron_'.__NAMESPACE__.'_auto_cache');
        wp_clear_scheduled_hook('_cron_'.__NAMESPACE__.'_cleanup');
    }
    
    /**
     * Adds `define('WP_CACHE', TRUE);` to the `/wp-config.php` file.
     *
     * @since 140422 First documented version.
     *
     * @return string The new contents of the updated `/wp-config.php` file;
     *                else an empty string if unable to add the `WP_CACHE` constant.
     */
    public function add_wp_cache_to_wp_config()
    {
        if (!$this->options['enable']) {
            return '';
        } // Nothing to do.

        if (!($wp_config_file = $this->find_wp_config_file())) {
            return '';
        } // Unable to find `/wp-config.php`.

        if (!is_readable($wp_config_file)) {
            return '';
        } // Not possible.
        if (!($wp_config_file_contents = file_get_contents($wp_config_file))) {
            return '';
        } // Failure; could not read file.

        if (preg_match('/define\s*\(\s*([\'"])WP_CACHE\\1\s*,\s*(?:\-?[1-9][0-9\.]*|TRUE|([\'"])(?:[^0\'"]|[^\'"]{2,})\\2)\s*\)\s*;/i', $wp_config_file_contents)) {
            return $wp_config_file_contents;
        } // It's already in there; no need to modify this file.

        if (!($wp_config_file_contents = $this->remove_wp_cache_from_wp_config())) {
            return '';
        } // Unable to remove previous value.

        if (!($wp_config_file_contents = preg_replace('/^\s*(\<\?php|\<\?)\s+/i', '${1}'."\n"."define('WP_CACHE', TRUE);"."\n", $wp_config_file_contents, 1))) {
            return '';
        } // Failure; something went terribly wrong here.

        if (strpos($wp_config_file_contents, "define('WP_CACHE', TRUE);") === false) {
            return '';
        } // Failure; unable to add; unexpected PHP code.

        if (defined('DISALLOW_FILE_MODS') && DISALLOW_FILE_MODS) {
            return '';
        } // We may NOT edit any files.

        if (!is_writable($wp_config_file)) {
            return '';
        } // Not possible.
        if (!file_put_contents($wp_config_file, $wp_config_file_contents)) {
            return '';
        } // Failure; could not write changes.

        return $this->apply_wp_filters(__METHOD__, $wp_config_file_contents, get_defined_vars());
    }

    /**
     * Removes `define('WP_CACHE', TRUE);` from the `/wp-config.php` file.
     *
     * @since 140422 First documented version.
     *
     * @return string The new contents of the updated `/wp-config.php` file;
     *                else an empty string if unable to remove the `WP_CACHE` constant.
     */
    public function remove_wp_cache_from_wp_config()
    {
        if (!($wp_config_file = $this->find_wp_config_file())) {
            return '';
        } // Unable to find `/wp-config.php`.

        if (!is_readable($wp_config_file)) {
            return '';
        } // Not possible.
        if (!($wp_config_file_contents = file_get_contents($wp_config_file))) {
            return '';
        } // Failure; could not read file.

        if (!preg_match('/([\'"])WP_CACHE\\1/i', $wp_config_file_contents)) {
            return $wp_config_file_contents;
        } // Already gone.

        if (preg_match('/define\s*\(\s*([\'"])WP_CACHE\\1\s*,\s*(?:0|FALSE|NULL|([\'"])0?\\2)\s*\)\s*;/i', $wp_config_file_contents)) {
            return $wp_config_file_contents;
        } // It's already disabled; no need to modify this file.

        if (!($wp_config_file_contents = preg_replace('/define\s*\(\s*([\'"])WP_CACHE\\1\s*,\s*(?:\-?[0-9\.]+|TRUE|FALSE|NULL|([\'"])[^\'"]*\\2)\s*\)\s*;/i', '', $wp_config_file_contents))) {
            return '';
        } // Failure; something went terribly wrong here.

        if (preg_match('/([\'"])WP_CACHE\\1/i', $wp_config_file_contents)) {
            return '';
        } // Failure; perhaps the `/wp-config.php` file contains syntax we cannot remove safely.

        if (defined('DISALLOW_FILE_MODS') && DISALLOW_FILE_MODS) {
            return '';
        } // We may NOT edit any files.

        if (!is_writable($wp_config_file)) {
            return '';
        } // Not possible.
        if (!file_put_contents($wp_config_file, $wp_config_file_contents)) {
            return '';
        } // Failure; could not write changes.

        return $this->apply_wp_filters(__METHOD__, $wp_config_file_contents, get_defined_vars());
    }

    /**
     * Checks to make sure the `zc-advanced-cache` file still exists;
     *    and if it doesn't, the `advanced-cache.php` is regenerated automatically.
     *
     * @since 140422 First documented version.
     *
     * @attaches-to `init` hook.
     *
     * @note This runs so that remote deployments which completely wipe out an
     *    existing set of website files (like the AWS Elastic Beanstalk does) will NOT cause ZenCache
     *    to stop functioning due to the lack of an `advanced-cache.php` file, which is generated by ZenCache.
     *
     *    For instance, if you have a Git repo with all of your site files; when you push those files
     *    to your website to deploy them, you most likely do NOT have the `advanced-cache.php` file.
     *    ZenCache creates this file on its own. Thus, if it's missing (and QC is active)
     *    we simply regenerate the file automatically to keep ZenCache running.
     */
    public function check_advanced_cache()
    {
        if (!$this->options['enable']) {
            return;
        } // Nothing to do.

        if (!empty($_REQUEST[__NAMESPACE__])) {
            return;
        } // Skip on plugin actions.

        $cache_dir           = $this->cache_dir(); // Current cache directory.
        $advanced_cache_file = WP_CONTENT_DIR.'/advanced-cache.php';

        // Fixes zero-byte advanced-cache.php bug related to migrating from Quick Cache
        // See https://github.com/websharks/zencache/issues/432
        // Also fixes a missing define('WP_CACHE', TRUE) bug related to migrating from Quick Cache
        // See https://github.com/websharks/zencache/issues/450
        if (!is_file($cache_dir.'/zc-advanced-cache')
           || !is_file($advanced_cache_file) || filesize($advanced_cache_file) === 0
        ) {
            $this->add_advanced_cache();
            $this->add_wp_cache_to_wp_config();
        }
    }

    /**
     * Creates and adds the `advanced-cache.php` file.
     *
     * @since 140422 First documented version.
     *
     * @note Many of the ZenCache option values become PHP Constants in the `advanced-cache.php` file.
     *    We take an option key (e.g. `version_salt`) and prefix it with `zencache_`.
     *    Then we convert it to uppercase (e.g. `ZENCACHE_VERSION_SALT`) and wrap
     *    it with double percent signs to form a replacement codes.
     *    ex: `%%ZENCACHE_VERSION_SALT%%`
     *
     * @note There are a few special options considered by this routine which actually
     *    get converted to regex patterns before they become replacement codes.
     *
     * @note In the case of a version salt, a PHP syntax is performed also.
     *
     * @return bool|null `TRUE` on success. `FALSE` or `NULL` on failure.
     *                   A special `NULL` return value indicates success with a single failure
     *                   that is specifically related to the `zc-advanced-cache` file.
     */
    public function add_advanced_cache()
    {
        if (!$this->remove_advanced_cache()) {
            return false;
        } // Still exists.

        $cache_dir               = $this->cache_dir();
        $advanced_cache_file     = WP_CONTENT_DIR.'/advanced-cache.php';
        $advanced_cache_template = dirname(__FILE__).'/includes/advanced-cache.tpl.php';

        if (is_file($advanced_cache_file) && !is_writable($advanced_cache_file)) {
            return false;
        } // Not possible to create.

        if (!is_file($advanced_cache_file) && !is_writable(dirname($advanced_cache_file))) {
            return false;
        } // Not possible to create.

        if (!is_file($advanced_cache_template) || !is_readable($advanced_cache_template)) {
            return false;
        } // Template file is missing; or not readable.

        if (!($advanced_cache_contents = file_get_contents($advanced_cache_template))) {
            return false;
        } // Template file is missing; or is not readable.

        $possible_advanced_cache_constant_key_values = array_merge(
            $this->options, // The following additional keys are dynamic.
            array('cache_dir'               => $this->base_path_to($this->cache_sub_dir),
                  'htmlc_cache_dir_public'  => $this->base_path_to($this->htmlc_cache_sub_dir_public),
                  'htmlc_cache_dir_private' => $this->base_path_to($this->htmlc_cache_sub_dir_private),
            ));
        foreach ($possible_advanced_cache_constant_key_values as $_option => $_value) {
            $_value = (string) $_value; // Force string.

            switch ($_option) {// Some values need tranformations.

                case 'exclude_uris': // Converts to regex (caSe insensitive).
                case 'exclude_refs': // Converts to regex (caSe insensitive).
                case 'exclude_agents': // Converts to regex (caSe insensitive).

                case 'htmlc_css_exclusions': // Converts to regex (caSe insensitive).
                case 'htmlc_js_exclusions': // Converts to regex (caSe insensitive).

                    if (($_values = preg_split('/['."\r\n".']+/', $_value, null, PREG_SPLIT_NO_EMPTY))) {
                        $_value = '/(?:'.implode('|', array_map(function ($string) {
                                $string = preg_quote($string, '/'); // Escape.
                                return preg_replace('/\\\\\*/', '.*?', $string); // Wildcards.

                            }, $_values)).')/i';
                    }
                    $_value = "'".$this->esc_sq($_value)."'";

                    break; // Break switch handler.

                case 'version_salt': // This is PHP code; and we MUST validate syntax.

                    if ($_value && !is_wp_error($_response = wp_remote_post('http://phpcodechecker.com/api/', array('body' => array('code' => $_value))))
                       && is_object($_response = json_decode(wp_remote_retrieve_body($_response))) && !empty($_response->errors) && strcasecmp($_response->errors, 'true') === 0
                    ) {
                        // We will NOT include a version salt if the syntax contains errors reported by this web service.

                        $_value = ''; // PHP syntax errors; empty this.
                        $this->enqueue_error(sprintf(__('<strong>%1$s</strong>: ignoring your Version Salt; it seems to contain PHP syntax errors.', $this->text_domain), esc_html($this->name)));
                    }
                    if (!$_value) {
                        $_value = "''";
                    } // Use an empty string (default).

                    break; // Break switch handler.

                default: // Default case handler.

                    $_value = "'".$this->esc_sq($_value)."'";

                    break; // Break switch handler.
            }
            $advanced_cache_contents = // Fill replacement codes.
                str_ireplace(array("'%%".__NAMESPACE__.'_'.$_option."%%'",
                                   "'%%".__NAMESPACE__.'_'.preg_replace('/^cache_/i', '', $_option)."%%'", ),
                             $_value, $advanced_cache_contents);
        }
        unset($_option, $_value, $_values, $_response); // Housekeeping.

        if (strpos($this->file, WP_CONTENT_DIR) === 0) {
            $plugin_file = "WP_CONTENT_DIR.'".$this->esc_sq(str_replace(WP_CONTENT_DIR, '', $this->file))."'";
        } else {
            $plugin_file = "'".$this->esc_sq($this->file)."'";
        } // Else use full absolute path.
        // Make it possible for the `advanced-cache.php` handler to find the plugin directory reliably.
        $advanced_cache_contents = str_ireplace("'%%".__NAMESPACE__."_PLUGIN_FILE%%'", $plugin_file, $advanced_cache_contents);

        // Ignore; this is created by ZenCache; and we don't need to obey in this case.
        #if(defined('DISALLOW_FILE_MODS') && DISALLOW_FILE_MODS)
        #	return FALSE; // We may NOT edit any files.

        if (!file_put_contents($advanced_cache_file, $advanced_cache_contents)) {
            return false;
        } // Failure; could not write file.

        $cache_lock = $this->cache_lock(); // Lock cache.

        if (!is_dir($cache_dir)) {
            mkdir($cache_dir, 0775, true);
        }

        if (is_writable($cache_dir) && !is_file($cache_dir.'/.htaccess')) {
            file_put_contents($cache_dir.'/.htaccess', $this->htaccess_deny);
        }

        if (!is_dir($cache_dir) || !is_writable($cache_dir) || !is_file($cache_dir.'/.htaccess') || !file_put_contents($cache_dir.'/zc-advanced-cache', time())) {
            $this->cache_unlock($cache_lock); // Unlock cache.
            return; // Special return value (NULL) in this case.
        }
        $this->cache_unlock($cache_lock); // Unlock cache.

        return true; // Success!
    }

    /**
     * Removes the `advanced-cache.php` file.
     *
     * @since 140422 First documented version.
     *
     * @return bool `TRUE` on success. `FALSE` on failure.
     *
     * @note The `advanced-cache.php` file is NOT actually deleted by this routine.
     *    Instead of deleting the file, we simply empty it out so that it's `0` bytes in size.
     *
     *    The reason for this is to preserve any file permissions set by the site owner.
     *    If the site owner previously allowed this specific file to become writable, we don't want to
     *    lose that permission by deleting the file; forcing the site owner to do it all over again later.
     *
     *    An example of where this is useful is when a site owner deactivates the QC plugin,
     *    but later they decide that QC really is the most awesome plugin in the world and they turn it back on.
     *
     * @see delete_advanced_cache()
     */
    public function remove_advanced_cache()
    {
        $advanced_cache_file = WP_CONTENT_DIR.'/advanced-cache.php';

        if (!is_file($advanced_cache_file)) {
            return true;
        } // Already gone.

        if (is_readable($advanced_cache_file) && filesize($advanced_cache_file) === 0) {
            return true;
        } // Already gone; i.e. it's empty already.

        if (!is_writable($advanced_cache_file)) {
            return false;
        } // Not possible.

        // Ignore; this is created by ZenCache; and we don't need to obey in this case.
        #if(defined('DISALLOW_FILE_MODS') && DISALLOW_FILE_MODS)
        #	return FALSE; // We may NOT edit any files.

        /* Empty the file only. This way permissions are NOT lost in cases where
            a site owner makes this specific file writable for ZenCache. */
        if (file_put_contents($advanced_cache_file, '') !== 0) {
            return false;
        } // Failure.

        return true; // Removal success.
    }

    /**
     * Deletes the `advanced-cache.php` file.
     *
     * @since 140422 First documented version.
     *
     * @return bool `TRUE` on success. `FALSE` on failure.
     *
     * @note The `advanced-cache.php` file is deleted by this routine.
     *
     * @see remove_advanced_cache()
     */
    public function delete_advanced_cache()
    {
        $advanced_cache_file = WP_CONTENT_DIR.'/advanced-cache.php';

        if (!is_file($advanced_cache_file)) {
            return true;
        } // Already gone.

        // Ignore; this is created by ZenCache; and we don't need to obey in this case.
        #if(defined('DISALLOW_FILE_MODS') && DISALLOW_FILE_MODS)
        #	return FALSE; // We may NOT edit any files.

        if (!is_writable($advanced_cache_file) || !unlink($advanced_cache_file)) {
            return false;
        } // Not possible; or outright failure.

        return true; // Deletion success.
    }

    /**
     * Checks to make sure the `zc-blog-paths` file still exists;
     *    and if it doesn't, the `zc-blog-paths` file is regenerated automatically.
     *
     * @since 140422 First documented version.
     *
     * @attaches-to `init` hook.
     *
     * @note This runs so that remote deployments which completely wipe out an
     *    existing set of website files (like the AWS Elastic Beanstalk does) will NOT cause ZenCache
     *    to stop functioning due to the lack of a `zc-blog-paths` file, which is generated by ZenCache.
     *
     *    For instance, if you have a Git repo with all of your site files; when you push those files
     *    to your website to deploy them, you most likely do NOT have the `zc-blog-paths` file.
     *    ZenCache creates this file on its own. Thus, if it's missing (and QC is active)
     *    we simply regenerate the file automatically to keep ZenCache running.
     */
    public function check_blog_paths()
    {
        if (!$this->options['enable']) {
            return;
        } // Nothing to do.

        if (!is_multisite()) {
            return;
        } // N/A.

        if (!empty($_REQUEST[__NAMESPACE__])) {
            return;
        } // Skip on plugin actions.

        $cache_dir = $this->cache_dir(); // Current cache directory.

        if (!is_file($cache_dir.'/zc-blog-paths')) {
            $this->update_blog_paths();
        }
    }

    /**
     * Creates and/or updates the `zc-blog-paths` file.
     *
     * @since 140422 First documented version.
     *
     * @attaches-to `enable_live_network_counts` filter.
     *
     * @param mixed $enable_live_network_counts Optional, defaults to a `NULL` value.
     *
     * @return mixed The value of `$enable_live_network_counts` (passes through).
     *
     * @note While this routine is attached to a WP filter, we also call upon it directly at times.
     */
    public function update_blog_paths($enable_live_network_counts = null)
    {
        $value = // This hook actually rides on a filter.
            $enable_live_network_counts; // Filter value.

        if (!$this->options['enable']) {
            return $value;
        } // Nothing to do.

        if (!is_multisite()) {
            return $value;
        } // N/A.

        $cache_dir  = $this->cache_dir(); // Cache dir.
        $cache_lock = $this->cache_lock(); // Lock.

        if (!is_dir($cache_dir)) {
            mkdir($cache_dir, 0775, true);
        }

        if (is_writable($cache_dir) && !is_file($cache_dir.'/.htaccess')) {
            file_put_contents($cache_dir.'/.htaccess', $this->htaccess_deny);
        }

        if (is_dir($cache_dir) && is_writable($cache_dir)) {
            $paths = // Collect child blog paths from the WordPress database.
                $this->wpdb()->get_col('SELECT `path` FROM `'.esc_sql($this->wpdb()->blogs)."` WHERE `deleted` <= '0'");

            foreach ($paths as &$_path) {
                // Strip base; these need to match `$host_dir_token`.
                $_path = '/'.ltrim(preg_replace('/^'.preg_quote($this->host_base_token(), '/').'/', '', $_path), '/');
            }
            unset($_path); // Housekeeping.

            file_put_contents($cache_dir.'/zc-blog-paths', serialize($paths));
        }
        $this->cache_unlock($cache_lock); // Unlock cache directory.

        return $value; // Pass through untouched (always).
    }

    /**
     * Removes the entire base directory.
     *
     * @since 140422 First documented version.
     *
     * @return int Total files removed by this routine (if any).
     */
    public function remove_base_dir()
    {
        $counter = 0; // Initialize.

        @set_time_limit(1800); // @TODO When disabled, display a warning.

        return ($counter += $this->delete_all_files_dirs_in($this->wp_content_base_dir_to(''), true));
    }
}
