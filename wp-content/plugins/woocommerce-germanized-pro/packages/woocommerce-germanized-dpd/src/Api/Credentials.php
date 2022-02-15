<?php

namespace Vendidero\Germanized\DPD\Api;

defined( 'ABSPATH' ) || exit;

class Credentials {

	/**
	 * The DELIS-Id of the user.
	 * @var string
	 */
	protected $delisId;

	/**
	 * The password of the user.
	 * @var string
	 */
	protected $password;

	/**
	 * The language (Java format) for messages.
	 * "de_DE" for german messages.
	 * "en_US" for english messages.
	 * @var string
	 */
	protected $messageLanguage;

	/**
	 * @param string $delisId The DELIS-Id of the user.
	 * @param string $password The password of the user.
	 * @param string $messageLanguage The language (e.g. en_US or de_DE) for messages
	 */
	public function __construct( $delisId, $password, $messageLanguage = 'en_US' ) {
		$this->setDelisId( $delisId )->setPassword( $password )->setMessageLanguage( $messageLanguage );
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
	 * @param string $password
	 * @return static
	 */
	public function setPassword( $password ) {
		$this->password = $password;
		return $this;
	}

	/**
	 * @param string $messageLanguage
	 * @return static
	 */
	public function setMessageLanguage( $messageLanguage ) {
		$this->messageLanguage = $messageLanguage;

		return $this;
	}
}