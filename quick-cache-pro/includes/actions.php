<?php
namespace quick_cache // Root namespace.
	{
		if(!defined('WPINC')) // MUST have WordPress.
			exit('Do NOT access this file directly: '.basename(__FILE__));

		class actions // Action handlers.
		{
			public function __construct()
				{
					if(empty($_REQUEST[__NAMESPACE__])) return;
					foreach((array)$_REQUEST[__NAMESPACE__] as $action => $args)
						if(method_exists($this, $action)) $this->{$action}($args);
				}

			public function clear_cache($args)
				{
					if(!current_user_can(plugin()->cap))
						return; // Nothing to do.

					if(empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce']))
						return; // Unauthenticated POST data.

					if(plugin()->options['cache_clear_s2clean_enable'])
						if(function_exists('s2clean')) s2clean()->md_cache_clear();

					$counter = plugin()->clear_cache(TRUE); // Counter.

					$redirect_to = self_admin_url('/admin.php'); // Redirect preparations.
					$query_args  = array('page' => __NAMESPACE__, __NAMESPACE__.'__cache_cleared' => '1');
					$redirect_to = add_query_arg(urlencode_deep($query_args), $redirect_to);

					wp_redirect($redirect_to).exit(); // All done :-)
				}

			public function ajax_clear_cache($args)
				{
					if(!current_user_can(plugin()->cap))
						return; // Nothing to do.

					if(empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce']))
						return; // Unauthenticated POST data.

					if(plugin()->options['cache_clear_s2clean_enable'])
						if(function_exists('s2clean')) $s2clean_counter = s2clean()->md_cache_clear();

					$counter = plugin()->clear_cache(TRUE); // Counter.

					$response = sprintf(__('<p><strong>Cleared a total of <code>%1$s</code> cache files.</strong></p>', plugin()->text_domain), $counter);
					$response .= __('<p>Cache reset for this site; recreation will occur automatically over time.</p>', plugin()->text_domain);
					if(isset($s2clean_counter)) $response .= sprintf(__('<p><strong>Also cleared <code>%1$s</code> s2Clean cache files.</strong></p>', plugin()->text_domain), $s2clean_counter);

					exit($response); // JavaScript will take it from here.
				}

			public function save_options($args)
				{
					if(!current_user_can(plugin()->cap))
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
					$args             = array_map('trim', stripslashes_deep((array)$args));
					plugin()->options = array_merge(plugin()->default_options, $args);

					if(!trim(plugin()->options['cache_dir'], '\\/'." \t\n\r\0\x0B") // Empty (do not allow).
					   || strpos(basename(plugin()->options['cache_dir']), 'wp-') === 0 // Reserved name?
					) plugin()->options['cache_dir'] = plugin()->default_options['cache_dir'];

					update_option(__NAMESPACE__.'_options', $args);

					$redirect_to = self_admin_url('/admin.php'); // Redirect preparations.
					$query_args  = array('page' => __NAMESPACE__, __NAMESPACE__.'__updated' => '1');

					plugin()->auto_clear_cache(); // May produce a notice.

					if(plugin()->options['enable']) // Enable.
						{
							if(!($add_wp_cache_to_wp_config = plugin()->add_wp_cache_to_wp_config()))
								$query_args[__NAMESPACE__.'__wp_config_wp_cache_add_failure'] = '1';

							if(!($add_advanced_cache = plugin()->add_advanced_cache()))
								$query_args[__NAMESPACE__.'__advanced_cache_add_failure']
									= ($add_advanced_cache === NULL)
									? 'qc-advanced-cache' : '1';
						}
					else // We need to disable Quick Cache in this case.
						{
							if(!($remove_wp_cache_from_wp_config = plugin()->remove_wp_cache_from_wp_config()))
								$query_args[__NAMESPACE__.'__wp_config_wp_cache_remove_failure'] = '1';

							if(!($remove_advanced_cache = plugin()->remove_advanced_cache()))
								$query_args[__NAMESPACE__.'__advanced_cache_remove_failure'] = '1';
						}
					$redirect_to = add_query_arg(urlencode_deep($query_args), $redirect_to);

					wp_redirect($redirect_to).exit(); // All done :-)
				}

			public function restore_default_options($args)
				{
					if(!current_user_can(plugin()->cap))
						return; // Nothing to do.

					if(empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce']))
						return; // Unauthenticated POST data.

					delete_option(__NAMESPACE__.'_options');

					$redirect_to = self_admin_url('/admin.php'); // Redirect preparations.
					$query_args  = array('page' => __NAMESPACE__, __NAMESPACE__.'__restored' => '1');

					plugin()->auto_clear_cache(); // May produce a notice.

					if(plugin()->options['enable']) // Enable.
						{
							if(!($add_wp_cache_to_wp_config = plugin()->add_wp_cache_to_wp_config()))
								$query_args[__NAMESPACE__.'__wp_config_wp_cache_add_failure'] = '1';

							if(!($add_advanced_cache = plugin()->add_advanced_cache()))
								$query_args[__NAMESPACE__.'__advanced_cache_add_failure']
									= ($add_advanced_cache === NULL)
									? 'qc-advanced-cache' : '1';
						}
					else // We need to disable Quick Cache in this case.
						{
							if(!($remove_wp_cache_from_wp_config = plugin()->remove_wp_cache_from_wp_config()))
								$query_args[__NAMESPACE__.'__wp_config_wp_cache_remove_failure'] = '1';

							if(!($remove_advanced_cache = plugin()->remove_advanced_cache()))
								$query_args[__NAMESPACE__.'__advanced_cache_remove_failure'] = '1';
						}
					$redirect_to = add_query_arg(urlencode_deep($query_args), $redirect_to);

					wp_redirect($redirect_to).exit(); // All done :-)
				}

			public function export_options($args)
				{
					if(!current_user_can(plugin()->cap))
						return; // Nothing to do.

					if(empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce']))
						return; // Unauthenticated POST data.

					ini_set('zlib.output_compression', FALSE);
					if(function_exists('apache_setenv'))
						apache_setenv('no-gzip', '1');

					if(ob_get_level()) // Cleans output buffers.
						while(ob_get_level()) ob_end_clean();

					$export    = json_encode(plugin()->options);
					$file_name = __NAMESPACE__.'-options.json';

					nocache_headers();
					header('Accept-Ranges: none');
					header('Content-Encoding: none');
					header('Content-Length: '.strlen($export));
					header('Content-Type: application/json; charset=UTF-8');
					header('Content-Disposition: attachment; filename="'.$file_name.'"');
					exit($export); // Deliver the export file.
				}
		}

		new actions(); // Initialize/handle actions.
	}