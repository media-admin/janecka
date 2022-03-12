<?php
/**
 * weDevs Settings API wrapper class
 */
namespace CMoreira\Plugins\DateTimePicker\Admin;

use CMoreira\Plugins\DateTimePicker\Integration\IntegrationHelper as IntegrationHelper;
use CMoreira\Plugins\DateTimePicker\Integration\ManualIntegration as ManualIntegration;

if ( ! class_exists( 'IntegrationAPI' ) ) {
	class IntegrationAPI {

		public function __construct() {

			$this->integration = new IntegrationHelper();
			$this->settings = new SettingsAPI();

	    add_action( 'admin_init', array( $this, 'manual_integration' ) );
		}

		/**
		 * Menu page callback
		 *
		 * @return Html
		 */
		 public function show_forms() {

			 $this->settings->style_fix();
			 $this->integration_style();
			 $integration_data_array = maybe_unserialize(get_option('_dtpicker_new_integration'));
			 ?>

			 <link rel="stylesheet" href="<?php echo plugins_url( '../../assets/css/', __FILE__  ) . 'slider.css'; ?>" />

			 <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons" />
				<?php echo $this->settings->top_bar(); ?>
			 	<div class="dtpkr-wrap">
					<?php settings_errors(); ?>
					<?php
						$value = get_option('dtp_integration');
						settings_fields('dtp_integration_settings');
					?>
					<div class="metabox-holder pt-0">
						<div class="box-group" style="border-bottom: 1px solid #ccc;">
							<div class="main-custom-switch-container">
								<div class="switch-desc">
									<h4 class="tab-main-heading">
										<?php echo __('Form Integration', 'date-time-picker-field'); ?>
									</h4>
								</div>
								<div class="switch-item right" style="display: none;">
									<p class="submit">
										<a href="javascript:void(0);" class="button button-primary button-itegration new-integration">
											<?php _e( 'Add New', 'date-time-picker-field' ); ?>
										</a>
									</p>
								</div>
							</div>
						</div>
						<div id="cf7-lite-append-to" class="cf7-lite-append-to">
							<?php
							if (is_array($integration_data_array) && count($integration_data_array) > 0) {
								foreach ($integration_data_array as $int_key => $integration_data) {
									if ($integration_data['plugin'] == 'manual') :
										$is_manual = true;
										$this->integration_template($int_key, $integration_data);
									endif;
								}
							}
							if(!$is_manual) {
								$integration_data['plugin'] == 'manual';
								$this->integration_template($int_key, $integration_data);
							}
							?>
						</div>
					</div>
					<div class="documentation-wrap">
            			<label>Documentation</label>
						<ul>
							<li>
								<a target="_blank" href="https://www.inputwp.com/documentation/how-to-use-manual-integration-css-selector">
									How to use Manual integration
								</a>
							</li>
						</ul>
        			</div>
				</div>

			 <div class="integration-sample dtpicker-hide">
				 <div class="manual-temp">
					 <?php $this->integration_template(null, []); ?>
				 </div>
			 </div>

			 <script>
			 jQuery(document).ready(function($) {
				 $('#copy-trigger').on('click', function() {
 					var copyText = document.getElementById("class_to_be_copied");
 					copyText.select();
   				copyText.setSelectionRange(0, 99999);
 					document.execCommand("copy");
 					jQuery(this).text('<?php _e('Copied', 'date-time-picker-field'); ?>');
 				});
			 });
			 </script>
		<?php
		}


		public function integration_template($int_key, $integration_data) {

			$pickers = $this->integration->get_date_time_pickers(esc_attr($integration_data['picker'])); ?>

			<div class="advertisement-wrap new-integration-content">
				<div class="custom-switch-container horizontal-bottom cf7-lite-tab" style="display: none;">
					<div class="switch-desc">
						<h4 class="global-heading cf7-lite-heading"><?php echo ( !empty($integration_data['label']) ? esc_attr($integration_data['label']) : __('Sample', 'date-time-picker-field') ); ?></h4>
					</div>
					<div class="switch-item">
						<div class="cf7-lite-tab-trigger">
							<span class="cf7-lite-tab_close material-icons right">keyboard_arrow_down</span>
							<span class="cf7-lite-tab_open material-icons right">keyboard_arrow_up</span>
						</div>
					</div>
				</div>
				<form method="post" action="" class="cf7-lite-tab-content" novalidate="novalidate">
					<div class="field-group-wrap" style="display: none;">
						<h5 class="pt-0">Introduction</h5>
						<div class="field-group">
							<label>
								<?php _e('Label(optional)', 'date-time-picker-field') ?>
							</label>
							<div class="description-text">
								<p>
									<?php _e('Give your calendar a name that will be showing as integration name.') ?>
								</p>
							</div>
							<div class="field-input">
								<input type="text" name="integration[label]" class="regular-text cf7-lite-label" value="<?php echo ( !empty($integration_data['label']) ? esc_attr($integration_data['label']) : __('Sample', 'date-time-picker-field') ); ?>"/>
							</div>
						</div>
					</div>
					<div class="field-group-wrap" style="display: none;">
						<div class="field-group">
							<label>
								<?php _e('Method', 'date-time-picker-field'); ?>
							</label>
							<div class="description-text">
								<p><?php _e('Select the method that you will use to integrate with a 3rd party form.') ?></p>
							</div>
							<div class="field-input">
								<select name="integration[plugin]" class="regular-text" autocomplete="off">
									<?php foreach ($this->integration->plugins as $key => $value) : ?>
									<option value="<?php echo $key; ?>" <?php selected($key, $integration_data['plugin'], true); ?>><?php echo $value; ?></option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>
					</div>

					<?php $this->manual_integration_template($int_key, $integration_data); ?>

				</form>
			</div>
			<?php
		}


		public function manual_integration_template($int_key, $manual_data) {

			$pickers = $this->integration->get_date_time_pickers(esc_attr($manual_data['picker']));
			?>
			<div class="form-details-container">
				<div class="form-details-item">
					<div class="field-group-wrap" style="display: none;">
						<h5 class="pt-0">Connect</h5>
						<div class="field-group">
							<label>
								<?php _e('Select Calendar', 'date-time-picker-field') ?>
							</label>
							<div class="field-input">
								<select name="integration[picker]" class="regular-text">
									<?php echo $pickers; ?>
								</select>
							</div>
						</div>
					</div>
					<div class="field-group-wrap">
						<div class="field-group">
							<label>
								<?php _e('CSS Selector', 'date-time-picker-field') ?>
							</label>
							<div class="description-text">
								<p>
									<?php _e('Selector of the input field you want to target and transform into a picker. You can enter multiple selectors separated by commas.', 'date-time-picker-field') ?>
								</p>
							</div>
							<div class="field-input">
								<input id="class_to_be_copied" type="text" name="integration[selector]" class="regular-text" value="<?php echo ( !empty($manual_data['selector']) ? esc_attr($manual_data['selector']) : '' ); ?>"/>
								<a href="javascript:void(0);" id="copy-trigger" class="button" style="padding-left: 10px; margin-left: 10px;"><?php _e( 'Copy', 'date-time-picker-field-pro' ); ?></a>
							</div>
							<div class="mt-10 description-text">
								<p class="mt-0 error-color">
									<?php _e('<strong>ATTENTION!</strong> If you want to integrate with Divi, please remove the “.” (dot) before the code.', 'date-time-picker-field') ?>
								</p>
							</div>
						</div>
					</div>
					<div class="field-group-wrap">
						<div class="field-group form-btn-flex">
							<div class="d-flex align-items-center">
								<?php wp_nonce_field( 'new_manual_integration' ); ?>
								<span class="generate-url">
									<input type="submit" class="button button-primary" name="new_manual_integration" id="new_manual_integration" value="<?php echo (empty($manual_data) ? __( 'Save', 'date-time-picker-field' ) : __( 'Update', 'date-time-picker-field' )); ?>" />
								</span>
								<?php if(empty($manual_data)) : ?>
									&nbsp;&nbsp;&nbsp;
									<a href="javascript:void(0);" class="button button-link cancel-manual" style="display: none;"><?php _e( 'Cancel', 'date-time-picker-field' ); ?></a>
								<?php endif; ?>
							</div>
							<?php if(!empty($manual_data)) : ?>
								<div class="delete-btn">
									<input type="submit" class="button button-link link-red right" style="display: none;" name="delete_manual_integration" value="<?php _e( 'Delete', 'date-time-picker-field' ); ?>" />
								</div>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
			<?php
	  }


	  public function integration_style() { ?>

	    <style type="text/css">
	      p.submit, .submit {margin-top: -15px !important;}
	      .button-itegration {padding-left: 17px !important;}
	      .button-import {padding-left: 27px !important;}
	      /* #wpbody-content .metabox-holder .box-group {max-height: 65px;} */
	      .arrow-wrap {margin: 15px auto 20px auto; float: right;}
	      /* .horizontal-bottom {border-bottom: 1px solid #d4d4d4; max-height: 60px;} */
	      .border-bottom-tab {border-bottom: 1px solid #d4d4d4;}
	      .field-group {margin: 5px 20px auto 20px; background: #f1f1f1; padding-bottom: 20px;}
	      .description {margin-top: 10px;}
	      .table-widefat {width: 100%}
	      .link-red {color: #e74c3c !important;}
	      .button-link, .button-link:hover, .button-link:focus, .button-link:active {text-decoration: none;}
	      .copy-url-input {margin-top: 0px !important;}
	      .regular-text{width: 242px;}
	      .cf7-lite-tab-bg, .ics-tab-bg, .export-tab-bg {background: #fff;}
	      /**.cf7-lite-append-to, .ics-append-to, .export-append-to {border-radius: 0px 0px 8px 8px;}**/
	      .cf7-lite-tab, .ics-tab, .export-tab {cursor: pointer;}
	      .cf7-lite-tab-trigger, .ics-tab-trigger, .export-tab-trigger {cursor: pointer;}
	      .dtpicker-hide {display: none;}
	      .multicheck {padding-bottom: 20px;}
	      .dtpkr-wrap, .metabox-holder, .metabox-holder-form {border-radius: 8px; }
	      /**.advertisement-wrap { border-radius: 0px 0px 8px 8px; }*/
	      .top-heading {font-weight: bold;}
		  .description-text .error-color {color: #757575;}
	    </style>
	    <?php
	  }


		public function manual_integration() {

	    if (isset($_POST['new_manual_integration']) && isset($_POST['integration']) && wp_verify_nonce( $_POST['_wpnonce'], 'new_manual_integration' )) {

	      $integration = $_POST['integration'];

	      $label = sanitize_text_field($integration['label']);
	      $plugin = sanitize_text_field($integration['plugin']);
	      $selector = sanitize_text_field($integration['selector']);
	      $picker = intval(sanitize_text_field($integration['picker']));

	      $data = array();

	      if (empty($selector)) {
	        $selector = '.input' . rand(100,999);
	      }

	      $manual = new ManualIntegration($picker);
	      $set = $manual->set_class($selector);

	      $insert = array(
	        array(
	          'label'   => $label,
	          'plugin'  => $plugin,
	          'picker'  => $picker,
	          'selector'=> $selector
	        )
	      );

	      $store = maybe_serialize(array_values($insert));

	      update_option('_dtpicker_new_integration', $store);
	    }

	    if (isset($_POST['delete_manual_integration']) && isset($_POST['integration']) && wp_verify_nonce( $_POST['_wpnonce'], 'new_manual_integration' )) {

	      $integration = $_POST['integration'];
	      $plugin = sanitize_text_field($integration['plugin']);
	      $picker = intval(sanitize_text_field($integration['picker']));
	      $data = maybe_unserialize(get_option('_dtpicker_new_integration'));
	      if (is_array($data) && count($data) > 0) {
	        foreach ($data as $key => $item) {
	          if($item['plugin'] == 'manual' && ($picker == $item['picker'] || $picker == 0)) {
	            unset($data[$key]);
	          }
	        }
	      }

	      $data = array_values($data);

	      update_option('_dtpicker_new_integration', maybe_serialize($data));
	    }
	  }
  }
}
