<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

namespace CleverReach\WordPress\Components;

use CleverReach\WordPress\Components\Utility\Forms_Formatter;
use CleverReach\WordPress\Components\Utility\Initializer;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Forms\Models\Form;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\RepositoryRegistry;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Surveys\SurveyType;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryClassException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\QueryFilter\Operators;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\QueryFilter\QueryFilter;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;

/**
 * Class Shortcode_Handler
 *
 * @package CleverReach\WordPress\Components
 */
class Shortcode_Handler {

	/**
	 * Creates plugin shortcodes
	 */
	public function create_shortcodes() {
		add_shortcode( 'cleverreach', array( $this, 'cleverreach_form_handler' ) );
	}

	/**
	 * Handler for cleverreach shortcode.
	 *
	 * @param array $attributes Shortcode attributes
	 *
	 * @return string
	 * @throws QueryFilterInvalidParamException
	 * @throws RepositoryClassException
	 * @throws RepositoryNotRegisteredException
	 * @throws \Exception
	 */
	public function cleverreach_form_handler( $attributes ) {
		Initializer::register();
		if ( empty( $attributes[ 'form' ] ) ) {
			return '';
		}

		$form_repository = RepositoryRegistry::getRepository( Form::getClassName() );
		$filter = new QueryFilter();
		$filter->where( 'formId', Operators::EQUALS, $attributes[ 'form' ] );
		/** @var Form $form */
		$form = $form_repository->selectOne( $filter );
		if ( ! $form ) {
			return '';
		}

		return Forms_Formatter::get_form_code( $form->getHtml() );
	}
}
