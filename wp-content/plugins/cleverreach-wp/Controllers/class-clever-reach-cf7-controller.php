<?php

namespace CleverReach\WordPress\Controllers;

use CleverReach\WordPress\Components\BusinessLogicServices\Attributes_Service;
use CleverReach\WordPress\Components\BusinessLogicServices\Recipient_Service;
use CleverReach\WordPress\Components\Entities\Contact;
use CleverReach\WordPress\Components\InfrastructureServices\Config_Service;
use CleverReach\WordPress\Components\Repositories\Base_Repository;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Forms\Models\DoiEmail;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Forms\Models\Form;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Interfaces\Attributes;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Proxy;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\RepositoryRegistry;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync\RecipientSyncTask;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync\SendDoiEmailsTask;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\SingleSignOn\SingleSignOnProvider;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Logger\Logger;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\QueryFilter\Operators;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\QueryFilter\QueryFilter;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Queue;

/**
 * Class Clever_Reach_CF7_Controller
 *
 * @package CleverReach\WordPress\Controllers
 */
class Clever_Reach_CF7_Controller {
	const CF7_PLUGIN_NAME = 'cleverreach-wp';
	const CF7_ENABLED_SYNCHRONIZATION_PROPERTY = 'cleverreach-wp-enabled-synchronization';
	const CF7_DOUBLE_OPT_IN_FORM_PROPERTY = 'cleverreach-wp-double-opt-in-form';
	const CF7_RECIPIENT_TAGS_PROPERTY = 'cleverreach-wp-recipient-tags';
	const CF7_ATTRIBUTE_MAPPINGS_PROPERTY = 'cleverreach-wp-attribute-mappings';
	const CF7_SIGN_UP_FIELD_ALREADY_EXISTS = 'cleverreach-wp-sign-up-field-already-exists';
	const CR_GENERAL_SETTINGS_PAGE = '/admin/account_settings.php#general';

	/**
	 * @var array
	 */
	public static $cleverreach_system_attributes = array(
		'email',
		'newsletter'
	);

	/**
	 * @var array
	 */
	private static $cleverreach_not_required_user_data = array(
		'fax'
	);

	/**
	 * @var array
	 */
	private static $contact_form_7_excluded_statuses = array(
		'validation_failed',
		'acceptance_missing',
		'mail_failed',
		'spam',
	);

	/**
	 * @var array
	 */
	private static $excluded_field_types = array(
		'cleverreach',
		'dynamictext',
		'dynamichidden',
		'email',
		'submit',
		'file',
	);

	/**
	 * @var string
	 */
	private $cleverreach_plugin_file;
	/**
	 * @var Config_Service
	 */
	private $config_service;
	/**
	 * @var Queue
	 */
	private $queue_service;
	/**
	 * @var Base_Repository
	 */
	private $contact_repository;

	/**
	 * Clever_Reach_CF7_Controller constructor.
	 *
	 * @param string $plugin_file
	 */
	public function __construct( $plugin_file ) {
		$this->cleverreach_plugin_file = $plugin_file;
	}

	/**
	 * Generates CleverReach signup tag within Contact Form 7 tag list.
	 */
	public function add_signup_tag() {
		if ( ! function_exists( 'wpcf7_add_tag_generator' ) ) {
			return;
		}

		wpcf7_add_tag_generator(
			self::CF7_PLUGIN_NAME,
			__( 'CleverReach® Sign-Up', 'cleverreach-wp' ),
			self::CF7_PLUGIN_NAME,
			array( $this, 'render_tag_settings' )
		);

		do_action( 'wpcf7cf_tag_generator' );
	}

	/**
	 * Registers method for rendering CleverReach tag within CF7 form.
	 */
	public function render_signup_tag() {
		wpcf7_add_form_tag( 'cleverreach', array( $this, 'render_tag' ) );
	}

