<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

namespace CleverReach\WordPress\Components;

use CleverReach\WordPress\Components\Utility\Forms_Formatter;
use CleverReach\WordPress\Components\Utility\Helper;
use CleverReach\WordPress\Components\Utility\Initializer;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Forms\Models\Form;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\RepositoryRegistry;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryClassException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\QueryFilter\Operators;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\QueryFilter\QueryFilter;
use WP_Widget;

/**
 * Class Wp_Clever_Reach_Widget
 *
 * @package CleverReach\WordPress\Components
 */
class Wp_Clever_Reach_Widget extends WP_Widget {

	/**
	 * @var array
	 */
	private $admin_view_parameters = array();
	/**
	 * @var array
	 */
	private $frontend_view_parameters = array();

	/**
	 * Wp_Clever_Reach_Widget constructor.
	 *
	 * @throws RepositoryClassException
	 */
	public function __construct() {
		parent::__construct(
			'wp_cleverreach',
			__( 'CleverReach®', 'wp-cleverreach' ),
			array( 'description' => __( 'Displays a CleverReach® Subscribe form', 'cleverreach-wp' ), )
		);

		Initializer::register();
	}

	/**
	 * @inheritDoc
	 *
	 * @param array $instance
	 *
	 * @return string|void
	 * @throws RepositoryNotRegisteredException
	 */
	public function form( $instance ) {
		$form_repository = RepositoryRegistry::getRepository( Form::getClassName() );
		/** @var Form[] $forms */
		$forms         = $form_repository->select();
		$form_id       = $this->get_form_id( $instance, $forms );
		$edit_form_url = Helper::get_controller_url( 'Forms', array( 'action' => 'open_form_in_cleverreach' ) );

		$this->admin_view_parameters = array(
			'forms'         => $forms,
			'edit_form_url' => $edit_form_url,
			'form_id'       => $form_id,
		);

		include __DIR__ . '/../resources/views/widget-admin.php';
	}

	/**
	 * @inheritDoc
	 *
	 * @param array $args
	 * @param array $instance
	 *
	 * @throws RepositoryNotRegisteredException
	 * @throws QueryFilterInvalidParamException
	 */
	public function widget( $args, $instance ) {
		$form_repository = RepositoryRegistry::getRepository( Form::getClassName() );
		$filter          = new QueryFilter();

		if ( ! empty( $instance[ 'form' ] ) ) {
			$filter->where( 'formId', Operators::EQUALS, $instance[ 'form' ] );
		}

		/** @var Form $form */
		$form = $form_repository->selectOne( $filter );
		if ( $form ) {
			$title = apply_filters( 'widget_title', $form->getName(), $instance, $this->id_base );

			$this->frontend_view_parameters = array(
				'before_widget' => $args[ 'before_widget' ],
				'after_widget'  => $args[ 'after_widget' ],
				'before_title'  => $args[ 'before_title' ],
				'after_title'   => $args[ 'after_title' ],
				'title'         => $title,
				'form_body'     => Forms_Formatter::get_form_code( $form->getHtml() ),
			);

			include __DIR__ . '/../resources/views/widget-frontend.php';
		}
	}

	/**
	 * Returns view parameters for admin form template
	 *
	 * @return array of admin view parameters
	 */
	public function get_admin_view_parameters() {
		return $this->admin_view_parameters;
	}

	/**
	 * Returns view parameters for frontend form template
	 *
	 * @return array of frontend view parameters
	 */
	public function get_frontend_view_parameters() {
		return $this->frontend_view_parameters;
	}

	/**
	 * Returns id of selected form
	 *
	 * @param array  $instance
	 * @param Form[] $forms
	 *
	 * @return string
	 */
	private function get_form_id( $instance, $forms ) {
		if ( ! empty( $instance[ 'form' ] ) && $this->is_form_exists( $instance[ 'form' ], $forms ) ) {
			return $instance[ 'form' ];
		}

		if ( ! empty( $forms ) ) {
			return $forms[ 0 ]->getFormId();
		}

		return '';
	}

	/**
	 * Checks if form exists
	 *
	 * @param string $form_id
	 * @param Form[] $forms
	 *
	 * @return bool
	 */
	private function is_form_exists( $form_id, $forms ) {
		foreach ( $forms as $form ) {
			if ( $form->getFormId() === $form_id ) {
				return true;
			}
		}

		return false;
	}
}
