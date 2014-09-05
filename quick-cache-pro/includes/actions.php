<?php
namespace quick_cache // Root namespace.
{
	if(!defined('WPINC')) // MUST have WordPress.
		exit('Do NOT access this file directly: '.basename(__FILE__));

	class actions // Action handlers.
	{
		protected $plugin; // Set by constructor.

		public function __construct()
		{
			$this->plugin = plugin();

			if(empty($_REQUEST[__NAMESPACE__])) return;
			foreach((array)$_REQUEST[__NAMESPACE__] as $action => $args)
				if(method_exists($this, $action)) $this->{$action}($args);
		}

		public function wipe_cache($args)
		{
			if(!current_user_can($this->plugin->network_cap))
				return; // Nothing to do.

			if(empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce']))
				return; // Unauthenticated POST data.

			$counter = $this->plugin->wipe_cache(TRUE); // Counter.

			if($this->plugin->options['cache_clear_s2clean_enable'])
				if(function_exists('s2clean')) $s2clean_counter = s2clean()->md_cache_clear();

			if($this->plugin->options['cache_clear_eval_code']) // Custom code?
			{
				ob_start(); // Buffer output from PHP code.
				eval('?>'.$this->plugin->options['cache_clear_eval_code'].'<?php ');
				$eval_output = ob_get_clean();
			}
			$redirect_to = self_admin_url('/admin.php'); // Redirect preparations.
			$query_args  = array('page' => __NAMESPACE__, __NAMESPACE__.'__cache_wiped' => '1');
			$redirect_to = add_query_arg(urlencode_deep($query_args), $redirect_to);

			wp_redirect($redirect_to).exit(); // All done :-)
		}

		public function ajax_wipe_cache($args)
		{
			if(!current_user_can($this->plugin->network_cap))
				return; // Nothing to do.

			if(empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce']))
				return; // Unauthenticated POST data.

			$counter = $this->plugin->wipe_cache(TRUE); // Counter.

			if($this->plugin->options['cache_clear_s2clean_enable'])
				if(function_exists('s2clean')) $s2clean_counter = s2clean()->md_cache_clear();

			if($this->plugin->options['cache_clear_eval_code']) // Custom code?
			{
				ob_start(); // Buffer output from PHP code.
				eval('?>'.$this->plugin->options['cache_clear_eval_code'].'<?php ');
				$eval_output = ob_get_clean();
			}
			$response = sprintf(__('<p><strong>Wiped a total of <code>%1$s</code> cache files.</strong></p>', $this->plugin->text_domain), $counter);
			$response .= __('<p>Cache wiped for all sites; recreation will occur automatically over time.</p>', $this->plugin->text_domain);
			if(isset($s2clean_counter)) $response .= sprintf(__('<p><strong>Also wiped <code>%1$s</code> s2Clean cache files.</strong></p>', $this->plugin->text_domain), $s2clean_counter);
			if(!empty($eval_output)) $response .= $eval_output; // Custom output (perhaps even multiple messages).

			exit($response); // JavaScript will take it from here.
		}

		public function clear_cache($args)
		{
			if(!current_user_can($this->plugin->cap))
				return; // Nothing to do.

			if(empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce']))
				return; // Unauthenticated POST data.

			$counter = $this->plugin->clear_cache(TRUE); // Counter.

			if($this->plugin->options['cache_clear_s2clean_enable'])
				if(function_exists('s2clean')) $s2clean_counter = s2clean()->md_cache_clear();

			if($this->plugin->options['cache_clear_eval_code']) // Custom code?
			{
				ob_start(); // Buffer output from PHP code.
				eval('?>'.$this->plugin->options['cache_clear_eval_code'].'<?php ');
				$eval_output = ob_get_clean();
			}
			$redirect_to = self_admin_url('/admin.php'); // Redirect preparations.
			$query_args  = array('page' => __NAMESPACE__, __NAMESPACE__.'__cache_cleared' => '1');
			$redirect_to = add_query_arg(urlencode_deep($query_args), $redirect_to);

			wp_redirect($redirect_to).exit(); // All done :-)
		}

		public function ajax_clear_cache($args)
		{
			if(!current_user_can($this->plugin->cap))
				return; // Nothing to do.

			if(empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce']))
				return; // Unauthenticated POST data.

			$counter = $this->plugin->clear_cache(TRUE); // Counter.

			if($this->plugin->options['cache_clear_s2clean_enable'])
				if(function_exists('s2clean')) $s2clean_counter = s2clean()->md_cache_clear();

			if($this->plugin->options['cache_clear_eval_code']) // Custom code?
			{
				ob_start(); // Buffer output from PHP code.
				eval('?>'.$this->plugin->options['cache_clear_eval_code'].'<?php ');
				$eval_output = ob_get_clean();
			}
			$response = sprintf(__('<p><strong>Cleared a total of <code>%1$s</code> cache files.</strong></p>', $this->plugin->text_domain), $counter);
			$response .= __('<p>Cache cleared for this site; recreation will occur automatically over time.</p>', $this->plugin->text_domain);
			if(isset($s2clean_counter)) $response .= sprintf(__('<p><strong>Also cleared <code>%1$s</code> s2Clean cache files.</strong></p>', $this->plugin->text_domain), $s2clean_counter);
			if(!empty($eval_output)) $response .= $eval_output; // Custom output (perhaps even multiple messages).

			exit($response); // JavaScript will take it from here.
		}

		public function save_options($args)
		{
			if(!current_user_can($this->plugin->cap))
				return; // Nothing to do.

			if(empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce']))
				return; // Unauthenticated POST data.

			if(!empty($_FILES[__NAMESPACE__]['tmp_name']['import_options']))
			{
				$import_file_contents = // This should be a JSON file.
					file_get_contents($_FILES[__NAMESPACE__]['tmp_name']['import_options']);
				unlink($_FILES[__NAMESPACE__]['tmp_name']['import_options']);

				$args = wp_slash(json_decode($import_file_contents, TRUE)); // As new options.
				unset($args['crons_setup']); // Unset; CANNOT be imported (installation-specific).
			}
			$args = array_map('trim', stripslashes_deep((array)$args));

			if(isset($args['base_dir'])) // No leading/trailing slashes please.
				$args['base_dir'] = trim($args['base_dir'], '\\/'." \t\n\r\0\x0B");

			$this->plugin->options = array_merge($this->plugin->default_options, $args);
			$this->plugin->options = array_intersect_key($this->plugin->options, $this->plugin->default_options);

			if(!trim($this->plugin->options['base_dir'], '\\/'." \t\n\r\0\x0B") // Empty?
			   || strpos(basename($this->plugin->options['base_dir']), 'wp-') === 0 // Reserved?
			) $this->plugin->options['base_dir'] = $this->plugin->default_options['base_dir'];

			update_option(__NAMESPACE__.'_options', $this->plugin->options); // Blog-specific.
			if(is_multisite()) update_site_option(__NAMESPACE__.'_options', $this->plugin->options);

			$redirect_to = self_admin_url('/admin.php'); // Redirect preparations.
			$query_args  = array('page' => __NAMESPACE__, __NAMESPACE__.'__updated' => '1');

			$this->plugin->auto_wipe_cache(); // May produce a notice.

			if($this->plugin->options['enable']) // Enable.
			{
				if(!($add_wp_cache_to_wp_config = $this->plugin->add_wp_cache_to_wp_config()))
					$query_args[__NAMESPACE__.'__wp_config_wp_cache_add_failure'] = '1';

				if(!($add_advanced_cache = $this->plugin->add_advanced_cache()))
					$query_args[__NAMESPACE__.'__advanced_cache_add_failure']
						= ($add_advanced_cache === NULL)
						? 'qc-advanced-cache' : '1';

				$this->plugin->update_blog_paths();
			}
			else // We need to disable Quick Cache in this case.
			{
				if(!($remove_wp_cache_from_wp_config = $this->plugin->remove_wp_cache_from_wp_config()))
					$query_args[__NAMESPACE__.'__wp_config_wp_cache_remove_failure'] = '1';

				if(!($remove_advanced_cache = $this->plugin->remove_advanced_cache()))
					$query_args[__NAMESPACE__.'__advanced_cache_remove_failure'] = '1';
			}
			$redirect_to = add_query_arg(urlencode_deep($query_args), $redirect_to);

			wp_redirect($redirect_to).exit(); // All done :-)
		}

		public function restore_default_options($args)
		{
			if(!current_user_can($this->plugin->cap))
				return; // Nothing to do.

			if(empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce']))
				return; // Unauthenticated POST data.

			delete_option(__NAMESPACE__.'_options'); // Blog-specific.
			if(is_multisite()) delete_site_option(__NAMESPACE__.'_options');
			$this->plugin->options = $this->plugin->default_options;

			$redirect_to = self_admin_url('/admin.php'); // Redirect preparations.
			$query_args  = array('page' => __NAMESPACE__, __NAMESPACE__.'__restored' => '1');

			$this->plugin->auto_wipe_cache(); // May produce a notice.

			if($this->plugin->options['enable']) // Enable.
			{
				if(!($add_wp_cache_to_wp_config = $this->plugin->add_wp_cache_to_wp_config()))
					$query_args[__NAMESPACE__.'__wp_config_wp_cache_add_failure'] = '1';

				if(!($add_advanced_cache = $this->plugin->add_advanced_cache()))
					$query_args[__NAMESPACE__.'__advanced_cache_add_failure']
						= ($add_advanced_cache === NULL)
						? 'qc-advanced-cache' : '1';

				$this->plugin->update_blog_paths();
			}
			else // We need to disable Quick Cache in this case.
			{
				if(!($remove_wp_cache_from_wp_config = $this->plugin->remove_wp_cache_from_wp_config()))
					$query_args[__NAMESPACE__.'__wp_config_wp_cache_remove_failure'] = '1';

				if(!($remove_advanced_cache = $this->plugin->remove_advanced_cache()))
					$query_args[__NAMESPACE__.'__advanced_cache_remove_failure'] = '1';
			}
			$redirect_to = add_query_arg(urlencode_deep($query_args), $redirect_to);

			wp_redirect($redirect_to).exit(); // All done :-)
		}

		public function export_options($args)
		{
			if(!current_user_can($this->plugin->cap))
				return; // Nothing to do.

			if(empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce']))
				return; // Unauthenticated POST data.

			ini_set('zlib.output_compression', FALSE);
			if($this->plugin->function_is_possible('apache_setenv'))
				apache_setenv('no-gzip', '1');

			while(@ob_end_clean()) ; // Cleans output buffers.

			$export    = json_encode($this->plugin->options);
			$file_name = __NAMESPACE__.'-options.json';

			nocache_headers();
			header('Accept-Ranges: none');
			header('Content-Encoding: none');
			header('Content-Length: '.strlen($export));
			header('Content-Type: application/json; charset=UTF-8');
			header('Content-Disposition: attachment; filename="'.$file_name.'"');
			exit($export); // Deliver the export file.
		}

		public function update_sync($args)
		{
			if(!current_user_can($this->plugin->update_cap))
				return; // Nothing to do.

			if(empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce']))
				return; // Unauthenticated POST data.

			$args = array_map('trim', stripslashes_deep((array)$args));

			if(empty($args['username'])) $args['username'] = $this->plugin->options['update_sync_username'];
			if(empty($args['password'])) $args['password'] = $this->plugin->options['update_sync_password'];
			if(!isset($args['version_check'])) $args['version_check'] = $this->plugin->options['update_sync_version_check'];

			$update_sync_url       = 'https://www.websharks-inc.com/products/update-sync.php';
			$update_sync_post_vars = array('data' => array('slug'     => str_replace('_', '-', __NAMESPACE__).'-pro', 'version' => 'latest-stable',
			                                               'username' => $args['username'], 'password' => $args['password']));

			$update_sync_response = wp_remote_post($update_sync_url, array('body' => $update_sync_post_vars));
			$update_sync_response = json_decode(wp_remote_retrieve_body($update_sync_response), TRUE);

			if(!is_array($update_sync_response) || !empty($update_sync_response['error'])
			   || empty($update_sync_response['version']) || empty($update_sync_response['zip'])
			) // Report errors in all of these cases. Redirect errors to `update-sync` page.
			{
				if(!empty($update_sync_response['error'])) $error = $update_sync_response['error'];
				else $error = __('Unknown error. Please wait 15 minutes and try again.', $this->plugin->text_domain);

				$redirect_to = self_admin_url('/admin.php'); // Redirect preparations.
				$query_args  = array('page' => __NAMESPACE__.'-update-sync', __NAMESPACE__.'__error' => $error);
				$redirect_to = add_query_arg(urlencode_deep($query_args), $redirect_to);

				wp_redirect($redirect_to).exit(); // Done; with errors.
			}
			$this->plugin->options['update_sync_username']           = $args['username']; // Update username.
			$this->plugin->options['update_sync_password']           = $args['password']; // Update password.
			$this->plugin->options['update_sync_version_check']      = $args['version_check']; // Check version?
			$this->plugin->options['last_update_sync_version_check'] = time(); // Update this; we just checked :-)
			update_option(__NAMESPACE__.'_options', $this->plugin->options); // Save each of these options.
			if(is_multisite()) update_site_option(__NAMESPACE__.'_options', $this->plugin->options);

			$notices = (is_array($notices = get_option(__NAMESPACE__.'_notices'))) ? $notices : array();
			unset($notices['persistent-update-sync-version']); // Dismiss this notice.
			update_option(__NAMESPACE__.'_notices', $notices); // Update notices.

			$redirect_to = self_admin_url('/update.php'); // Runs update routines in WordPress.
			$query_args  = array('action'                         => 'upgrade-plugin', 'plugin' => plugin_basename($this->plugin->file),
			                     '_wpnonce'                       => wp_create_nonce('upgrade-plugin_'.plugin_basename($this->plugin->file)),
			                     __NAMESPACE__.'__update_version' => $update_sync_response['version'],
			                     __NAMESPACE__.'__update_zip'     => base64_encode($update_sync_response['zip']));
			$redirect_to = add_query_arg(urlencode_deep($query_args), $redirect_to);

			wp_redirect($redirect_to).exit(); // All done :-)
		}

		public function dismiss_notice($args)
		{
			if(!current_user_can($this->plugin->cap))
				return; // Nothing to do.

			if(empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce']))
				return; // Unauthenticated POST data.

			$args = array_map('trim', stripslashes_deep((array)$args));
			if(empty($args['key'])) return; // Nothing to dismiss.

			$notices = (is_array($notices = get_option(__NAMESPACE__.'_notices'))) ? $notices : array();
			unset($notices[$args['key']]); // Dismiss this notice.
			update_option(__NAMESPACE__.'_notices', $notices);

			wp_redirect(remove_query_arg(__NAMESPACE__)).exit();
		}

		public function dismiss_error($args)
		{
			if(!current_user_can($this->plugin->cap))
				return; // Nothing to do.

			if(empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce']))
				return; // Unauthenticated POST data.

			$args = array_map('trim', stripslashes_deep((array)$args));
			if(empty($args['key'])) return; // Nothing to dismiss.

			$errors = (is_array($errors = get_option(__NAMESPACE__.'_errors'))) ? $errors : array();
			unset($errors[$args['key']]); // Dismiss this error.
			update_option(__NAMESPACE__.'_errors', $errors);

			wp_redirect(remove_query_arg(__NAMESPACE__)).exit();
		}
	}

	new actions(); // Initialize/handle actions.
}