	/**
	 * Renders CleverReach signup tag settings page in a pop-up modal.
	 *
	 * @param \WPCF7_ContactForm $form
	 * @param array              $args
	 */
	public function render_tag_settings( \WPCF7_ContactForm $form, array $args ) {
		include plugin_dir_path( $this->cleverreach_plugin_file ) . 'resources/views/signup-form-tag-settings.php';
	}

	/**
	 * Renders CleverReach signup tag within CF7 form.
	 *
	 * @param \WPCF7_FormTag $tag
	 *
	 * @return string Rendered HTML output.
	 */
	public function render_tag( \WPCF7_FormTag $tag ) {
		$attributes = array(
			'type'    => 'checkbox',
			'name'    => $tag->type,
			'checked' => $tag->has_option( 'preselect-checkbox' ),
		);

		if ( $tag->has_option( 'id' ) ) {
			$attributes[ 'id' ] = $tag->get_id_option();
		}

		if ( $tag->has_option( 'class' ) ) {
			$attributes[ 'class' ] = $tag->get_class_option();
		}

		$label = ! empty( $tag->values ) ? $tag->values[ 0 ] : '';

		$parent_class_list = 'wpcf7-form-control wpcf7-checkbox wpcf7-validates-as-required wpcf7-acceptance';

		if ( ! $tag->has_option( 'required-checkbox' ) ) {
			$parent_class_list .= ' optional';
		}

		$checkbox = '<span class="wpcf7-form-control-wrap">
						<span class="' . $parent_class_list . '">
							<span class="wpcf7-list-item first last">
								<label>
									<input %1$s />
									<span class="wpcf7-list-item-label">%2$s</span>
								</label>
							</span>
						</span>
					 </span>';

		return sprintf( $checkbox, wpcf7_format_atts( $attributes ), $label );
	}

	/**
	 * Adds CleveReach settings tab to the list of CF7 panels.
	 *
	 * @param array $panels
	 *
	 * @return array
	 */
	public function add_settings_tab( $panels ) {
		if ( current_user_can( 'wpcf7_edit_contact_form' ) ) {
			$panels[ self::CF7_PLUGIN_NAME . '-panel' ] = array(
				'title'    => __( 'CleverReach®', 'cleverreach-wp' ),
				'callback' => array( $this, 'render_settings_tab' )
			);
		}

		return $panels;
	}

	/**
	 * Renders CleverReach settings tab.
	 *
	 * @param \WPCF7_ContactForm $form
	 * @param string             $args
	 *
	 * @throws QueryFilterInvalidParamException
	 * @throws RepositoryNotRegisteredException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
	 */
	public function render_settings_tab( \WPCF7_ContactForm $form, $args = '' ) {
		/** @var Attributes_Service $attributes_service */
		$attributes_service = ServiceRegister::getService( Attributes::CLASS_NAME );
		$cr_settings        = $this->get_cr_settings( $form->id() );

		$args = wp_parse_args( $args,
			array(
				'id'                     => self::CF7_PLUGIN_NAME,
				'recipient_sync_enabled' => ! empty( $cr_settings[ self::CF7_ENABLED_SYNCHRONIZATION_PROPERTY ] )
					? (bool) $cr_settings[ self::CF7_ENABLED_SYNCHRONIZATION_PROPERTY ] : false,
			)
		);

		if ( $args[ 'recipient_sync_enabled' ] ) {
			$args = array_merge( $args, array(
				'forms'                   => $this->get_integration_specific_forms(),
				'cf7_tags'                => $this->get_cf7_tags( $form ),
				'cr_attributes'           => $attributes_service->getAttributes(),
				'double_opt_in_form'      => null,
				'is_user_info_incomplete' => $this->is_user_info_incomplete(),
				'recipient_tags'          => '',
				'attributes'              => array(),
				'cr_data_page'            => SingleSignOnProvider::getUrl( self::CR_GENERAL_SETTINGS_PAGE ),
			) );

			if ( ! empty( $cr_settings ) ) {
				$args[ 'recipient_tags' ] = ! empty( $cr_settings[ self::CF7_RECIPIENT_TAGS_PROPERTY ] )
					? implode( ',', $cr_settings[ self::CF7_RECIPIENT_TAGS_PROPERTY ] )
					: '';

				$args[ 'attributes' ] = ! empty( $cr_settings[ self::CF7_ATTRIBUTE_MAPPINGS_PROPERTY ] )
					? $cr_settings[ self::CF7_ATTRIBUTE_MAPPINGS_PROPERTY ]
					: array();

				$args[ 'double_opt_in_form' ] = ! empty( $cr_settings[ self::CF7_DOUBLE_OPT_IN_FORM_PROPERTY ] )
					? $cr_settings[ self::CF7_DOUBLE_OPT_IN_FORM_PROPERTY ]
					: null;
			}
		}

		include plugin_dir_path( $this->cleverreach_plugin_file ) . 'resources/views/signup-form-settings-tab.php';
	}

