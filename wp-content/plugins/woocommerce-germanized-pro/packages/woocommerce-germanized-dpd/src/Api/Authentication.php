<?php

namespace Vendidero\Germanized\DPD\Api;

defined( 'ABSPATH' ) || exit;

class Authentication {

	/**
	 * The delis user id for authentication.
	 * @var string
	 */
	protected $delisId;

	/**
	 * The token for authentication. Field authToken of Login, as a result of Method "getAuth" of LoginService.
	 * @var string
	 */
	protected $authToken;

	/**
	 * The language (Java format) for messages.
	 * "de_DE" for german messages.
	 * "en_US" for english messages.
	 * @var string
	 */
	protected $messageLanguage;

	protected $depot;

	protected $customerUid;

	/**
	 * @return string|null
	 */
	public function getDelisId() {
		return isset( $this->delisId ) ? $this->delisId : null;
	}

	/**
	 * @param string $delisId
	 * @return static
	 */
	public function setDelisId( $delisId ) {
		$this->delisId = $delisId;
		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getAuthToken() {
		return isset( $this->authToken ) ? $this->authToken : null;
	}

	/**
	 * @param string $authToken
	 * @return static
	 */
	public function setAuthToken( $authToken ) {
		$this->authToken = $authToken;

		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getMessageLanguage() {
		return isset( $this->messageLanguage ) ? $this->messageLanguage : null;
	}

	/**
	 * @param string $messageLanguage
	 * @return static
	 */
	public function setMessageLanguage( $messageLanguage ) {
		$this->messageLanguage = $messageLanguage;
		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getDepot() {
		return isset( $this->depot ) ? $this->depot : null;
	}

	/**
	 * @param string $depot
	 * @return static
	 */
	public function setDepot( $depot ) {
		$this->depot = $depot;
		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getCustomerUid() {
		return isset( $this->customerUid ) ? $this->customerUid : null;
	}

	/**
	 * @param string $customerUid
	 * @return static
	 */
	public function setCustomerUid( $customerUid ) {
		$this->customerUid = $customerUid;
		return $this;
	}
}