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