	/**
	 * Handles saving or duplicating CleveReach settings for Contact Form 7.
	 *
	 * @param \WPCF7_ContactForm $form
	 *
	 * @throws QueryFilterInvalidParamException
	 * @throws RepositoryNotRegisteredException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
	 */
	public function save_tag_settings( \WPCF7_ContactForm $form ) {
		if ( $form->get_current()->id() !== $form->id() ) {
			$this->handle_duplicate_action( $form );
		} else {
			$this->handle_save_action( $form );
		}
	}

	/**
	 * Deletes CleverReach settings for CF7 form with the provided ID.
	 *
	 * @param int $id Contact form ID.
	 */
	public function delete_tag_settings( $id ) {
		delete_post_meta( $id, '_' . self::CF7_PLUGIN_NAME );
	}

	/**
	 * Handles CF7 form submit.
	 *
	 * @param \WPCF7_ContactForm $form
	 * @param                    $options
	 *
	 * @throws QueryFilterInvalidParamException
	 * @throws RepositoryNotRegisteredException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
	 */
	public function submit_form( \WPCF7_ContactForm $form, $options ) {

		if ( in_array( $options[ 'status' ], self::$contact_form_7_excluded_statuses, true ) ) {
			return;
		}

		$cr_settings = $this->get_cr_settings( $form->id() );
		if ( empty( $cr_settings[ self::CF7_ENABLED_SYNCHRONIZATION_PROPERTY ] ) ) {
			return;
		}

		$emails              = array();
		$cr_checkbox_present = false;

		foreach ( $form->scan_form_tags() as $tag ) {
			if ( 'email' === $tag->basetype && array_key_exists( $tag->name, $_POST ) ) {
				$emails[] = sanitize_text_field( $_POST[ $tag->name ] );
			} elseif ( 'cleverreach' === $tag->basetype ) {
				$cr_checkbox_present = true;
			}
		}

		if ( empty( $emails ) ) {
			Logger::logError( "Email address field is not found in the form {$form->name()}. Please configure " .
			                  'CF7 to have at least one email field.', 'Integration' );

			return;
		}

		$cr_checkbox_checked = $cr_checkbox_present && isset( $_POST[ 'cleverreach' ] );

		if ( $cr_checkbox_present && ! $cr_checkbox_checked ) {
			return;
		}

		$doi_emails  = array();
		$contact_ids = array();
		$attributes  = $this->get_attributes( $form->id() );
		$doi_form_id = ! $this->is_user_info_incomplete() ? $this->get_doi_form_id( $form->id() ) : null;

		foreach ( $emails as $email ) {
			$filter = new QueryFilter();
			$filter->where( 'email', Operators::EQUALS, $email );

			/** @var Contact $contact */
			$contact       = $this->get_contact_repository()->selectOne( $filter );
			$special_tags  = array( 'contact' );
			$is_subscribed = false;

			if ( ! $cr_checkbox_present || ( $cr_checkbox_checked && empty( $doi_form_id ) ) ) {
				$special_tags  = array( 'subscriber' );
				$is_subscribed = true;
			}

			if ( ! empty( $doi_form_id ) && ( ! $cr_checkbox_present || $cr_checkbox_checked ) ) {
				$doi_emails[] = new DoiEmail(
					$doi_form_id,
					$email,
					! empty( $_SERVER[ 'REMOTE_ADDR' ] ) ? $_SERVER[ 'REMOTE_ADDR' ] : '0.0.0.0',
					! empty( $_SERVER[ 'HTTP_REFERER' ] ) ? $_SERVER[ 'HTTP_REFERER' ] : '/',
					! empty( $_SERVER[ 'HTTP_USER_AGENT' ] ) ? $_SERVER[ 'HTTP_USER_AGENT' ] : 'wordpress'
				);
			}

			if ( null === $contact ) {
				$contact_id = $this->save_contact(
					$email,
					$form->id(),
					empty( $doi_form_id ),
					$is_subscribed,
					$this->get_cr_tags( $form->id() ),
					$special_tags,
					$attributes
				);
			} else {
				$contact_id = $this->update_contact(
					$contact,
					$form->id(),
					$contact->is_active(),
					$cr_checkbox_checked,
					$this->get_cr_tags( $form->id() ),
					$special_tags,
					$attributes
				);
			}

			$contact_ids[] = Recipient_Service::CONTACT_ID_PREFIX . $contact_id;
		}

		if ( ! empty( $contact_ids ) ) {
			$this->get_queue_service()->enqueue(
				$this->get_config_service()->getQueueName(),
				new RecipientSyncTask( $contact_ids )
			);
		}

		if ( ! empty( $doi_emails ) ) {
			$this->get_queue_service()->enqueue(
				$this->get_config_service()->getQueueName(),
				new SendDoiEmailsTask( $doi_emails )
			);
		}
	}

