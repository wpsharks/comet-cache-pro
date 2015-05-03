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
			 * Hook/filter API for ZenCache.
			 -------------------------------------------------------------------------------------- */



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
	}
}
