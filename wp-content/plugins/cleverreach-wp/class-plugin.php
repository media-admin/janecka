<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

namespace CleverReach\WordPress;

use CleverReach\WordPress\Components\Hook_Handler;
use CleverReach\WordPress\Components\InfrastructureServices\Config_Service;
use CleverReach\WordPress\Components\Shortcode_Handler;
use CleverReach\WordPress\Components\Utility\Database;
use CleverReach\WordPress\Components\Utility\Forms_Formatter;
use CleverReach\WordPress\Components\Utility\Helper;
use CleverReach\WordPress\Components\Utility\Initializer;
use CleverReach\WordPress\Components\Utility\Task_Queue;
use CleverReach\WordPress\Components\Utility\Update\Update_Schema;
use CleverReach\WordPress\Components\Utility\Versioned_File_Reader;
use CleverReach\WordPress\Components\Wp_Clever_Reach_Widget;
use CleverReach\WordPress\Controllers\Clever_Reach_CF7_Controller;
use CleverReach\WordPress\Controllers\Clever_Reach_Frontend_Controller;
use CleverReach\WordPress\Controllers\Clever_Reach_Index;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Forms\Models\Form;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Interfaces\Proxy;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\RepositoryRegistry;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Scheduler\Models\HourlySchedule;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync\ClearCompletedScheduleCheckTasksTask;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync\FormCacheSyncTask;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync\InitialSyncTask;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync\RegisterEventHandlerTask;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\TaskQueueStorage;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Logger\Logger;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryClassException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\QueryFilter\Operators;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\QueryFilter\QueryFilter;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\QueueItem;

/**
 * Class Plugin
 *
 * @package CleverReach\WordPress
 */
class Plugin {

	const UNCHECKED = 'unchecked';
	const CHECKED = 'checked';
	const UPDATE_OPTION_CHECKED_KEY = 'cleverreach-wp-update-checked';

	/**
	 * Database session
	 *
	 * @var \wpdb
	 */
	public $db;

	/**
	 * Plugin instance
	 *
	 * @var Plugin
	 */
	protected static $instance = null;

	/**
	 * CleverReach plugin file
	 *
	 * @var string
	 */
	private $cleverreach_plugin_file;

	/**
	 * Base url
	 *
	 * @var string
	 */
	private $base_url;

	/**
	 * Config service
	 *
	 * @var Config_Service
	 */
	private $config_service;

	/**
	 * Plugin constructor.
	 *
	 * @param \wpdb  $wpdb WordPress database session.
	 * @param string $cleverreach_plugin_file Plugin file.
	 */
	public function __construct( $wpdb, $cleverreach_plugin_file ) {
		$this->db                      = $wpdb;
		$this->cleverreach_plugin_file = $cleverreach_plugin_file;
		$this->base_url                = Helper::get_clever_reach_base_url( '/resources/' );
	}

	/**
	 * Initialize plugin
	 *
	 * @throws RepositoryClassException
	 */
	private function initialize() {
		Initializer::register();
		$this->load_plugin_init_hooks();

		if ( Helper::is_plugin_enabled() ) {
			$this->load_clever_reach_admin_menu_and_newsletter_field();
			$this->load_clever_reach_plugin_text_domain();
			$this->load_cf7_hooks();
			$hook_handler = new Hook_Handler();
			$hook_handler->register_hooks();

			$this->add_widgets_and_blocks();

			if ( $this->should_show_oauth_connection_lost_notification() ) {
				add_action( 'admin_notices', array( $this, 'show_oauth_connection_lost_message' ) );
			}
		}
	}

	/**
	 * Returns whether CleverReach OAuth connection lost notification should be shown.
	 *
	 * @return bool
	 */
	private function should_show_oauth_connection_lost_notification(  ) {
		return ! $this->is_cleverreach_page() && $this->get_config_service()->show_oauth_connection_lost_notice();
	}

	/**
	 * Returns whether the current page is a CleverReach plugin page.
	 *
	 * @return bool
	 */
	private function is_cleverreach_page() {
		return array_key_exists( 'page', $_REQUEST ) && ( 'wp-cleverreach' === $_REQUEST[ 'page' ] );
	}