	/**
	 * Handles Contact Form 7 save action.
	 *
	 * @param \WPCF7_ContactForm $form
	 *
	 * @throws QueryFilterInvalidParamException
	 * @throws RepositoryNotRegisteredException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
	 */
	private function handle_save_action( \WPCF7_ContactForm $form ) {
		$args = $_REQUEST;

		if ( array_key_exists( self::CF7_ENABLED_SYNCHRONIZATION_PROPERTY, $args ) ) {
			$this->before_save_action( $form, $args );

			$cr_settings                      = array();
			$integration_sync_already_enabled = $this->get_config_service()->is_integration_recipient_sync_enabled( 'CF7' );
			$integration_sync_enabled         = (bool) sanitize_text_field( $args[ self::CF7_ENABLED_SYNCHRONIZATION_PROPERTY ] );

			$cr_settings[ self::CF7_ENABLED_SYNCHRONIZATION_PROPERTY ] = $integration_sync_enabled;
			$cr_settings[ self::CF7_SIGN_UP_FIELD_ALREADY_EXISTS ]     = sanitize_text_field( $args[ self::CF7_SIGN_UP_FIELD_ALREADY_EXISTS ] );

			$this->get_config_service()->set_integration_recipient_sync_enabled( 'CF7', $integration_sync_enabled );

			if ( $integration_sync_enabled && $integration_sync_already_enabled ) {
				$tags = array();
				if ( ! empty( $args[ self::CF7_RECIPIENT_TAGS_PROPERTY ] ) ) {
					$tags = explode( ',', sanitize_text_field( $args[ self::CF7_RECIPIENT_TAGS_PROPERTY ] ) );
				}

				$mappings = array();
				foreach ( $args as $key => $arg ) {
					if ( 'empty' !== $arg && preg_match( '/^cleverreach-wp-[a-zA-Z0-9-_]{1,}-attribute$/', $key ) ) {
						$mappings[ $key ] = sanitize_text_field( $arg );
					}
				}

				$cr_settings = array_merge( $cr_settings, array(
						self::CF7_RECIPIENT_TAGS_PROPERTY     => $tags,
						self::CF7_ATTRIBUTE_MAPPINGS_PROPERTY => $mappings,
					)
				);

				if ( ! $this->is_user_info_incomplete() ) {
					$cr_settings[ self::CF7_DOUBLE_OPT_IN_FORM_PROPERTY ] = ! empty( $args[ self::CF7_DOUBLE_OPT_IN_FORM_PROPERTY ] )
						? sanitize_text_field( $args[ self::CF7_DOUBLE_OPT_IN_FORM_PROPERTY ] ) :
						'none';
				}
			}

			update_post_meta(
				$form->id(),
				'_' . self::CF7_PLUGIN_NAME,
				wpcf7_normalize_newline_deep( $cr_settings )
			);
		}
	}

