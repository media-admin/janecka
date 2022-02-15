<div id="cr-integrations-block" class="widgets-holder-wrap">
	<div id="sidebar-1" class="widgets-sortables ui-droppable ui-sortable">
		<div id="cr-integrations-sidebar" class="sidebar-name">
			<button id="cr-sidebar-button" type="button" class="handlediv hide-if-no-js" aria-expanded="true">
				<span class="screen-reader-text">Footer</span>
				<span class="toggle-indicator" aria-hidden="true"></span>
			</button>
			<h2><?php echo esc_html( __( 'Integrations', 'cleverreach-wp' ) );?></h2>
		</div>
		<div id="cr-integration-table" class="sidebar-description">
			<div id="cr-integration-row-header" class="cr-integration-row">
				<div class="cr-integration-column">
					<?php echo esc_html( __( 'Integration name', 'cleverreach-wp' ) );?>
				</div>
				<div class="cr-integration-column">
					<?php echo esc_html( __( 'Status', 'cleverreach-wp' ) );?>
				</div>
			</div>
			<input type="hidden" id="cr-installed" value="<?php echo esc_attr( __( 'Installed', 'cleverreach-wp' ) ) ?>" />
			<input type="hidden" id="cr-not-installed" value="<?php echo esc_attr( __( 'Not installed', 'cleverreach-wp' ) ) ?>" />
		</div>
	</div>
</div>