	/**
	 * Ads widgets and text editor blocks
	 */
	private function add_widgets_and_blocks() {
		global $wp_version;
		if ( $this->is_user_connected() ) {
			$shortcode_handler = new Shortcode_Handler();
			$shortcode_handler->create_shortcodes();

			add_action( 'widgets_init', array( $this, 'register_widgets' ) );

			if ( version_compare( $wp_version, '5.0.0', 'ge' ) ) {
				add_filter('block_categories', array($this, 'add_gutenberg_block_category'), 10, 2);
				add_action( 'enqueue_block_editor_assets', array( $this, 'load_gutenberg_block' ) );
				register_block_type( 'cleverreach/subscription-form', array(
					'render_callback' => array( $this, 'render_subscription_form' ),
					'attributes'      => array(
						'formID' => array(
							'type' => 'string',
						),
						'renderForm' => array(
							'type' => 'boolean',
						),
						'className' => array(
							'type' => 'string',
						),
					),
				) );
			}

			$this->load_classic_editor_hooks();
		}
	}

	/**
	 * Add CleverReach block category to gutenberg editor
	 *
	 * @param array $categories list of categories
	 *
	 * @param       $post
	 *
	 * @return array
	 */
	public function add_gutenberg_block_category( $categories, $post ) {
		return array_merge(
			$categories,
			array(
				array(
					'slug' => 'cleverreach',
					'title' => 'CleverReach®',
				)
			)
		);
	}