	/**
	 * Optimizes user onboarding process. When newsletter sign-up field is added for the first time,
	 * CleverReach synchronization will be enabled automatically with first DOI form.
	 *
	 * @param \WPCF7_ContactForm $form
	 * @param array              $args
	 *
	 * @throws QueryFilterInvalidParamException
	 * @throws RepositoryNotRegisteredException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
	 */
	private function before_save_action( \WPCF7_ContactForm $form, array &$args ) {
		$cr_settings                  = $this->get_cr_settings( $form->id() );
		$integration_sync_enabled     = (bool) sanitize_text_field( $_REQUEST[ self::CF7_ENABLED_SYNCHRONIZATION_PROPERTY ] );
		$sing_up_field_already_exists = ! empty( $cr_settings[ self::CF7_SIGN_UP_FIELD_ALREADY_EXISTS ] )
			? $cr_settings[ self::CF7_SIGN_UP_FIELD_ALREADY_EXISTS ] :
			false;

		if ( ! $sing_up_field_already_exists && ! $integration_sync_enabled ) {
			foreach ( $form->scan_form_tags() as $tag ) {
				if ( 'cleverreach' === $tag->basetype ) {
					$args[ self::CF7_DOUBLE_OPT_IN_FORM_PROPERTY ]      = $this->get_first_integration_form_id();
					$args[ self::CF7_ENABLED_SYNCHRONIZATION_PROPERTY ] = true;
					$sing_up_field_already_exists                       = true;
					break;
				}
			}
		}

		$args[ self::CF7_SIGN_UP_FIELD_ALREADY_EXISTS ] = $sing_up_field_already_exists;
	}

	/**
	 * Returns integration specific CleverReach forms.
	 *
	 * @return Form[]
	 *
	 * @throws QueryFilterInvalidParamException
	 * @throws RepositoryNotRegisteredException
	 */
	private function get_integration_specific_forms() {
		/** @var Base_Repository $form_repository */
		$form_repository = RepositoryRegistry::getRepository( Form::CLASS_NAME );
		$filter          = new QueryFilter();
		$filter->where(
			'groupId',
			Operators::EQUALS,
			$this->get_config_service()->getIntegrationId()
		);

		return $form_repository->select( $filter );
	}

	/**
	 * Returns first integration form ID.
	 *
	 * @return int|null
	 *
	 * @throws QueryFilterInvalidParamException
	 * @throws RepositoryNotRegisteredException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
	 */
	private function get_first_integration_form_id() {
		$forms = $this->get_integration_specific_forms();

		if ( $this->is_user_info_incomplete() ) {
			return null;
		}

		return ! empty( $forms ) ? $forms[ 0 ]->getFormId() : null;
	}

