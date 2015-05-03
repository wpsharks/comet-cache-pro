<?php
namespace zencache // Root namespace.
{
	if(!defined('WPINC')) // MUST have WordPress.
		exit('Do NOT access this file directly: '.basename(__FILE__));

	if(!class_exists('\\'.__NAMESPACE__.'\\share'))
	{
		/**
		 * ZenCache (Shared Methods)
		 *
		 * @package zencache\share
		 * @since 140725 Reorganizing class members.
		 */
		abstract class share // Shared between {@link advanced_cache} and {@link plugin}.
		{
			/* --------------------------------------------------------------------------------------
			 * Class properties.
			 -------------------------------------------------------------------------------------- */

			/**
			 * Name of this plugin.
			 *
			 * @since 150218 Refactoring.
			 *
			 * @var string Plugin name.
			 */
			public $name = 'ZenCache';

			/**
			 * Short name for this plugin.
			 *
			 * @since 150218 Refactoring.
			 *
			 * @var string Short name for this plugin.
			 */
			public $short_name = 'ZC';

			/**
			 * Domain name for this plugin.
			 *
			 * @since 150218 Refactoring.
			 *
			 * @var string Domain name for this plugin.
			 */
			public $domain = 'zencache.com';

			/**
			 * Identifies pro version of ZenCache.
			 *
			 * @since 140422 First documented version.
			 *
			 * @var boolean `TRUE` for ZenCache Pro.
			 */
			public $is_pro = TRUE;

			/**
			 * Version string in YYMMDD[+build] format.
			 *
			 * @since 140422 First documented version.
			 *
			 * @var string Current version of the software.
			 */
			public $version = '150409';

			/**
			 * Plugin slug; based on `__NAMESPACE__`.
			 *
			 * @since 150218 Refactoring.
			 */
			public $slug = '';

			/**
			 * Text domain for translations; based on `__NAMESPACE__`.
			 *
			 * @since 140422 First documented version.
			 *
			 * @var string Defined by class constructor; for translations.
			 */
			public $text_domain = '';

			/**
			 * An instance-based cache for class members.
			 *
			 * @since 140725 Reorganizing class members.
			 *
			 * @var array An instance-based cache for class members.
			 */
			public $cache = array();

			/**
			 * A global static cache for class members.
			 *
			 * @since 140725 Reorganizing class members.
			 *
			 * @var array Global static cache for class members.
			 */
			public static $static = array();

			/**
			 * Array of hooks added by plugins.
			 *
			 * @since 140422 First documented version.
			 *
			 * @var array An array of any hooks added by plugins.
			 */
			public $hooks = array();

			/**
			 * Flag indicating the current user login cookie is expired or invalid.
			 *
			 * @since 140429 Improving user cache handlers.
			 *
			 * @var boolean `TRUE` if current user login cookie is expired or invalid.
			 *    See also {@link user_token()} and {@link advanced_cache::maybe_start_ob_when_logged_in_postload()}.
			 */
			public $user_login_cookie_expired_or_invalid = FALSE;

			/* --------------------------------------------------------------------------------------
			 * Shared constructor.
			 -------------------------------------------------------------------------------------- */

			/**
			 * Class constructor.
			 *
			 * @since 140422 First documented version.
			 */
			public function __construct()
			{
				if(strpos(__NAMESPACE__, '\\') !== FALSE) // Sanity check.
					throw new \exception('Not a root namespace: `'.__NAMESPACE__.'`.');

				$this->slug = $this->text_domain = str_replace('_', '-', __NAMESPACE__);
			}

			/* --------------------------------------------------------------------------------------
			 * Misc. utility methods.
			 -------------------------------------------------------------------------------------- */

			/**
			 * Trims strings deeply.
			 *
			 * @since 150409
			 *
			 * @param mixed  $values Any value can be converted into a trimmed string.
			 *    Actually, objects can't, but this recurses into objects.
			 *
			 * @param string $chars Specific chars to trim.
			 *    Defaults to PHP's trim: " \r\n\t\0\x0B". Use an empty string to bypass.
			 *
			 * @param string $extra_chars Additional chars to trim.
			 *
			 * @return string|array|object Trimmed string, array, object.
			 */
			public function trim_deep($values, $chars = '', $extra_chars = '')
			{
				if(is_array($values) || is_object($values))
				{
					foreach($values as $_key => &$_values)
						$_values = $this->trim_deep($_values, $chars, $extra_chars);
					unset($_key, $_values); // Housekeeping.
					return $values; // Trimmed deeply.
				}
				$string      = (string)$values;
				$chars       = (string)$chars;
				$extra_chars = (string)$extra_chars;

				$chars = isset($chars[0]) ? $chars : " \r\n\t\0\x0B";
				$chars = $chars.$extra_chars; // Concatenate.

				return trim($string, $chars);
			}

			/**
			 * Escape single quotes.
			 *
			 * @since 140422 First documented version.
			 *
			 * @param string  $string Input string to escape.
			 * @param integer $times Optional. Defaults to one escape char; e.g. `\'`.
			 *    If you need to escape more than once, set this to something > `1`.
			 *
			 * @return string Escaped string; e.g. `Raam\'s the lead developer`.
			 */
			public function esc_sq($string, $times = 1)
			{
				return str_replace("'", str_repeat('\\', abs($times))."'", (string)$string);
			}

			/**
			 * String replace ONE time.
			 *
			 * @since 150218 Refactoring cache clear/purge routines.
			 *
			 * @param string  $needle A string to search/replace.
			 * @param string  $replace What to replace `$needle` with.
			 * @param string  $haystack The string/haystack to search in.
			 *
			 * @param boolean $caSe_insensitive Defaults to a `FALSE` value.
			 *    Pass this as `TRUE` to a caSe-insensitive search/replace.
			 *
			 * @return string The `$haystack`, with `$needle` replaced with `$replace` ONE time only.
			 */
			public function str_replace_once($needle, $replace, $haystack, $caSe_insensitive = FALSE)
			{
				$needle      = (string)$needle;
				$replace     = (string)$replace;
				$haystack    = (string)$haystack;
				$caSe_strpos = $caSe_insensitive ? 'stripos' : 'strpos';

				if(($needle_strpos = $caSe_strpos($haystack, $needle)) === FALSE)
					return $haystack; // Nothing to replace.

				return (string)substr_replace($haystack, $replace, $needle_strpos, strlen($needle));
			}

			/**
			 * String replace ONE time (caSe-insensitive).
			 *
			 * @since 150218 Refactoring cache clear/purge routines.
			 *
			 * @param string $needle A string to search/replace.
			 * @param string $replace What to replace `$needle` with.
			 * @param string $haystack The string/haystack to search in.
			 *
			 * @return string The `$haystack`, with `$needle` replaced with `$replace` ONE time only.
			 */
			public function str_ireplace_once($needle, $replace, $haystack)
			{
				return $this->str_replace_once($needle, $replace, $haystack, TRUE);
			}

			/**
			 * Normalizes directory/file separators.
			 *
			 * @since 140829 Implementing XML/RSS feed clearing.
			 *
			 * @param string  $dir_file Directory/file path.
			 *
			 * @param boolean $allow_trailing_slash Defaults to FALSE.
			 *    If TRUE; and `$dir_file` contains a trailing slash; we'll leave it there.
			 *
			 * @return string Normalized directory/file path.
			 */
			public function n_dir_seps($dir_file, $allow_trailing_slash = FALSE)
			{
				$dir_file = (string)$dir_file; // Force string value.
				if(!isset($dir_file[0])) return ''; // Catch empty string.

				if(strpos($dir_file, '://' !== FALSE))  // A possible stream wrapper?
				{
					if(preg_match('/^(?P<stream_wrapper>[a-zA-Z0-9]+)\:\/\//', $dir_file, $stream_wrapper))
						$dir_file = preg_replace('/^(?P<stream_wrapper>[a-zA-Z0-9]+)\:\/\//', '', $dir_file);
				}
				if(strpos($dir_file, ':' !== FALSE))  // Might have a Windows® drive letter?
				{
					if(preg_match('/^(?P<drive_letter>[a-zA-Z])\:[\/\\\\]/', $dir_file)) // It has a Windows® drive letter?
						$dir_file = preg_replace_callback('/^(?P<drive_letter>[a-zA-Z])\:[\/\\\\]/', create_function('$m', 'return strtoupper($m[0]);'), $dir_file);
				}
				$dir_file = preg_replace('/\/+/', '/', str_replace(array(DIRECTORY_SEPARATOR, '\\', '/'), '/', $dir_file));
				$dir_file = ($allow_trailing_slash) ? $dir_file : rtrim($dir_file, '/'); // Strip trailing slashes.

				if(!empty($stream_wrapper[0])) // Stream wrapper (force lowercase).
					$dir_file = strtolower($stream_wrapper[0]).$dir_file;

				return $dir_file; // Normalized now.
			}

			/**
			 * Adds a tmp name suffix to a directory/file path.
			 *
			 * @since 150218 Refactoring cache clear/purge routines.
			 *
			 * @param string $dir_file An input directory or file path.
			 *
			 * @return string The original `$dir_file` with a tmp name suffix.
			 */
			public function add_tmp_suffix($dir_file)
			{
				return (string)rtrim($dir_file, DIRECTORY_SEPARATOR.'\\/').'-'.str_replace('.', '', uniqid('', TRUE)).'-tmp';
			}

			/**
			 * Acquires system tmp directory path.
			 *
			 * @since 150218 Refactoring cache clear/purge routines.
			 *
			 * @return string System tmp directory path; else an empty string.
			 */
			public function get_tmp_dir()
			{
				if(isset(static::$static[__FUNCTION__]))
					return static::$static[__FUNCTION__];

				static::$static[__FUNCTION__] = ''; // Initialize.
				$tmp_dir                      = &static::$static[__FUNCTION__];

				if(defined('WP_TEMP_DIR'))
					$possible_tmp_dirs[] = WP_TEMP_DIR;

				if($this->function_is_possible('sys_get_temp_dir'))
					$possible_tmp_dirs[] = sys_get_temp_dir();

				if($this->function_is_possible('ini_get'))
					$possible_tmp_dirs[] = ini_get('upload_tmp_dir');

				if(!empty($_SERVER['TEMP']))
					$possible_tmp_dirs[] = $_SERVER['TEMP'];

				if(!empty($_SERVER['TMPDIR']))
					$possible_tmp_dirs[] = $_SERVER['TMPDIR'];

				if(!empty($_SERVER['TMP']))
					$possible_tmp_dirs[] = $_SERVER['TMP'];

				if(stripos(PHP_OS, 'win') === 0)
					$possible_tmp_dirs[] = 'C:/Temp';

				if(stripos(PHP_OS, 'win') !== 0)
					$possible_tmp_dirs[] = '/tmp';

				if(defined('WP_CONTENT_DIR'))
					$possible_tmp_dirs[] = WP_CONTENT_DIR;

				if(!empty($possible_tmp_dirs)) foreach($possible_tmp_dirs as $_tmp_dir)
					if(($_tmp_dir = trim((string)$_tmp_dir)) && @is_dir($_tmp_dir) && @is_writable($_tmp_dir))
						return ($tmp_dir = $this->n_dir_seps($_tmp_dir));
				unset($_tmp_dir); // Housekeeping.

				return ($tmp_dir = ''); // Failed to locate.
			}

			/**
			 * Finds absolute server path to `/wp-config.php` file.
			 *
			 * @since 140422 First documented version.
			 *
			 * @return string Absolute server path to `/wp-config.php` file;
			 *    else an empty string if unable to locate the file.
			 */
			public function find_wp_config_file()
			{
				if(is_file($abspath_wp_config = ABSPATH.'wp-config.php'))
					$wp_config_file = $abspath_wp_config;

				else if(is_file($dirname_abspath_wp_config = dirname(ABSPATH).'/wp-config.php'))
					$wp_config_file = $dirname_abspath_wp_config;

				else $wp_config_file = ''; // Unable to find `/wp-config.php` file.

				return $wp_config_file;
			}

			/* --------------------------------------------------------------------------------------
			 * File/directory iteration utilities for ZenCache.
			 -------------------------------------------------------------------------------------- */

			/**
			 * Recursive directory iterator based on a regex pattern.
			 *
			 * @since 140422 First documented version.
			 *
			 * @param string $dir An absolute server directory path.
			 * @param string $regex A regex pattern; compares to each full file path.
			 *
			 * @return \RegexIterator Navigable with {@link \foreach()}; where each item
			 *    is a {@link \RecursiveDirectoryIterator}.
			 */
			public function dir_regex_iteration($dir, $regex)
			{
				$dir_iterator      = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_SELF | \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS);
				$iterator_iterator = new \RecursiveIteratorIterator($dir_iterator, \RecursiveIteratorIterator::CHILD_FIRST);
				$regex_iterator    = new \RegexIterator($iterator_iterator, $regex, \RegexIterator::MATCH, \RegexIterator::USE_KEY);

				return $regex_iterator;
			}

			/**
			 * Clear files from the cache directory (for all hosts/blogs);
			 *    i.e. those that match a specific regex pattern.
			 *
			 * @since 150218 Refactoring cache clear/purge routines.
			 *
			 * @param string $regex A regex pattern; see {@link delete_files_from_cache_dir()}.
			 *
			 * @return integer Total files cleared by this routine (if any).
			 *
			 * @see delete_files_from_cache_dir()
			 */
			public function clear_files_from_cache_dir($regex)
			{
				return $this->delete_files_from_cache_dir($regex);
			}

			/**
			 * Clear files from the cache directory (for the current host);
			 *    i.e. those that match a specific regex pattern.
			 *
			 * @since 150218 Refactoring cache clear/purge routines.
			 *
			 * @param string $regex A regex pattern; see {@link delete_files_from_host_cache_dir()}.
			 *
			 * @return integer Total files cleared by this routine (if any).
			 *
			 * @see delete_files_from_host_cache_dir()
			 */
			public function clear_files_from_host_cache_dir($regex)
			{
				return $this->delete_files_from_host_cache_dir($regex);
			}

			/**
			 * Purge files from the cache directory (for all hosts/blogs);
			 *    i.e. those that match a specific regex pattern.
			 *
			 * @since 150218 Refactoring cache clear/purge routines.
			 *
			 * @param string $regex A regex pattern; see {@link delete_files_from_cache_dir()}.
			 *
			 * @return integer Total files purged by this routine (if any).
			 *
			 * @see delete_files_from_cache_dir()
			 */
			public function purge_files_from_cache_dir($regex)
			{
				return $this->delete_files_from_cache_dir($regex, TRUE);
			}

			/**
			 * Purge files from the cache directory (for the current host);
			 *    i.e. those that match a specific regex pattern.
			 *
			 * @since 150218 Refactoring cache clear/purge routines.
			 *
			 * @param string $regex A regex pattern; see {@link delete_files_from_host_cache_dir()}.
			 *
			 * @return integer Total files purged by this routine (if any).
			 *
			 * @see delete_files_from_host_cache_dir()
			 */
			public function purge_files_from_host_cache_dir($regex)
			{
				return $this->delete_files_from_host_cache_dir($regex, TRUE);
			}

			/**
			 * Delete files from the cache directory (for all hosts/blogs);
			 *    i.e. those that match a specific regex pattern.
			 *
			 * @since 141110 Refactoring cache clear/purge routines.
			 *
			 * @param string  $regex A `/[regex pattern]/`; relative to the cache directory.
			 *    e.g. `/^http\/example\.com\/my\-slug(?:\/index)?(?:\.|\/(?:page\/[0-9]+|comment\-page\-[0-9]+)[.\/])/`
			 *
			 *    Or, this can also be a full/absolute regex pattern against an absolute path;
			 *    provided that it always starts with `/^`; including the full absolute cache/host directory path.
			 *    e.g. `/^\/cache\/dir\/http\/example\.com\/my\-slug(?:\/index)?(?:\.|\/(?:page\/[0-9]+|comment\-page\-[0-9]+)[.\/])/`
			 *
			 *    NOTE: Paths used in any/all regex patterns should be generated with {@link build_cache_path()}.
			 *       Recommended flags to {@link build_cache_path()} include the following.
			 *
			 *       - {@link CACHE_PATH_NO_PATH_INDEX}
			 *       - {@link CACHE_PATH_NO_QUV}
			 *       - {@link CACHE_PATH_NO_EXT}
			 *
			 *    **TIP:** There is a variation of {@link build_cache_path()} to assist with this.
			 *    Please see: {@link build_cache_path_regex()}. It is much easier to work with :-)
			 *
			 * @param boolean $check_max_age Check max age? i.e. use purge behavior?
			 *
			 * @return integer Total files deleted by this routine (if any).
			 *
			 * @throws \exception If unable to delete a file for any reason.
			 */
			public function delete_files_from_cache_dir($regex, $check_max_age = FALSE)
			{
				$counter = 0; // Initialize.

				if(!($regex = (string)$regex))
					return $counter; // Nothing to do.

				if(!is_dir($cache_dir = $this->cache_dir()))
					return $counter; // Nothing to do.

				$cache_dir = $this->n_dir_seps($cache_dir);

				if($check_max_age && (empty($this->options) || !is_array($this->options) || !isset($this->options['cache_max_age'])))
					throw new \exception(__('The `options` property w/ a `cache_max_age` key is not defined in this class.', $this->text_domain));

				if($check_max_age && !($max_age = strtotime('-'.$this->options['cache_max_age'])))
					return $counter; // Invalid cache expiration time.

				/* ------- Begin lock state... ----------- */

				$cache_lock = $this->cache_lock(); // Lock cache writes.

				clearstatcache(); // Clear stat cache to be sure we have a fresh start below.

				$cache_dir_tmp       = $this->add_tmp_suffix($cache_dir); // Temporary directory.
				$cache_dir_tmp_regex = $regex; // Initialize host-specific regex pattern for the tmp directory.

				$cache_dir_tmp_regex = '\\/'.ltrim($cache_dir_tmp_regex, '^\\/'); // Make sure it begins with an escaped `/`.
				$cache_dir_tmp_regex = $this->str_ireplace_once(preg_quote($cache_dir.'/', '/'), '', $cache_dir_tmp_regex);

				$cache_dir_tmp_regex = ltrim($cache_dir_tmp_regex, '^\\/');
				if(strpos($cache_dir_tmp_regex, '(?:\/') === 0 || strpos($cache_dir_tmp_regex, '(\/') === 0)
					$cache_dir_tmp_regex = '/^'.preg_quote($cache_dir_tmp, '/').$cache_dir_tmp_regex;
				else $cache_dir_tmp_regex = '/^'.preg_quote($cache_dir_tmp.'/', '/').$cache_dir_tmp_regex;

				# if(WP_DEBUG) file_put_contents(WP_CONTENT_DIR.'/zc-debug.log', print_r($regex, TRUE)."\n".print_r($cache_dir_tmp_regex, TRUE)."\n\n", FILE_APPEND);
				// Uncomment the above line to debug regex pattern matching used by this routine; and others that call upon it.

				if(!rename($cache_dir, $cache_dir_tmp)) // Work from tmp directory so deletions are atomic.
					throw new \exception(sprintf(__('Unable to delete files. Rename failure on directory: `%1$s`.', $this->text_domain), $cache_dir));

				/** @var $_resource \RecursiveDirectoryIterator Regex iterator reference for IDEs. */
				foreach(($_dir_regex_iteration = $this->dir_regex_iteration($cache_dir_tmp, $cache_dir_tmp_regex)) as $_resource)
				{
					$_resource_type = $_resource->getType();
					$_sub_path_name = $_resource->getSubpathname();
					$_path_name     = $_resource->getPathname();

					if($_resource_type !== 'dir' && strpos($_sub_path_name, '/') === FALSE)
						continue; // Don't delete links/files in the immediate directory; e.g. `zc-advanced-cache` or `.htaccess`, etc.
					// Actual `http|https/...` cache links/files are nested. Links/files in the immediate directory are for other purposes.

					switch($_resource_type) // Based on type; i.e. `link`, `file`, `dir`.
					{
						case 'link': // Symbolic links; i.e. 404 errors.

							if($check_max_age && !empty($max_age) && is_file($_resource->getLinkTarget()))
								if(($_lstat = lstat($_path_name)) && !empty($_lstat['mtime']))
									if($_lstat['mtime'] >= $max_age) // Still valid?
										break; // Break switch handler.

							if(!unlink($_path_name)) // Throw exception if unable to delete.
								throw new \exception(sprintf(__('Unable to delete symlink: `%1$s`.', $this->text_domain), $_path_name));
							$counter++; // Increment counter for each link we delete.

							break; // Break switch handler.

						case 'file': // Regular files; i.e. not symlinks.

							if($check_max_age && !empty($max_age)) // Should check max age?
								if($_resource->getMTime() >= $max_age) // Still valid?
									break; // Break switch handler.

							if(!unlink($_path_name)) // Throw exception if unable to delete.
								throw new \exception(sprintf(__('Unable to delete file: `%1$s`.', $this->text_domain), $_path_name));
							$counter++; // Increment counter for each file we delete.

							break; // Break switch handler.

						case 'dir': // A regular directory; i.e. not a symlink.

							if($regex !== '/^.+/i') // Deleting everything?
								break; // Break switch handler. Not deleting everything.

							if($check_max_age && !empty($max_age)) // Should check max age?
								break; // Break switch handler. Not deleting everything in this case.

							if(!rmdir($_path_name)) // Throw exception if unable to delete the directory itself.
								throw new \exception(sprintf(__('Unable to delete dir: `%1$s`.', $this->text_domain), $_path_name));
							# $counter++; // Increment counter for each directory we delete. ~ NO don't do that here.

							break; // Break switch handler.

						default: // Something else that is totally unexpected here.
							throw new \exception(sprintf(__('Unexpected resource type: `%1$s`.', $this->text_domain), $_resource_type));
					}
				}
				unset($_dir_regex_iteration, $_resource, $_resource_type, $_sub_path_name, $_path_name, $_lstat); // Housekeeping.

				if(!rename($cache_dir_tmp, $cache_dir)) // Deletions are atomic; restore original directory now.
					throw new \exception(sprintf(__('Unable to delete files. Rename failure on tmp directory: `%1$s`.', $this->text_domain), $cache_dir_tmp));

				/* ------- End lock state... ------------- */

				$this->cache_unlock($cache_lock); // Unlock cache directory.

				return $counter; // Total files deleted by this routine.
			}

			/**
			 * Delete files from the cache directory (for the current host);
			 *    i.e. those that match a specific regex pattern.
			 *
			 * @since 141110 Refactoring cache clear/purge routines.
			 *
			 * @param string  $regex A `/[regex pattern]/`; relative to the host cache directory.
			 *    e.g. `/^my\-slug(?:\/index)?(?:\.|\/(?:page\/[0-9]+|comment\-page\-[0-9]+)[.\/])/`
			 *
			 *    Or, this can also be a full/absolute regex pattern against an absolute path;
			 *    provided that it always starts with `/^`; including the full absolute cache/host directory path.
			 *    e.g. `/^\/cache\/dir\/http\/example\.com\/my\-slug(?:\/index)?(?:\.|\/(?:page\/[0-9]+|comment\-page\-[0-9]+)[.\/])/`
			 *
			 *    NOTE: Paths used in any/all regex patterns should be generated with {@link build_cache_path()}.
			 *       Recommended flags to {@link build_cache_path()} include the following.
			 *
			 *       - {@link CACHE_PATH_NO_SCHEME}
			 *       - {@link CACHE_PATH_NO_HOST}
			 *       - {@link CACHE_PATH_NO_PATH_INDEX}
			 *       - {@link CACHE_PATH_NO_QUV}
			 *       - {@link CACHE_PATH_NO_EXT}
			 *
			 *    **TIP:** There is a variation of {@link build_cache_path()} to assist with this.
			 *    Please see: {@link build_host_cache_path_regex()}. It is much easier to work with :-)
			 *
			 * @param boolean $check_max_age Check max age? i.e. use purge behavior?
			 *
			 * @return integer Total files deleted by this routine (if any).
			 *
			 * @throws \exception If unable to delete a file for any reason.
			 */
			public function delete_files_from_host_cache_dir($regex, $check_max_age = FALSE)
			{
				$counter = 0; // Initialize.

				if(!($regex = (string)$regex))
					return $counter; // Nothing to do.

				if(!is_dir($cache_dir = $this->cache_dir()))
					return $counter; // Nothing to do.

				$host                 = $_SERVER['HTTP_HOST'];
				$host_base_dir_tokens = $this->host_base_dir_tokens();
				$cache_dir            = $this->n_dir_seps($cache_dir);

				if($check_max_age && (empty($this->options) || !is_array($this->options) || !isset($this->options['cache_max_age'])))
					throw new \exception(__('The `options` property w/ a `cache_max_age` key is not defined in this class.', $this->text_domain));

				if($check_max_age && !($max_age = strtotime('-'.$this->options['cache_max_age'])))
					return $counter; // Invalid cache expiration time.

				/* ------- Begin lock state... ----------- */

				$cache_lock = $this->cache_lock(); // Lock cache writes.

				clearstatcache(); // Clear stat cache to be sure we have a fresh start below.

				foreach(array('http', 'https') as $_host_scheme) // Consider `http|https` schemes.

					/* This multi-scheme iteration could (alternatively) be accomplished via regex `\/https?\/`.
						HOWEVER, since this operation is supposed to impact only a single host in a network, and because
						we want to do atomic deletions, we iterate and rename `$_host_cache_dir` for each scheme.

						It's also worth noting that most high traffic sites will not be in the habit of serving
						pages over SSL all the time; so this really should not have a significant performance hit.
						In fact, it may improve performance since we are traversing each sub-directory separately;
						i.e. we don't need to glob both `http` and `https` traffic into a single directory scan. */
				{
					$_host_url              = $_host_scheme.'://'.$host.$host_base_dir_tokens; // Base URL for this host|blog.
					$_host_cache_path_flags = $this::CACHE_PATH_NO_PATH_INDEX | $this::CACHE_PATH_NO_QUV | $this::CACHE_PATH_NO_EXT;
					$_host_cache_path       = $this->build_cache_path($_host_url, '', '', $_host_cache_path_flags);
					$_host_cache_dir        = $this->n_dir_seps($cache_dir.'/'.$_host_cache_path); // Normalize.

					if(!$_host_cache_dir || !is_dir($_host_cache_dir)) continue; // Nothing to do.

					$_host_cache_dir_tmp       = $this->add_tmp_suffix($_host_cache_dir); // Temporary directory.
					$_host_cache_dir_tmp_regex = $regex; // Initialize host-specific regex pattern for the tmp directory.

					$_host_cache_dir_tmp_regex = '\\/'.ltrim($_host_cache_dir_tmp_regex, '^\\/'); // Make sure it begins with an escaped `/`.
					$_host_cache_dir_tmp_regex = $this->str_ireplace_once(preg_quote($_host_cache_path.'/', '/'), '', $_host_cache_dir_tmp_regex);
					$_host_cache_dir_tmp_regex = $this->str_ireplace_once(preg_quote($_host_cache_dir.'/', '/'), '', $_host_cache_dir_tmp_regex);

					$_host_cache_dir_tmp_regex = ltrim($_host_cache_dir_tmp_regex, '^\\/');
					if(strpos($_host_cache_dir_tmp_regex, '(?:\/') === 0 || strpos($_host_cache_dir_tmp_regex, '(\/') === 0)
						$_host_cache_dir_tmp_regex = '/^'.preg_quote($_host_cache_dir_tmp, '/').$_host_cache_dir_tmp_regex;
					else $_host_cache_dir_tmp_regex = '/^'.preg_quote($_host_cache_dir_tmp.'/', '/').$_host_cache_dir_tmp_regex;

					# if(WP_DEBUG) file_put_contents(WP_CONTENT_DIR.'/zc-debug.log', print_r($regex, TRUE)."\n".print_r($_host_cache_dir_tmp_regex, TRUE)."\n\n", FILE_APPEND);
					// Uncomment the above line to debug regex pattern matching used by this routine; and others that call upon it.

					if(!rename($_host_cache_dir, $_host_cache_dir_tmp)) // Work from tmp directory so deletions are atomic.
						throw new \exception(sprintf(__('Unable to delete files. Rename failure on tmp directory: `%1$s`.', $this->text_domain), $_host_cache_dir));

					/** @var $_file_dir \RecursiveDirectoryIterator Regex iterator reference for IDEs. */
					foreach(($_dir_regex_iteration = $this->dir_regex_iteration($_host_cache_dir_tmp, $_host_cache_dir_tmp_regex)) as $_resource)
					{
						$_resource_type = $_resource->getType();
						$_sub_path_name = $_resource->getSubpathname();
						$_path_name     = $_resource->getPathname();

						if($_host_cache_dir === $cache_dir && $_resource_type !== 'dir' && strpos($_sub_path_name, '/') === FALSE)
							continue; // Don't delete links/files in the immediate directory; e.g. `zc-advanced-cache` or `.htaccess`, etc.
						// Actual `http|https/...` cache links/files are nested. Links/files in the immediate directory are for other purposes.

						switch($_resource_type) // Based on type; i.e. `link`, `file`, `dir`.
						{
							case 'link': // Symbolic links; i.e. 404 errors.

								if($check_max_age && !empty($max_age) && is_file($_resource->getLinkTarget()))
									if(($_lstat = lstat($_path_name)) && !empty($_lstat['mtime']))
										if($_lstat['mtime'] >= $max_age) // Still valid?
											break; // Break switch handler.

								if(!unlink($_path_name)) // Throw exception if unable to delete.
									throw new \exception(sprintf(__('Unable to delete symlink: `%1$s`.', $this->text_domain), $_path_name));
								$counter++; // Increment counter for each link we delete.

								break; // Break switch handler.

							case 'file': // Regular files; i.e. not symlinks.

								if($check_max_age && !empty($max_age)) // Should check max age?
									if($_resource->getMTime() >= $max_age) // Still valid?
										break; // Break switch handler.

								if(!unlink($_path_name)) // Throw exception if unable to delete.
									throw new \exception(sprintf(__('Unable to delete file: `%1$s`.', $this->text_domain), $_path_name));
								$counter++; // Increment counter for each file we delete.

								break; // Break switch handler.

							case 'dir': // A regular directory; i.e. not a symlink.

								if($regex !== '/^.+/i') // Deleting everything?
									break; // Break switch handler. Not deleting everything.

								if($check_max_age && !empty($max_age)) // Should check max age?
									break; // Break switch handler. Not deleting everything in this case.

								if(!rmdir($_path_name)) // Throw exception if unable to delete the directory itself.
									throw new \exception(sprintf(__('Unable to delete dir: `%1$s`.', $this->text_domain), $_path_name));
								# $counter++; // Increment counter for each directory we delete. ~ NO don't do that here.

								break; // Break switch handler.

							default: // Something else that is totally unexpected here.
								throw new \exception(sprintf(__('Unexpected resource type: `%1$s`.', $this->text_domain), $_resource_type));
						}
					}
					unset($_dir_regex_iteration, $_resource, $_resource_type, $_sub_path_name, $_path_name, $_lstat); // Housekeeping.

					if(!rename($_host_cache_dir_tmp, $_host_cache_dir)) // Deletions are atomic; restore original directory now.
						throw new \exception(sprintf(__('Unable to delete files. Rename failure on tmp directory: `%1$s`.', $this->text_domain), $_host_cache_dir_tmp));
				}
				unset($_host_scheme, $_host_url, $_host_cache_path_flags, $_host_cache_path,
					$_host_cache_dir, $_host_cache_dir_tmp, $_host_cache_dir_tmp_regex); // Housekeeping.

				/* ------- End lock state... ------------- */

				$this->cache_unlock($cache_lock); // Unlock cache directory.

				return $counter; // Total files deleted by this routine.
			}

			/**
			 * Delete all files/dirs from a directory (for all schemes/hosts);
			 *    including `zc-` prefixed files; or anything else for that matter.
			 *
			 * @since 141110 Refactoring cache clear/purge routines.
			 *
			 * @param string  $dir The directory from which to delete files/dirs.
			 *
			 *    SECURITY: This directory MUST be located inside the `/wp-content/` directory.
			 *    Also, it MUST be a sub-directory of `/wp-content/`, NOT the directory itself.
			 *    Also, it cannot be: `mu-plugins`, `themes`, or `plugins`.
			 *
			 * @param boolean $delete_dir_too Delete parent? i.e. delete the `$dir` itself also?
			 *
			 * @return integer Total files/directories deleted by this routine (if any).
			 *
			 * @throws \exception If unable to delete a file/directory for any reason.
			 */
			public function delete_all_files_dirs_in($dir, $delete_dir_too = FALSE)
			{
				$counter = 0; // Initialize.

				if(!($dir = trim((string)$dir)) || !is_dir($dir))
					return $counter; // Nothing to do.

				$dir                  = $this->n_dir_seps($dir); // Normalize separators.
				$dir_temp             = $this->add_tmp_suffix($dir); // Temporary directory.
				$wp_content_dir_regex = preg_quote($this->n_dir_seps(WP_CONTENT_DIR), '/');

				if(!preg_match('/^'.$wp_content_dir_regex.'\/[^\/]+/i', $dir))
					return $counter; // Security flag; do nothing in this case.

				if(preg_match('/^'.$wp_content_dir_regex.'\/(?:mu\-plugins|themes|plugins)(?:\/|$)/i', $dir))
					return $counter; // Security flag; do nothing in this case.

				/* ------- Begin lock state... ----------- */

				$cache_lock = $this->cache_lock(); // Lock cache writes.

				clearstatcache(); // Clear stat cache to be sure we have a fresh start below.

				if(!rename($dir, $dir_temp)) // Work from tmp directory so deletions are atomic.
					throw new \exception(sprintf(__('Unable to delete all files/dirs. Rename failure on tmp directory: `%1$s`.', $this->text_domain), $dir));

				/** @var $_file_dir \RecursiveDirectoryIterator for IDEs. */
				foreach(($_dir_regex_iteration = $this->dir_regex_iteration($dir_temp, '/.+/')) as $_resource)
				{
					$_resource_type = $_resource->getType();
					$_sub_path_name = $_resource->getSubpathname();
					$_path_name     = $_resource->getPathname();

					switch($_resource_type) // Based on type; i.e. `link`, `file`, `dir`.
					{
						case 'link': // Symbolic links; i.e. 404 errors.

							if(!unlink($_path_name)) // Throw exception if unable to delete.
								throw new \exception(sprintf(__('Unable to delete symlink: `%1$s`.', $this->text_domain), $_path_name));
							$counter++; // Increment counter for each link we delete.

							break; // Break switch handler.

						case 'file': // Regular files; i.e. not symlinks.

							if(!unlink($_path_name)) // Throw exception if unable to delete.
								throw new \exception(sprintf(__('Unable to delete file: `%1$s`.', $this->text_domain), $_path_name));
							$counter++; // Increment counter for each file we delete.

							break; // Break switch handler.

						case 'dir': // A regular directory; i.e. not a symlink.

							if(!rmdir($_path_name)) // Throw exception if unable to delete the directory itself.
								throw new \exception(sprintf(__('Unable to delete dir: `%1$s`.', $this->text_domain), $_path_name));
							$counter++; // Increment counter for each directory we delete.

							break; // Break switch handler.

						default: // Something else that is totally unexpected here.
							throw new \exception(sprintf(__('Unexpected resource type: `%1$s`.', $this->text_domain), $_resource_type));
					}
				}
				unset($_dir_regex_iteration, $_resource, $_resource_type, $_sub_path_name, $_path_name); // Housekeeping.

				if(!rename($dir_temp, $dir)) // Deletions are atomic; restore original directory now.
					throw new \exception(sprintf(__('Unable to delete all files/dirs. Rename failure on tmp directory: `%1$s`.', $this->text_domain), $dir_temp));

				if($delete_dir_too) // Delete parent? i.e. delete the `$dir` itself also?
				{
					if(!rmdir($dir)) // Throw exception if unable to delete.
						throw new \exception(sprintf(__('Unable to delete directory: `%1$s`.', $this->text_domain), $dir));
					$counter++; // Increment counter for each directory we delete.
				}
				/* ------- End lock state... ------------- */

				$this->cache_unlock($cache_lock); // Unlock cache directory.

				return $counter; // Total files deleted by this routine.
			}

			/* --------------------------------------------------------------------------------------
			 * Cache locking utilities.
			 -------------------------------------------------------------------------------------- */

			/**
			 * Get an exclusive lock on the cache directory.
			 *
			 * @since 140422 First documented version.
			 *
			 * @return array Lock type & resource handle needed to unlock later or FALSE if disabled by filter.
			 *
			 * @throws \exception If {@link \sem_get()} not available and there's
			 *    no writable tmp directory for {@link \flock()} either.
			 *
			 * @throws \exception If unable to obtain an exclusive lock by any available means.
			 *
			 * @note This call is blocking; i.e. it will not return a lock until a lock becomes possible.
			 *    In short, this will block the caller until such time as write access becomes possible.
			 */
			public function cache_lock()
			{
				if($this->apply_wp_filters(__CLASS__.'_disable_cache_locking', FALSE))
					return FALSE;

				if(!($wp_config_file = $this->find_wp_config_file()))
					throw new \exception(__('Unable to find the wp-config.php file.', $this->text_domain));

				$locking_method = $this->apply_wp_filters(__METHOD__.'_lock_type', 'flock');

				if(!in_array($locking_method, array('flock', 'sem')))
					$locking_method = 'flock';

				if($locking_method === 'sem')
					if($this->function_is_possible('sem_get'))
						if(($ipc_key = ftok($wp_config_file, 'w')))
							if(($resource = sem_get($ipc_key, 1)) && sem_acquire($resource))
								return array('type' => 'sem', 'resource' => $resource);

				// Use `flock()` as a decent fallback when `sem_get()` is not not forced or is not possible.

				if(!($tmp_dir = $this->get_tmp_dir()))
					throw new \exception(__('No writable tmp directory.', $this->text_domain));

				$inode_key = fileinode($wp_config_file);
				$mutex     = $tmp_dir.'/'.$this->slug.'-'.$inode_key.'.lock';
				if(!($resource = fopen($mutex, 'w')) || !flock($resource, LOCK_EX))
					throw new \exception(__('Unable to obtain an exclusive lock.', $this->text_domain));

				return array('type' => 'flock', 'resource' => $resource);
			}

			/**
			 * Release an exclusive lock on the cache directory.
			 *
			 * @since 140422 First documented version.
			 *
			 * @param array $lock Type & resource that we are unlocking.
			 */
			public function cache_unlock(array $lock)
			{
				if($this->apply_wp_filters(__CLASS__.'_disable_cache_locking', FALSE))
					return;

				if(!is_array($lock))
					return; // Not possible.

				if(empty($lock['type']) || empty($lock['resource']))
					return; // Not possible.

				if(!is_resource($lock['resource']))
					return; // Not possible.

				if($lock['type'] === 'sem')
					sem_release($lock['resource']);

				else if($lock['type'] === 'flock')
				{
					flock($lock['resource'], LOCK_UN);
					fclose($lock['resource']);
				}
			}

			/* --------------------------------------------------------------------------------------
			 * Translation utilities for ZenCache.
			 -------------------------------------------------------------------------------------- */

			/**
			 * `X file` or `X files`, translated w/ singlular/plural context.
			 *
			 * @since 140422 First documented version.
			 *
			 * @param integer $counter Total files; i.e. the counter.
			 *
			 * @return string The phrase `X file` or `X files`.
			 */
			public function i18n_files($counter)
			{
				$counter = (integer)$counter; // Force integer.

				return sprintf(_n('%1$s file', '%1$s files', $counter, $this->text_domain), $counter);
			}

			/**
			 * `X directory` or `X directories`, translated w/ singlular/plural context.
			 *
			 * @since 140422 First documented version.
			 *
			 * @param integer $counter Total directories; i.e. the counter.
			 *
			 * @return string The phrase `X directory` or `X directories`.
			 */
			public function i18n_dirs($counter)
			{
				$counter = (integer)$counter; // Force integer.

				return sprintf(_n('%1$s directory', '%1$s directories', $counter, $this->text_domain), $counter);
			}

			/**
			 * `X file/directory` or `X files/directories`, translated w/ singlular/plural context.
			 *
			 * @since 140422 First documented version.
			 *
			 * @param integer $counter Total files/directories; i.e. the counter.
			 *
			 * @return string The phrase `X file/directory` or `X files/directories`.
			 */
			public function i18n_files_dirs($counter)
			{
				$counter = (integer)$counter; // Force integer.

				return sprintf(_n('%1$s file/directory', '%1$s files/directories', $counter, $this->text_domain), $counter);
			}

			/* --------------------------------------------------------------------------------------
			 * Hook/filter API for ZenCache.
			 -------------------------------------------------------------------------------------- */

			/**
			 * Assigns an ID to each callable attached to a hook/filter.
			 *
			 * @since 140422 First documented version.
			 *
			 * @param string|callable|mixed $function A string or a callable.
			 *
			 * @return string Hook ID for the given `$function`.
			 *
			 * @throws \exception If the hook/function is invalid (i.e. it's not possible to generate an ID).
			 */
			public function hook_id($function)
			{
				if(is_string($function))
					return $function;

				if(is_object($function)) // Closure.
					$function = array($function, '');
				else $function = (array)$function;

				if(is_object($function[0]))
					return spl_object_hash($function[0]).$function[1];

				else if(is_string($function[0]))
					return $function[0].'::'.$function[1];

				throw new \exception(__('Invalid hook.', $this->text_domain));
			}

			/**
			 * Adds a new hook (works with both actions & filters).
			 *
			 * @since 140422 First documented version.
			 *
			 * @param string                $hook The name of a hook to attach to.
			 * @param string|callable|mixed $function A string or a callable.
			 * @param integer               $priority Hook priority; defaults to `10`.
			 * @param integer               $accepted_args Max number of args that should be passed to the `$function`.
			 *
			 * @return boolean This always returns a `TRUE` value.
			 */
			public function add_hook($hook, $function, $priority = 10, $accepted_args = 1)
			{
				$this->hooks[$hook][$priority][$this->hook_id($function)]
					= array('function' => $function, 'accepted_args' => (integer)$accepted_args);
				return TRUE; // Always returns true.
			}

			/**
			 * Adds a new action hook.
			 *
			 * @since 140422 First documented version.
			 *
			 * @return boolean This always returns a `TRUE` value.
			 *
			 * @see add_hook()
			 */
			public function add_action() // Simple `add_hook()` alias.
			{
				return call_user_func_array(array($this, 'add_hook'), func_get_args());
			}

			/**
			 * Adds a new filter.
			 *
			 * @since 140422 First documented version.
			 *
			 * @return boolean This always returns a `TRUE` value.
			 *
			 * @see add_hook()
			 */
			public function add_filter() // Simple `add_hook()` alias.
			{
				return call_user_func_array(array($this, 'add_hook'), func_get_args());
			}

			/**
			 * Removes a hook (works with both actions & filters).
			 *
			 * @since 140422 First documented version.
			 *
			 * @param string                $hook The name of a hook to remove.
			 * @param string|callable|mixed $function A string or a callable.
			 * @param integer               $priority Hook priority; defaults to `10`.
			 *
			 * @return boolean `TRUE` if removed; else `FALSE` if not removed for any reason.
			 */
			public function remove_hook($hook, $function, $priority = 10)
			{
				if(!isset($this->hooks[$hook][$priority][$this->hook_id($function)]))
					return FALSE; // Nothing to remove in this case.

				unset($this->hooks[$hook][$priority][$this->hook_id($function)]);
				if(!$this->hooks[$hook][$priority]) unset($this->hooks[$hook][$priority]);
				return TRUE; // Existed before it was removed in this case.
			}

			/**
			 * Removes an action.
			 *
			 * @since 140422 First documented version.
			 *
			 * @return boolean `TRUE` if removed; else `FALSE` if not removed for any reason.
			 *
			 * @see remove_hook()
			 */
			public function remove_action() // Simple `remove_hook()` alias.
			{
				return call_user_func_array(array($this, 'remove_hook'), func_get_args());
			}

			/**
			 * Removes a filter.
			 *
			 * @since 140422 First documented version.
			 *
			 * @return boolean `TRUE` if removed; else `FALSE` if not removed for any reason.
			 *
			 * @see remove_hook()
			 */
			public function remove_filter() // Simple `remove_hook()` alias.
			{
				return call_user_func_array(array($this, 'remove_hook'), func_get_args());
			}

			/**
			 * Runs any callables attached to an action.
			 *
			 * @since 140422 First documented version.
			 *
			 * @param string $hook The name of an action hook.
			 */
			public function do_action($hook)
			{
				if(empty($this->hooks[$hook]))
					return; // No hooks.

				$hook_actions = $this->hooks[$hook];
				ksort($hook_actions); // Sort by priority.

				$args = func_get_args(); // We'll need these below.
				foreach($hook_actions as $_hook_action) foreach($_hook_action as $_action)
				{
					if(!isset($_action['function'], $_action['accepted_args']))
						continue; // Not a valid filter in this case.

					call_user_func_array($_action['function'], array_slice($args, 1, $_action['accepted_args']));
				}
				unset($_hook_action, $_action); // Housekeeping.
			}

			/**
			 * Runs any callables attached to a filter.
			 *
			 * @since 140422 First documented version.
			 *
			 * @param string $hook The name of a filter hook.
			 * @param mixed  $value The value to filter.
			 *
			 * @return mixed The filtered `$value`.
			 */
			public function apply_filters($hook, $value)
			{
				if(empty($this->hooks[$hook]))
					return $value; // No hooks.

				$hook_filters = $this->hooks[$hook];
				ksort($hook_filters); // Sort by priority.

				$args = func_get_args(); // We'll need these below.
				foreach($hook_filters as $_hook_filter) foreach($_hook_filter as $_filter)
				{
					if(!isset($_filter['function'], $_filter['accepted_args']))
						continue; // Not a valid filter in this case.

					$args[1] = $value; // Continously update the argument `$value`.
					$value   = call_user_func_array($_filter['function'], array_slice($args, 1, $_filter['accepted_args']));
				}
				unset($_hook_filter, $_filter); // Housekeeping.

				return $value; // With applied filters.
			}

			/**
			 * Does an action w/ back compat. for Quick Cache.
			 *
			 * @since 150218 First documented version.
			 *
			 * @param string $hook The hook to apply.
			 */
			public function do_wp_action($hook)
			{
				$hook = (string)$hook; // Force string value.
				$args = func_get_args(); // Including `$hook`.
				call_user_func_array('do_action', $args);

				if(stripos($hook, __NAMESPACE__) === 0) // Do Quick Cache back compat?
				{
					$quick_cache_filter  = 'quick_cache'.substr($hook, strlen(__NAMESPACE__));
					$quick_cache_args    = $args; // Use a copy of the args.
					$quick_cache_args[0] = $quick_cache_filter;

					call_user_func_array('do_action', $quick_cache_args);
				}
			}

			/**
			 * Applies filters w/ back compat. for Quick Cache.
			 *
			 * @since 150218 First documented version.
			 *
			 * @param string $hook The hook to apply.
			 *
			 * @return mixed The filtered value.
			 */
			public function apply_wp_filters($hook)
			{
				$hook  = (string)$hook; // Force string value.
				$args  = func_get_args(); // Including `$hook`.
				$value = call_user_func_array('apply_filters', $args);

				if(stripos($hook, __NAMESPACE__) === 0) // Do Quick Cache back compat?
				{
					$quick_cache_hook    = 'quick_cache'.substr($hook, strlen(__NAMESPACE__));
					$quick_cache_args    = $args; // Use a copy of the args.
					$quick_cache_args[0] = $quick_cache_hook;
					$quick_cache_args[1] = $value;

					$value = call_user_func_array('apply_filters', $quick_cache_args);
				}
				return $value; // Filtered value.
			}

			/* --------------------------------------------------------------------------------------
			 * Misc. long property values.
			 -------------------------------------------------------------------------------------- */

			/**
			 * Apache `.htaccess` rules that deny public access to the contents of a directory.
			 *
			 * @since 140422 First documented version.
			 *
			 * @var string `.htaccess` fules.
			 */
			public $htaccess_deny = "<IfModule authz_core_module>\n\tRequire all denied\n</IfModule>\n<IfModule !authz_core_module>\n\tdeny from all\n</IfModule>";
		}

		if(!function_exists('\\'.__NAMESPACE__.'\\__'))
		{
			/**
			 * Polyfill for {@link \__()}.
			 *
			 * @since 140422 First documented version.
			 *
			 * @param string $string String to translate.
			 * @param string $text_domain Plugin text domain.
			 *
			 * @return string Possibly translated string.
			 */
			function __($string, $text_domain)
			{
				static $exists; // Static cache.

				if($exists || ($exists = function_exists('__')))
					return \__($string, $text_domain);

				return $string; // Not possible (yet).
			}
		}
	}
}
