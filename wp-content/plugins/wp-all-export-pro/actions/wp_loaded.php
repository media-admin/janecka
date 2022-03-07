<?php

function pmxe_wp_loaded() {

	$scheduledExport = new \Wpae\Scheduling\Export();

	if ( ! empty($_GET['zapier_subscribe']) and ! empty($_GET['api_key']) )
	{
	    pmxe_set_max_execution_time();
		$zapier_api_key = PMXE_Plugin::getInstance()->getOption('zapier_api_key');

		if ( ! empty($zapier_api_key) and $zapier_api_key == $_GET['api_key'] )
		{
			$subscriptions = get_option('zapier_subscribe', array());

			$body = json_decode(file_get_contents("php://input"), true);

			if ( ! empty($body))
			{
				$subscriptions[basename($body['target_url'])] = $body;
			}

			update_option('zapier_subscribe', $subscriptions);

			exit(json_encode(array('status' => 200)));
		}
		else
		{
			http_response_code(401);
			exit(json_encode(array('status' => 'error')));
		}
	}

	if ( ! empty($_GET['zapier_unsubscribe']) and ! empty($_GET['api_key']) )
	{
	    pmxe_set_max_execution_time();
		$zapier_api_key = PMXE_Plugin::getInstance()->getOption('zapier_api_key');

		if ( ! empty($zapier_api_key) and $zapier_api_key == $_GET['api_key'] )
		{
			$subscriptions = get_option('zapier_subscribe', array());

			$body = json_decode(file_get_contents("php://input"), true);

			if ( ! empty($subscriptions[basename($body['target_url'])]) ) unset($subscriptions[basename($body['target_url'])]);

			update_option('zapier_subscribe', $subscriptions);

			exit(json_encode(array('status' => 200)));
		}
		else
		{
			http_response_code(401);
			exit(json_encode(array('status' => 'error')));
		}
	}

	if ( ! empty($_GET['export_completed']) and ! empty($_GET['api_key']))
	{
        pmxe_set_max_execution_time();
		$zapier_api_key = PMXE_Plugin::getInstance()->getOption('zapier_api_key');

		if ( ! empty($zapier_api_key) and $zapier_api_key == $_GET['api_key'] )
		{

			global $wpdb;

			$table_prefix = PMXE_Plugin::getInstance()->getTablePrefix();

			$export = $wpdb->get_row("SELECT * FROM {$table_prefix}exports ORDER BY `registered_on` DESC LIMIT 1");

			if ( ! empty($export) and ! is_wp_error($export) )
			{
				$child_export = new PMXE_Export_Record();
				$child_export->getById( $export->id );

				if ( ! $child_export->isEmpty())
				{
					$wp_uploads = wp_upload_dir();

					$is_secure_import = PMXE_Plugin::getInstance()->getOption('secure');

					if ( ! $is_secure_import)
					{
						$filepath = get_attached_file($child_export->attch_id);
					}
					else
					{
						$filepath = wp_all_export_get_absolute_path($child_export->options['filepath']);
					}

					$fileurl = str_replace($wp_uploads['basedir'], $wp_uploads['baseurl'], $filepath);

					$response = array(
						'website_url' => home_url(),
						'export_id' => $child_export->id,
						'export_name' => $child_export->friendly_name,
						'file_name' => basename($filepath),
						'file_type' => $child_export->options['export_to'],
						'post_types_exported' => empty($child_export->options['cpt']) ? $child_export->options['wp_query'] : implode(',', $child_export->options['cpt']),
						'export_created_date' => $child_export->registered_on,
						'export_last_run_date' => date('Y-m-d H:i:s'),
						'export_trigger_type' => 'manual',
						'records_exported' => $child_export->exported,
						'export_file' => ''
					);

					if (file_exists($filepath))
					{
						$response['export_file_url'] = $fileurl;
						$response['status'] = 200;
						$response['message'] = 'OK';
					}
					else
					{
						$response['export_file_url'] = '';
						$response['status'] = 300;
						$response['message'] = 'File doesn\'t exist';
					}

					$response = apply_filters('wp_all_export_zapier_response', $response);

					wp_send_json(array($response));
				}
			}

		}
		else
		{
			http_response_code(401);
			exit(json_encode(array('status' => 'error')));
		}
	}

	/* Check if cron is manualy, then execute export */
	$cron_job_key = PMXE_Plugin::getInstance()->getOption('cron_job_key');

	if ( ! empty($cron_job_key) and ! empty($_GET['export_id']) and ! empty($_GET['export_key']) and $_GET['export_key'] == $cron_job_key and !empty($_GET['action']) and in_array($_GET['action'], array('processing', 'trigger'))) {
        pmxe_set_max_execution_time();
		$logger = function($m) {
		    echo "<p>$m</p>\\n";
		};

		$export = new PMXE_Export_Record();

		$ids = explode(',', $_GET['export_id']);

		$queue_exports = empty($export->parent_id) ? get_option( 'wp_all_export_queue_' . esc_sql(strip_tags($_GET['export_id']))) : false;

		if ( ! empty($queue_exports) and is_array($queue_exports))
		{
			$ids = array_merge($ids, $queue_exports);
		}

		if ( ! empty($ids) and is_array($ids) )
		{
			foreach ($ids as $id) { if (empty($id)) continue;

				$export->getById($id);

				$cpt = $export->options['cpt'];
				if(!is_array($cpt)) {
				    $cpt = array($cpt);
                }

                $addons = new \Wpae\App\Service\Addons\AddonService();
                if(
                    ((in_array('users', $cpt) || in_array('shop_customer', $cpt)) && !$addons->isUserAddonActive())
                    ||
                    ($export->options['export_type'] == 'advanced' && $export->options['wp_query_selector'] == 'wp_user_query' && !$addons->isUserAddonActive())
                ) {
                    die(wp_kses_post(\__('The User Export Add-On Pro is required to run this export. If you already own it, you can download the add-on here: <a href="https://www.wpallimport.com/portal/downloads" target="_blank">https://www.wpallimport.com/portal/downloads</a>', \PMXE_Plugin::LANGUAGE_DOMAIN)));
                }

                if(
                    (!$addons->isWooCommerceAddonActive() && !$addons->isWooCommerceProductAddonActive() && strpos($export->options['wp_query'], 'product') !== false && \class_exists('WooCommerce')) ||
                    (!$addons->isWooCommerceAddonActive() && !$addons->isWooCommerceOrderAddonActive() && strpos($export->options['wp_query'], 'shop_order') !== false) ||
                    (!$addons->isWooCommerceAddonActive() && strpos($export->options['wp_query'], 'shop_coupon') !== false)

                ) {
                    die(wp_kses_post(\__('The WooCommerce Export Add-On Pro is required to run this export. If you already own it, you can download the add-on here: <a href="https://www.wpallimport.com/portal/downloads" target="_blank">https://www.wpallimport.com/portal/downloads</a>', \PMXE_Plugin::LANGUAGE_DOMAIN)));
                }

                if (((in_array('product', $cpt) && in_array('product_variation', $cpt) ) ||
                     (in_array('shop_order', $cpt) && !$addons->isWooCommerceOrderAddonActive()) ||
                     in_array('shop_coupon', $cpt) ||
                     in_array('shop_review', $cpt) ) &&
                    !$addons->isWooCommerceAddonActive()) {
					die(wp_kses_post(\__('The WooCommerce Export Add-On Pro is required to run this export. If you already own it, you can download the add-on here: <a href="https://www.wpallimport.com/portal/downloads" target="_blank">https://www.wpallimport.com/portal/downloads</a>', \PMXE_Plugin::LANGUAGE_DOMAIN)));
				}

                // Block Google Merchant Exports if the supporting add-on isn't active.
				if(isset($export->options['xml_template_type']) && $export->options['xml_template_type'] == \XmlExportEngine::EXPORT_TYPE_GOOLE_MERCHANTS && !$addons->isWooCommerceAddonActive()) {

					die(wp_kses_post(\__('The WooCommerce Export Add-On Pro is required to run this export. If you already own it, you can download the add-on here: <a href="https://www.wpallimport.com/portal/downloads" target="_blank">https://www.wpallimport.com/portal/downloads</a>', \PMXE_Plugin::LANGUAGE_DOMAIN)));

				}

					if((in_array('acf', $export->options['cc_type']) || $export->options['xml_template_type'] == 'custom' && in_array('acf', $export->options['custom_xml_template_options']['cc_type'])) && !$addons->isAcfAddonActive()) {
					die(wp_kses_post(\__('The ACF Export Add-On Pro is required to run this export. If you already own it, you can download the add-on here: <a href="https://www.wpallimport.com/portal/downloads" target="_blank">https://www.wpallimport.com/portal/downloads</a>', \PMXE_Plugin::LANGUAGE_DOMAIN)));
				}

                $cpt_string = reset($cpt);

                if(strpos($cpt_string, 'custom_') === 0 && !class_exists('GF_Export_Add_On')) {
                    die('The Gravity Forms Export Add-On Pro is required for this export. If you already own it, you can download the add-on here: <a href="https://www.wpallimport.com/portal/downloads" target="_blank">https://www.wpallimport.com/portal/downloads</a>');
                }

				if ( ! $export->isEmpty() ){

					switch ($_GET['action']) {

						case 'trigger':

							if ( (int) $export->executing )
							{
								wp_send_json(array(
									'status'     => 403,
									'message'    => sprintf(esc_html__('Export #%s is currently in manually process. Request skipped.', 'wp_all_export_plugin'), $id)
								));
							}
							elseif ( ! $export->processing and ! $export->triggered )
							{
                                $scheduledExport->trigger($export);

								wp_send_json(array(
									'status'     => 200,
									'message'    => sprintf(esc_html__('#%s Cron job triggered.', 'wp_all_export_plugin'), $id)
								));
							}
							elseif( $export->processing and ! $export->triggered)
							{
								wp_send_json(array(
									'status'     => 403,
									'message'    => sprintf(esc_html__('Export #%s currently in process. Request skipped.', 'wp_all_export_plugin'), $id)
								));
							}
							elseif( ! $export->processing and $export->triggered)
							{
								wp_send_json(array(
									'status'     => 403,
									'message'    => sprintf(esc_html__('Export #%s already triggered. Request skipped.', 'wp_all_export_plugin'), $id)
								));
							}

							break;

						case 'processing':

							if ( $export->processing == 1 and (time() - strtotime($export->registered_on)) > 120){ // it means processor crashed, so it will reset processing to false, and terminate. Then next run it will work normally.
								$export->set(array(
									'processing' => 0
								))->update();
							}

							// start execution imports that is in the cron process												
							if ( ! (int) $export->triggered )
							{
								if ( ! empty($export->parent_id) or empty($queue_exports))
								{
									wp_send_json(array(
										'status'     => 403,
										'message'    => sprintf(esc_html__('Export #%s is not triggered. Request skipped.', 'wp_all_export_plugin'), $id)
									));
								}
							}
							elseif ( (int) $export->executing )
							{
								wp_send_json(array(
									'status'     => 403,
									'message'    => sprintf(esc_html__('Export #%s is currently in manually process. Request skipped.', 'wp_all_export_plugin'), $id)
								));
							}
							elseif ( (int) $export->triggered and ! (int) $export->processing )
							{
							    try {
                                    $export->set(array('canceled' => 0))->execute($logger, true);
                                } catch (\Wpae\App\Service\Addons\AddonNotFoundException $e) {
							        die($e->getMessage());
                                }
								if ( ! (int) $export->triggered and ! (int) $export->processing )
								{
                                    $scheduledExport->process($export);

                                    wp_send_json(array(
										'status'     => 200,
										'message'    => sprintf(esc_html__('Export #%s complete', 'wp_all_export_plugin'), $export->id)
									));
								}
								else
								{
									wp_send_json(array(
										'status'     => 200,
										'message'    => sprintf(esc_html__('Records Processed %s.', 'wp_all_export_plugin'), (int) $export->exported)
									));
								}

							}
							else
							{
								wp_send_json(array(
									'status'     => 403,
									'message'    => sprintf(esc_html__('Export #%s already processing. Request skipped.', 'wp_all_export_plugin'), $id)
								));
							}

							break;
					}
				}
			}
		}
	}

	if ( ! empty($_GET['action']) && ! empty($_GET['export_id']) && (!empty($_GET['export_hash']) || !empty($_GET['security_token'])))
	{
        pmxe_set_max_execution_time();
		$securityToken = '';
		if(empty($_GET['export_hash'])) {
			$securityToken = $_GET['security_token'];
		} else {
			$securityToken = $_GET['export_hash'];
		}

		if ( $securityToken == substr(md5($cron_job_key . $_GET['export_id']), 0, 16) )
		{
			$export = new PMXE_Export_Record();

			$export->getById($_GET['export_id']);

			if ( ! $export->isEmpty())
			{
				switch ($_GET['action'])
				{
					case 'get_data':

						if ( ! empty($export->options['current_filepath']) and @file_exists($export->options['current_filepath']))
						{
							$filepath = $export->options['current_filepath'];
						}
						else
						{
							$is_secure_import = PMXE_Plugin::getInstance()->getOption('secure');

							if ( ! $is_secure_import)
							{
								$filepath = get_attached_file($export->attch_id);
							}
							else
							{
								$filepath = wp_all_export_get_absolute_path($export->options['filepath']);
							}
						}

						if ( ! empty($_GET['part']) and is_numeric($_GET['part'])) $filepath = str_replace(basename($filepath), str_replace('.' . $export->options['export_to'], '', basename($filepath)) . '-' . $_GET['part'] . '.' . $export->options['export_to'], $filepath);

						break;

					case 'get_bundle':

						$filepath = wp_all_export_get_absolute_path($export->options['bundlepath']);

						break;

					default:
						# code...
						break;
				}

				if (file_exists($filepath))
				{
					$uploads  = wp_upload_dir();
					$fileurl = $uploads['baseurl'] . str_replace($uploads['basedir'], '', str_replace(basename($filepath), rawurlencode(basename($filepath)), $filepath));

					if($export['options']['export_to'] == XmlExportEngine::EXPORT_TYPE_XML && $export['options']['xml_template_type'] == XmlExportEngine::EXPORT_TYPE_GOOLE_MERCHANTS) {

						// If we are doing a google merchants export, send the file as a download.
						header("Content-type: text/plain");
						header("Content-Disposition: attachment; filename=".basename($filepath));
						if ( ob_get_length() !== false ) {
							ob_end_clean();
						}
						readfile($filepath);

						die;
					}
					if(apply_filters('wp_all_export_no_cache', false)) {

                        // If we are doing a google merchants export, send the file as a download.
                        header("Content-type: " . mime_content_type($filepath));
                        header("Content-Disposition: attachment; filename=" . basename($filepath));
                        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
                        header("Cache-Control: post-check=0, pre-check=0", false);
                        header("Pragma: no-cache");

                        readfile($filepath);

                        die;
                    }

                    $fileurl = str_replace("\\", "/", $fileurl);

					// Add random parameter to avoid caching
					if( strpos( $fileurl, '?' ) !== false ){
						$fileurl .= '&wpae_nocache=' . mt_rand();
					}else{
						$fileurl .= '?wpae_nocache=' . mt_rand();
				    }

                    wp_redirect($fileurl);
				}
				else
				{
					wp_send_json(array(
						'status'     => 403,
						'message'    => esc_html__('File doesn\'t exist', 'wp_all_export_plugin')
					));
				}
			}
		}
		else
		{
			wp_send_json(array(
				'status'     => 403,
				'message'    => esc_html__('Export hash is not valid.', 'wp_all_export_plugin')
			));
		}
	}

    if(isset($_GET['action']) && $_GET['action'] == 'wpae_public_api') {
	    pmxe_set_max_execution_time();
        $router = new \Wpae\Http\Router();
        $router->route($_GET['q'], false);
    }
}

function pmxe_set_max_execution_time()
{
    @ini_set("max_input_time", PMXE_Plugin::getInstance()->getOption('max_input_time'));

    $maxExecutionTime  = PMXE_Plugin::getInstance()->getOption('max_execution_time');
    if($maxExecutionTime == -1) {
        $maxExecutionTime = 0;
    }

    @ini_set("max_execution_time", $maxExecutionTime);
}