	/**
	 * Handles Contact Form 7 duplicate action.
	 *
	 * @param \WPCF7_ContactForm $form
	 */
	private function handle_duplicate_action( \WPCF7_ContactForm $form ) {
		$cr_settings = $this->get_cr_settings( $form->get_current()->id() );
		if ( ! empty( $cr_settings ) ) {
			update_post_meta(
				$form->id(),
				'_' . self::CF7_PLUGIN_NAME,
				wpcf7_normalize_newline_deep( $cr_settings )
			);
		}
	}

	/**
	 * Filters list of available form tags and excludes all email, submit and CleverReach sign-up tags.
	 *
	 * @param \WPCF7_ContactForm $form
	 *
	 * @return array
	 */
	private function get_cf7_tags( \WPCF7_ContactForm $form ) {
		$tags = array();
		/** @var \WPCF7_FormTag $tag */
		foreach ( $form->scan_form_tags() as $tag ) {
			if ( ! in_array( $tag->basetype, self::$excluded_field_types, true ) ) {
				$tags[] = $tag;
			}
		}

		return $tags;
	}

	/**
	 * Returns DOI form ID.
	 *
	 * @param int $cf7_form_id
	 *
	 * @return mixed
	 */
	private function get_doi_form_id( $cf7_form_id ) {
		$cr_settings = $this->get_cr_settings( $cf7_form_id );

		$doi_form_id = $cr_settings[ self::CF7_DOUBLE_OPT_IN_FORM_PROPERTY ];

		return 'none' !== $doi_form_id ? $doi_form_id : null;
	}

