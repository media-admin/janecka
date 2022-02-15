<?php 

function pmae_admin_notices() {
	// notify user if history folder is not writable		
	if ( ! class_exists( 'PMXE_Plugin' ) /*|| PMXE_EDITION == 'free' */ ) {
		?>
		<div class="error"><p>
			<?php printf(
					__('<b>%s Plugin</b>: WP All Export Pro must be installed and activated. You can download it here <a href="https://www.wpallimport.com/portal" target="_blank">https://www.wpallimport.com/portal/</a>', 'PMAE_Plugin'),
					PMAE_Plugin::getInstance()->getName()
			) ?>
		</p></div>
		<?php
		
		deactivate_plugins( PMAE_ROOT_DIR . '/wpae-acf-add-on-pro.php');
		return;
		
	}

	if ( class_exists( 'PMXE_Plugin' ) and ( version_compare(PMXE_VERSION, '1.5.6') < 0 and PMXE_EDITION != 'paid') and 0 ) {
		?>
		<div class="error"><p>
			<?php printf(
					__('<b>%s Plugin</b>: Please update your WP All Export to the latest version', 'wp_all_export_acf_add_on'),
					PMAE_Plugin::getInstance()->getName()
			) ?>
		</p></div>
		<?php
		
		deactivate_plugins( PMAE_ROOT_DIR . '/wpae-acf-add-on-pro.php');
	}

	$input = new PMAE_Input();
	$messages = $input->get('pmae_nt', array());
	if ($messages) {
		is_array($messages) or $messages = array($messages);
		foreach ($messages as $type => $m) {
			in_array((string)$type, array('updated', 'error')) or $type = 'updated';
			?>
			<div class="<?php echo $type ?>"><p><?php echo $m ?></p></div>
			<?php 
		}
	}

	if ( ! empty($_GET['type']) and $_GET['type'] == 'user'){
		?>
		<script type="text/javascript">
			(function($){$(function () {
				$('#toplevel_page_pmxi-admin-home').find('.wp-submenu').find('li').removeClass('current');
				$('#toplevel_page_pmxi-admin-home').find('.wp-submenu').find('a').removeClass('current');
				$('#toplevel_page_pmxi-admin-home').find('.wp-submenu').find('li').eq(2).addClass('current').find('a').addClass('current');
			});})(jQuery);
		</script>
		<?php
	}
}
