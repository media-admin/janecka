<?php

namespace CleverReach\WordPress\Controllers;

use CleverReach\WordPress\IntegrationCore\BusinessLogic\Proxy\SurveyProxy;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;

/**
 * Class Clever_Reach_Survey_Controller
 *
 * @package CleverReach\WordPress\Controllers
 */
class Clever_Reach_Survey_Controller extends Clever_Reach_Base_Controller {
	/**
	 * @var SurveyProxy
	 */
	private $survey_proxy;
	/**
	 * @var array
	 */
	private static $supported_languages = array('en', 'de');

	/**
	 * Handles incoming request.
	 *
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
	 */
	public function handle() {
		if ( $this->is_post() ) {
			$this->post();
		} else {
			$this->get();
		}
	}

	/**
	 * Ignores survey.
	 *
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
	 */
	public function ignore() {
		$token       = $this->get_param( 'token' );
		$survey_id   = $this->get_param( 'pollId' );
		$customer_id = $this->get_param( 'customerId' );

		if ( null === $token || null === $survey_id || null === $customer_id ) {
			$this->die_with_status( 400 );
		}

		$response_code = $this->get_survey_proxy()->ignore( $token, $survey_id, $customer_id );

		$this->die_with_status( $response_code );
	}

	/**
	 * Returns available survey if it exists.
	 */
	private function get() {
		$type = $this->get_param( 'type' );

		if ( null === $type ) {
			$this->die_with_status( 400 );
		}

		$response = $this->get_survey_proxy()->get( $type, $this->get_user_language() );

		if ( empty( $response ) ) {
			$this->die_with_status( 303 );
		}

		$this->die_json( $response );
	}

	/**
	 * Submits survey.
	 *
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
	 */
	private function post() {
		$token = $this->get_param( 'token' );

		if ( null === $token ) {
			$this->die_with_status( 400 );
		}

		$payload       = json_decode( $this->get_raw_input(), true );
		$response_code = $this->get_survey_proxy()->post( $token, $payload, $this->get_user_language() );

		$this->die_with_status( $response_code );
	}

	/**
	 * Returns an instance of survey proxy.
	 *
	 * @return SurveyProxy
	 */
	private function get_survey_proxy() {
		if ( $this->survey_proxy === null ) {
			$this->survey_proxy = ServiceRegister::getService( SurveyProxy::CLASS_NAME );
		}

		return $this->survey_proxy;
	}

	/**
	 * Returns current user language.
	 *
	 * @return string
	 */
	private function get_user_language() {
		$full_locale = explode( '_', get_user_locale() );
		$locale      = $full_locale[ 0 ];
		if ( ! in_array( $locale, self::$supported_languages ) ) {
			$locale = 'en';
		}

		return $locale;
	}
}