	/**
	 * Returns whether user information on CleverReach is incomplete.
	 *
	 * @return bool
	 *
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
	 */
	private function is_user_info_incomplete() {
		if ( $this->get_config_service()->isUserOnline() ) {
			/** @var Proxy $proxy */
			$proxy     = ServiceRegister::getService( Proxy::CLASS_NAME );
			$user_info = $proxy->getUserInfo( $this->get_config_service()->getAccessToken() );
		} else {
			$user_info = $this->get_config_service()->getUserInfo();
		}

		if ( empty( $user_info ) ) {
			return true;
		}

		foreach ( $user_info as $key => $value ) {
			if ( in_array( $key, self::$cleverreach_not_required_user_data, true ) ) {
				continue;
			}

			if ( empty( $value ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns recipient tags.
	 *
	 * @param int $cf7_form_id
	 *
	 * @return mixed
	 */
	private function get_cr_tags( $cf7_form_id ) {
		$cr_settings = $this->get_cr_settings( $cf7_form_id );

		if ( ! array_key_exists( self::CF7_RECIPIENT_TAGS_PROPERTY, $cr_settings )
		     || empty( $cr_settings[ self::CF7_RECIPIENT_TAGS_PROPERTY ] )
		) {
			return array();
		}

		return $cr_settings[ self::CF7_RECIPIENT_TAGS_PROPERTY ];
	}

	/**
	 * Returns CleverReach settings for CF7 form with provided ID.
	 *
	 * @param int $id Contact form ID.
	 *
	 * @return array
	 */
	private function get_cr_settings( $id ) {
		$meta = get_post_meta( $id, '_' . self::CF7_PLUGIN_NAME );

		return ! empty( $meta ) ? $meta[ 0 ] : array();
	}

	/**
	 * Return associative array of CR attributes with corresponding values.
	 *
	 * @param int $form_id Contact form ID.
	 *
	 * @return array
	 */
	private function get_attributes( $form_id ) {
		$cr_settings = $this->get_cr_settings( $form_id );
		$attributes  = array();

		if ( ! array_key_exists( self::CF7_ATTRIBUTE_MAPPINGS_PROPERTY, $cr_settings )
		     || empty( $cr_settings[ self::CF7_ATTRIBUTE_MAPPINGS_PROPERTY ] )
		) {
			return $attributes;
		}

		foreach ( $cr_settings[ self::CF7_ATTRIBUTE_MAPPINGS_PROPERTY ] as $cf7_property => $cr_attribute ) {
			$cf7_property = preg_replace(
				'/^' . preg_quote( 'cleverreach-wp-', '/' ) . '/',
				'',
				$cf7_property
			);
			$cf7_property = preg_replace(
				'/' . preg_quote( '-attribute', '/' ) . '$/',
				'',
				$cf7_property
			);

			if ( ! array_key_exists( $cf7_property, $_POST ) ) {
				$cf7_value = '';
			} else {
				$cf7_value = is_array( $_POST[ $cf7_property ] ) ?
					implode( ',', sanitize_text_field( $_POST[ $cf7_property ] ) ) :
					sanitize_text_field( $_POST[ $cf7_property ] );
			}

			if ( $cr_attribute !== 'birthday' && ! empty( $attributes[ $cr_attribute ] ) ) {
				$attributes[ $cr_attribute ] .= ' ' . $cf7_value;
			} else {
				$attributes[ $cr_attribute ] = $cf7_value;
			}
		}

		return $attributes;
	}

	/**
	 * Saves a new contact.
	 *
	 * @param string $email
	 * @param int    $form_id
	 * @param bool   $is_active
	 * @param bool   $is_subscribed
	 * @param array  $tags
	 * @param array  $special_tags
	 * @param array  $attribute_mappings
	 *
	 * @return int Contact ID.
	 *
	 * @throws RepositoryNotRegisteredException
	 */
	private function save_contact(
		$email,
		$form_id,
		$is_active,
		$is_subscribed,
		$tags = array(),
		$special_tags = array(),
		$attribute_mappings = array()
	) {
		$contact = new Contact(
			$email,
			$form_id,
			$tags,
			$special_tags,
			$attribute_mappings,
			$is_active,
			$is_subscribed,
			get_user_locale()
		);

		$id = $this->get_contact_repository()->save( $contact );
		$contact->setId( $id );
		$this->get_contact_repository()->update( $contact );

		return (int) $contact->getId();
	}

	/**
	 * Updates an existing contact.
	 *
	 * @param Contact $contact
	 * @param int     $form_id
	 * @param bool    $is_active
	 * @param bool    $is_subscribed
	 * @param array   $tags
	 * @param array   $special_tags
	 * @param array   $attribute_mappings
	 *
	 * @return int Contact ID.
	 *
	 * @throws RepositoryNotRegisteredException
	 */
	private function update_contact(
		$contact,
		$form_id,
		$is_active,
		$is_subscribed,
		$tags = array(),
		$special_tags = array(),
		$attribute_mappings = array()
	) {
		$contact->set_form_id( $form_id );
		$contact->set_tags( $tags );
		$contact->set_special_tags( $special_tags );
		$contact->set_attributes( $attribute_mappings );
		$contact->set_active( $is_active );
		$contact->set_subscribed( $is_subscribed );
		$contact->set_language( get_user_locale() );
		$this->get_contact_repository()->update( $contact );

		return (int) $contact->getId();
	}

	/**
	 * Returns an instance of configuration service.
	 *
	 * @return Config_Service
	 */
	private function get_config_service() {
		if ( null === $this->config_service ) {
			$this->config_service = ServiceRegister::getService( Configuration::CLASS_NAME );
		}

		return $this->config_service;
	}

	/**
	 * Returns an instance of queue service.
	 *
	 * @return Queue
	 */
	private function get_queue_service() {
		if ( null === $this->queue_service ) {
			$this->queue_service = ServiceRegister::getService( Queue::CLASS_NAME );
		}

		return $this->queue_service;
	}

	/**
	 * Returns an instance of contact repository.
	 *
	 * @return Base_Repository
	 *
	 * @throws RepositoryNotRegisteredException
	 */
	private function get_contact_repository() {
		if ( null === $this->contact_repository ) {
			$this->contact_repository = RepositoryRegistry::getRepository( Contact::CLASS_NAME );
		}

		return $this->contact_repository;
	}
}