	/**
	 * Creates block on gutenberg editor
	 *
	 * @throws RepositoryNotRegisteredException
	 */
	public function load_gutenberg_block() {
		wp_register_script(
			'cleverreach-forms-block',
			Helper::get_clever_reach_base_url( '/resources/js/cleverreach.gutenberg-block.js' ),
			array( 'wp-blocks', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-element', 'underscore' )
		);

		wp_enqueue_script( 'cleverreach-forms-block' );

		/** @var Form[] $forms */
		$forms           = RepositoryRegistry::getRepository( Form::getClassName() )->select();
		$formatted_forms = array();
		foreach ( $forms as $form ) {
			$formatted_forms[] = array(
				'form_id' => $form->getFormId(),
				'name'    => $form->getName(),
				'content' => $form->getHtml(),
				'url'     => Helper::get_controller_url(
					'Forms',
					array(
						'action'  => 'open_form_in_cleverreach',
						'form_id' => $form->getFormId(),
					)
				),
			);
		}

		wp_localize_script( 'cleverreach-forms-block', 'cleverReachFormsBlock', array(
			'forms'            => $formatted_forms,
			'cleverreach_logo' => Helper::get_clever_reach_base_url( '/resources/images/logo_cleverreach.svg' ),
			'translations'     => $this->get_gutenberg_translations(),
		) );
	}

	/**
	 * Returns translations used in gutenberg script
	 *
	 * @return array
	 */
	private function get_gutenberg_translations()
	{
		return array(
			'subscription_form'        => __( 'Subscription form', 'cleverreach-wp' ),
			'edit_in_cleverreach'      => __( 'Edit in CleverReach®', 'cleverreach-wp' ),
			'insert_form'              => __( 'Insert CleverReach® signup form', 'cleverreach-wp' ),
			'form_settings'            => __( 'Form settings', 'cleverreach-wp' ),
			'form'                     => __( 'Form', 'cleverreach-wp' ),
		);
	}

	/**
	 * Creates CleverReach button on TinyMCE editor.
	 */
	private function load_classic_editor_hooks() {
		add_filter( 'tiny_mce_before_init', array( $this, 'cleverreach_add_base_url' ) );
		add_filter( 'mce_buttons', array( $this, 'cleverreach_register_toolbar_button' ) );
		add_filter( 'mce_external_plugins', array( $this, 'cleverreach_display_toolbar_button' ) );

		// enable shortcodes in text widgets
		add_filter( 'widget_text', 'do_shortcode' );
		add_filter( 'widget_text', 'shortcode_unautop' );
	}

	/**
	 * Adds base site URL to TinyMCE settings array.
	 *
	 * @param array $settings TinyMCE settings array.
	 *
	 * @return array Updated array.
	 */
	public function cleverreach_add_base_url( $settings ) {
		$settings[ 'base_url' ] = get_site_url();

		return $settings;
	}

	/**
	 * Hooks on MCE buttons event.
	 *
	 * @param array $buttons
	 *
	 * @return mixed
	 */
	public function cleverreach_register_toolbar_button( $buttons ) {
		array_push( $buttons, 'separator', 'cleverreach' );

		return $buttons;
	}

	/**
	 * Hooks on MCE external plugins event.
	 *
	 * @param array $plugin_array
	 *
	 * @return mixed
	 */
	public function cleverreach_display_toolbar_button( $plugin_array ) {
		$plugin_array['cleverreach'] = Helper::get_clever_reach_base_url( '/resources/js/cleverreach.classic-editor.js' );

		return $plugin_array;
	}

	/**
	 * Loads CleverReach icon for the sidebar menu on administrator panel.
	 */
	public function load_cleverreach_icon() {
		echo '
		    <style>
		    .dashicons-cr {
		        background-image: url("'. Helper::get_clever_reach_base_url( '/resources/images/cr-envelope.svg' ) . '");
		        background-repeat: no-repeat;
		        background-position: center; 
		    }
		    
		    .current .dashicons-cr {
		        background-image: url("'. Helper::get_clever_reach_base_url( '/resources/images/cr-envelope-white.svg' ) . '");
		    }
		    </style>
		';
	}

	/**
	 * Shows OAuth connection lost notification.
	 */
	public function show_oauth_connection_lost_message() {
		$notification_data = $this->get_config_service()->get_admin_notification_data();
		$title             = __( 'Your connection between WordPress and CleverReach was interrupted!', 'cleverreach-wp' );
		$link_label        = __( 'Re-authenticate now', 'cleverreach-wp' );
		echo "
			<div class='notice notice-info is-dismissible'>
				<p>
					<b>{$title}</b>&nbsp;
					{$notification_data['description']}
					<a href='{$notification_data['url']}'>{$link_label}</a>
				</p>
			</div>";
	}

	/**
	 * Register CleverReach widget
	 */
	public function register_widgets() {
		$widget = new Wp_Clever_Reach_Widget();
		register_widget($widget);
	}

	/**
	 * Renders CleverReach form on wordpress admin
	 *
	 * @param array $attributes block attributes
	 *
	 * @return string
	 *
	 * @throws QueryFilterInvalidParamException
	 * @throws RepositoryNotRegisteredException
	 * @throws \Exception
	 */
	public function render_subscription_form( $attributes ) {
		if ( ! empty( $attributes[ 'formID' ] ) ) {
			$class_attributes = $this->get_class_attributes( $attributes );

			$filter = new QueryFilter();
			$filter->where( 'formId', Operators::EQUALS, (string) $attributes[ 'formID' ] );

			/** @var Form $form */
			$form = RepositoryRegistry::getRepository( Form::getClassName() )->selectOne( $filter );

			$html = $form !== null ? Forms_Formatter::get_form_code( $form->getHtml() ) : '';

			return "<div class='{$class_attributes}'>{$html}</div>";
		}

		return '';
	}

	/**
	 * Removes invalid classes and returns them in format 'class1 class2 class3....'
	 *
	 * @param array $attributes
	 *
	 * @return string
	 */
	private function get_class_attributes( $attributes ) {
		if ( empty( $attributes[ 'className' ] ) ) {
			return '';
		}

		$class_attributes = '';
		$classes          = $attributes[ 'className' ];
		$classes          = preg_replace( '/\s+/', ' ', $classes );
		$array_of_classes = explode( ' ', $classes );
		foreach ( $array_of_classes as $class ) {
			$class = trim( $class );
			if ( $this->is_class_name_valid( $class ) ) {
				$class_attributes .= ' ' . $class;
			}
		}

		return $class_attributes;
	}

	/**
	 * Checks if css class name is valid
	 *
	 * @param string $class_name css selector
	 *
	 * @return bool
	 */
	private function is_class_name_valid($class_name) {
		if ($class_name === '') {
			return true;
		}

		$class_name_pattern = '/^[[a-zA-Z-_]+[a-zA-Z0-9-_]*$/';

		return preg_match($class_name_pattern, $class_name) === 1;
	}

	/**
	 * Returns singleton instance of the plugin.
	 *
	 * @param \wpdb  $wpdb                    WordPress database session.
	 * @param string $cleverreach_plugin_file Plugin file.
	 *
	 * @return Plugin
	 *
	 * @throws RepositoryClassException
	 */
	public static function instance( $wpdb, $cleverreach_plugin_file ) {
		if ( null === self::$instance ) {
			self::$instance = new self( $wpdb, $cleverreach_plugin_file );
		}

		self::$instance->initialize();
		return self::$instance;
	}

	/**
	 * Load translation files
	 */
	public function load_clever_reach_plugin_text_domain() {
		unload_textdomain( 'cleverreach-wp' );
		load_plugin_textdomain( 'cleverreach-wp', false, plugin_basename( dirname( $this->cleverreach_plugin_file ) ) . '/i18n/languages' );
	}

	/**
	 * Add all init plugin hooks
	 */
	private function load_plugin_init_hooks() {
		register_activation_hook( $this->cleverreach_plugin_file, array( $this, 'cleverreach_activate' ) );
		add_action( 'init', array( $this, 'load_clever_reach_plugin_text_domain' ) );
		add_action( 'admin_init', array( $this, 'cleverreach_initialize_new_site' ) );
		add_action( 'upgrader_process_complete', array( $this, 'update_option_for_plugin_update_check' ), 100, 2 );
		add_filter( 'query_vars', array( $this, 'cleverreach_plugin_add_trigger' ) );
		add_action( 'template_redirect', array( $this, 'cleverreach_plugin_trigger_check' ) );
	}

	/**
	 * Registers all Contact Form 7 related hooks.
	 */
	private function load_cf7_hooks() {
		if ( Helper::is_initial_sync_finished()
		     && Helper::is_plugin_enabled( 'contact-form-7/wp-contact-form-7.php' )
		) {
			$cr7_controller = new Clever_Reach_CF7_Controller( $this->cleverreach_plugin_file );

			add_action( 'admin_init', array( $cr7_controller, 'add_signup_tag' ), 1000 );
			add_action( 'wpcf7_init', array( $cr7_controller, 'render_signup_tag' ) );
			add_action( 'wpcf7_editor_panels', array( $cr7_controller, 'add_settings_tab' ) );
			add_action( 'wpcf7_after_save', array( $cr7_controller, 'save_tag_settings' ) );
			add_action( 'after_delete_post', array( $cr7_controller, 'delete_tag_settings' ) );
			add_action( 'wpcf7_submit', array( $cr7_controller, 'submit_form' ), 10, 2 );
		}
	}

	/**
	 * Add CleverReach admin menu and newsletter field hooks
	 */
	private function load_clever_reach_admin_menu_and_newsletter_field() {
		if ( is_admin() && ! is_network_admin() ) {
			add_action( 'admin_menu', array( $this, 'cleverreach_create_admin_menu' ) );

			if ( $this->get_config_service()->is_recipient_sync_enabled() ) {
				add_action( 'user_new_form', array( $this, 'cleverreach_register_form_field_newsletter' ) );
				add_action( 'show_user_profile', array( $this, 'cleverreach_register_form_field_newsletter' ) );
				add_action( 'edit_user_profile', array( $this, 'cleverreach_register_form_field_newsletter' ) );
			}
		}
	}

	/**
	 * Plugin install method
	 *
	 * @param bool $is_network_wide Is plugin activate network wide.
	 *
	 * @throws RepositoryClassException
	 * @throws RepositoryNotRegisteredException
	 * @throws TaskRunnerStatusStorageUnavailableException Task runner status storage unavailable exception.
	 */
	public function cleverreach_activate( $is_network_wide ) {
		if ( ! Helper::is_curl_enabled() ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die(
				esc_html(
					__(
						'cURL is not installed or enabled in your PHP installation. This is required for background task to work. Please install it and then refresh this page.',
						'cleverreach-wp'
					)
				),
				'Plugin dependency check',
				array( 'back_link' => true )
			);
		}

		if ( Helper::is_plugin_initialized() ) {
			Task_Queue::wakeup();
		} elseif ( is_multisite() && $is_network_wide ) {
			$site_ids = get_sites();
			foreach ( $site_ids as $site_id ) {
				switch_to_blog( $site_id->blog_id );
				$this->init_database();
				$this->init_config();
				$this->add_scheduled_tasks();
				restore_current_blog();
			}
		} else {
			$this->init_database();
			$this->init_config();
			$this->add_scheduled_tasks();
		}
	}

	/**
	 * Plugin update method
	 */
	private function cleverreach_update_if_version_changed() {
		if ( is_multisite() ) {
			$site_ids = get_sites();
			foreach ( $site_ids as $site_id ) {
				switch_to_blog( $site_id->blog_id );
				$this->update_plugin_on_single_site();
				restore_current_blog();
			}
		} else {
			$this->update_plugin_on_single_site();
		}
	}

	/**
	 * Adds cleverreach query variable.
	 *
	 * @param array $vars Filter variables.
	 *
	 * @return array Filter variables.
	 */
	public function cleverreach_plugin_add_trigger( $vars ) {
		$vars[] = 'cleverreach_wp_controller';
		return $vars;
	}

	/**
	 * Trigger action on calling cleverreach controller.
	 */
	public function cleverreach_plugin_trigger_check() {
		$controller_name = get_query_var( 'cleverreach_wp_controller' );
		if ( ! empty( $controller_name ) ) {
			$controller = new Clever_Reach_Index();
			$controller->index();
		}
	}

	/**
	 * Updates option for plugin update check when hook for update is triggered
	 */
	public function update_option_for_plugin_update_check() {
		update_site_option(self::UPDATE_OPTION_CHECKED_KEY, self::UNCHECKED);
	}

	/**
	 * Initializes base CleverReach tables and values if plugin is accessed from a new site
	 *
	 * @throws TaskRunnerStatusStorageUnavailableException Task runner status storage unavailable exception.
	 */
	public function cleverreach_initialize_new_site() {
		if ( ! Helper::is_plugin_initialized() ) {
			$this->init_database();
			$this->init_config();
		}

		$is_checked = get_site_option( self::UPDATE_OPTION_CHECKED_KEY );
		if ( $is_checked !== self::CHECKED ) {
			$this->cleverreach_update_if_version_changed();
			update_site_option( self::UPDATE_OPTION_CHECKED_KEY, self::CHECKED );
		}
	}

	/**
	 * Creates CleverReach admin menu tab
	 */
	public function cleverreach_create_admin_menu() {
		$controller = new Clever_Reach_Frontend_Controller();
		add_menu_page(
			'CleverReach',
			'CleverReach®',
			'manage_options',
			'wp-cleverreach',
			array( $controller, 'render' ),
			'dashicons-cr',
			31
		);
		add_action( 'admin_head', array( $this, 'load_cleverreach_icon' ) );
	}

	/**
	 * Creates 'Subscribe to newsletter' field on user form
	 */
	public function cleverreach_register_form_field_newsletter() {
		include plugin_dir_path( $this->cleverreach_plugin_file ) . 'resources/views/register-form-newsletter.php';
	}

	/**
	 * Install database tables
	 */
	private function init_database() {
		$installer = new Database( $this->db );
		$installer->install();
	}

	/**
	 * Set initial config values
	 *
	 * @throws TaskRunnerStatusStorageUnavailableException Task runner status storage unavailable exception.
	 */
	private function init_config() {
		$config = $this->get_config_service();
		$config->setTaskRunnerStatus( '', null );
		$config->setProductSearchEndpointPassword( md5( time() ) );
		$config->set_language( Helper::get_site_language() );
		$config->set_database_version( Helper::get_plugin_version() );
		$config->set_plugin_opened_for_the_first_time( false );
		$config->set_entity_table_created();
	}

	/**
	 * @throws RepositoryClassException
	 * @throws RepositoryNotRegisteredException
	 */
	private function add_scheduled_tasks() {
		$schedule_repository = RepositoryRegistry::getScheduleRepository();
		$config = $this->get_config_service();

		$clear_scheduled_check_tasks_schedule = new HourlySchedule(
			new ClearCompletedScheduleCheckTasksTask(),
			$config->getQueueName()
		);

		$clear_scheduled_check_tasks_schedule->setNextSchedule();
		$schedule_repository->save( $clear_scheduled_check_tasks_schedule );
	}

	/**
	 * Updates plugin on single WordPress site.
	 *
	 * @return bool
	 *
	 * @throws IntegrationCore\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
	 * @throws QueryFilterInvalidParamException
	 * @throws RepositoryClassException
	 * @throws RepositoryNotRegisteredException
	 */
	private function update_plugin_on_single_site() {
		if ( Helper::is_plugin_enabled() && $this->is_plugin_version_changed()) {
			$previous_version = $this->config_service->get_database_version();
			$migration_directory = realpath( __DIR__ ) . '/database/migrations/';
			$version_file_reader = new Versioned_File_Reader( $migration_directory, $previous_version );
			while ( $version_file_reader->has_next() ) {
				/** @var Update_Schema $update_schema */
				$update_schema = $version_file_reader->read_next();
				try {
					$update_schema->update();
				} catch ( \Exception $ex ) {
					Logger::logError( $ex->getMessage(), 'Database Update' );

					return false;
				}
			}

			if ( $this->should_delete_receiver_event()
			     && version_compare( $this->get_config_service()->get_database_version(), '1.3.0', 'lt' )
			) {
				ServiceRegister::getService( Proxy::CLASS_NAME )->deleteReceiverEvent();
			}

			if ( version_compare( $this->get_config_service()->get_database_version(), '1.5.0', 'lt' ) ) {
				Task_Queue::enqueue( new FormCacheSyncTask() );
			}

			if ( version_compare( $this->get_config_service()->get_database_version(), '1.5.1', 'lt' ) ) {
				Task_Queue::enqueue( new RegisterEventHandlerTask() );
				$this->delete_form_schedulers();
				$this->delete_old_forms();
				Task_Queue::enqueue( new FormCacheSyncTask() );
			}

			if ( version_compare( $this->get_config_service()->get_database_version(), '1.5.2', 'lt' ) ) {
				Task_Queue::enqueue( new RegisterEventHandlerTask() );
			}

			$this->get_config_service()->set_database_version( Helper::get_plugin_version() );
		}

		return true;
	}

	/**
	 * Deletes form schedulers.
	 *
	 * @throws QueryFilterInvalidParamException
	 * @throws RepositoryClassException
	 * @throws RepositoryNotRegisteredException
	 */
	private function delete_form_schedulers() {
		$schedule_repository = RepositoryRegistry::getScheduleRepository();
		$filter              = new QueryFilter();
		$filter->where( 'taskType', Operators::EQUALS, FormCacheSyncTask::getClassName() );

		$schedule_repository->deleteBy( $filter );
	}

	/**
	 * Deletes old forms that do not belong to the integration list.
	 *
	 * @throws QueryFilterInvalidParamException
	 * @throws RepositoryNotRegisteredException
	 */
	private function delete_old_forms() {
		$form_repository = RepositoryRegistry::getRepository( Form::CLASS_NAME );
		$filter          = new QueryFilter();
		$filter->where( 'groupId', Operators::NOT_EQUALS, $this->get_config_service()->getIntegrationId() );

		$form_repository->deleteBy( $filter );
	}

	/**
	 * Check if update script should execute
	 *
	 * @return bool
	 */
	private function is_plugin_version_changed() {
		$previous_version = $this->get_config_service()->get_database_version();
		$current_version  = Helper::get_plugin_version();
		if ( ! $this->get_config_service()->is_entity_table_created() ) {
			$this->config_service->set_database_version( '1.3.0' );

			return true;
		}

		return version_compare( $previous_version, $current_version, 'lt' );
	}

	/**
	 * Returns whether receiver event should be deleted upon plugin update.
	 *
	 * @return bool
	 */
	private function should_delete_receiver_event() {
		/** @var TaskQueueStorage $task_queue_service */
		$task_queue_service     = ServiceRegister::getService( TaskQueueStorage::CLASS_NAME );
		$initial_sync_task_item = $task_queue_service->findLatestByType( InitialSyncTask::getClassName() );

		return $initial_sync_task_item
		       && $initial_sync_task_item->getStatus() === QueueItem::COMPLETED
		       && ( $this->get_config_service()->is_recipient_sync_enabled()
		            || $this->get_config_service()->is_integration_recipient_sync_enabled( 'CF7' )
		       );
	}

	/**
	 * Check if user is authenticated and if initial sync is finished
	 *
	 * @return bool
	 */
	private function is_user_connected() {
		/** @var TaskQueueStorage $task_queue */
		$task_queue = ServiceRegister::getService( TaskQueueStorage::CLASS_NAME );
		$initial_sync_task = $task_queue->findLatestByType( 'Initial_Sync_Task' );
		$access_token = $this->get_config_service()->getAccessToken();
		if (empty($access_token) || !$initial_sync_task) {
			return false;
		}

		return $initial_sync_task->getStatus() === QueueItem::COMPLETED;
	}

	/**
	 * Gets config service
	 *
	 * @return Config_Service
	 */
	private function get_config_service() {
		if ( null === $this->config_service ) {
			$this->config_service = ServiceRegister::getService( Configuration::CLASS_NAME );
		}

		return $this->config_service;
	}
}
