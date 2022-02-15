<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

namespace CleverReach\WordPress\Controllers;

use CleverReach\WordPress\Components\InfrastructureServices\Config_Service;
use CleverReach\WordPress\Components\Repositories\Base_Repository;
use CleverReach\WordPress\Components\Utility\Helper;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Forms\Models\Form;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\RepositoryRegistry;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\SingleSignOn\SingleSignOnProvider;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\QueryFilter\Operators;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\QueryFilter\QueryFilter;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;

/**
 * Class Clever_Reach_Forms_Controller
 *
 * @package CleverReach\WordPress\Controllers
 */
class Clever_Reach_Forms_Controller extends Clever_Reach_Base_Controller {

	/**
	 * @var Base_Repository
	 */
	private $form_repository;

	/**
	 * Returns all forms in json format
	 *
	 * @throws RepositoryNotRegisteredException
	 * @throws QueryFilterInvalidParamException
	 */
	public function get_all_forms() {
		/** @var Form[] $form_entities */
		$form_entities = $this->get_form_repository()->select();
		$results = array();
		foreach ( $form_entities as $form ) {
			$raw_form = $form->toArray();
			$this->remove_unused_data( $raw_form );
			$raw_form[ 'url' ] = Helper::get_controller_url(
				'Forms',
				array(
					'action'  => 'open_form_in_cleverreach',
					'form_id' => $form->getFormId(),
				)
			);
			$results[] = $raw_form;
		}

		$this->die_json( $results );
	}

	/**
	 * Return form in json format filtered by formId
	 *
	 * @throws QueryFilterInvalidParamException
	 * @throws RepositoryNotRegisteredException
	 */
	public function get_form_by_id() {
		if ( !$form_id = $this->get_param( 'form_id' ) ) {
			$this->return_error( __( 'Bad Request. Form ID not provided.' ) );
		}

		$filter = new QueryFilter();
		$filter->where( 'formId', Operators::EQUALS, $form_id );
		$form = $this->get_form_repository()->selectOne( $filter );
		if ( $form === null ) {
			$this->return_error( __( 'Form with provided ID not found' ) );
		}

		$raw_form = $form->toArray();
		$this->remove_unused_data( $raw_form );

		$this->die_json( $raw_form );
	}

	/**
	 * Redirects user to either single sign on link or, if it's not available, base link to form on CR.
	 */
	public function open_form_in_cleverreach() {
		if ( ! $form_id = $this->get_param( 'form_id' ) ) {
			$this->return_error( __( 'Bad Request. Form ID not provided.' ) );
		}

		$deep_link = '/admin/forms_layout_create.php?id=' . $form_id;
		$link      = '';

		try {
			$link = SingleSignOnProvider::getUrl( $deep_link );
		} catch ( \Exception $exception ) {
		}

		if ( empty( $link ) ) {
			$link = $this->get_fallback_link( $deep_link );
		}

		wp_redirect( $link );
	}

	/**
	 * Returns fallback link to CleverReach form.
	 *
	 * @param string $deep_link
	 *
	 * @return string
	 */
	private function get_fallback_link( $deep_link ) {
		/** @var Config_Service $config_service */
		$config_service = ServiceRegister::getService( Configuration::CLASS_NAME );
		$user_info      = $config_service->getUserInfo();

		return 'https://' . $user_info[ 'login_domain' ] . $deep_link;
	}

	/**
	 * Removes unused data from form fetched from cache
	 *
	 * @param array $raw_form cached form
	 */
	private function remove_unused_data( & $raw_form ) {
		unset(
			$raw_form[ 'class_name' ],
			$raw_form[ 'id' ],
			$raw_form[ 'lastUpdateTimestamp' ],
			$raw_form[ 'context' ],
			$raw_form[ 'hash' ]
		);
	}

	/**
	 * Display error message in json format
	 *
	 * @param string $message error message
	 */
	private function return_error( $message ) {
		$this->die_json( array(
				'success' => false,
				'message' => $message,
			)
		);
	}

	/**
	 * @return Base_Repository
	 *
	 * @throws RepositoryNotRegisteredException
	 */
	private function get_form_repository() {
		if ( $this->form_repository === null ) {
			$this->form_repository = RepositoryRegistry::getRepository( Form::getClassName() );
		}

		return $this->form_repository;
	}
}
