<?php

class WC_GZDP_VAT_Validation_Switzerland {

	private $api_url = "https://www.uid-wse.admin.ch/V5.0/PublicServices.svc?wsdl";

	private $client = null;

	private $options = array(
		'debug' => false,
	);

	private $valid = false;

	private $data = array();

	private $errors = false;

	public function __construct( $options = array() ) {

		foreach( $options as $option => $value ) {
			$this->options[ $option ] = $value;
		}

		if ( ! class_exists( 'SoapClient' ) ) {
			wp_die( __( 'SoapClient is required to enable VAT validation', 'woocommerce-germanized-pro' ) );
		}

		try {
			$this->client = new SoapClient( $this->api_url, array( 'trace' => true, "exceptions" => true ) );
		} catch( Exception $e ) {
			WC_germanized_pro()->log( sprintf( 'Error %s while setting up the SOAP Client for CH VAT validation: %s', $e->getCode(), $e->getMessage() ), 'error', 'vat-validation' );

			$this->valid = false;
		}
	}

	public function check( $country, $nr ) {
		$rs           = null;
		$this->errors = new WP_Error();
		$instance     = WC_germanized_pro();

		if ( $this->client ) {
			try {
				$args = array(
					'vatNumber' => $country . $nr
				);

				$rs = $this->client->ValidateVatNumber( $args );

				if ( true === $rs->ValidateVatNumberResult ) {
					$instance->log( sprintf( 'Successfully validated: %s', $args['vatNumber'] ), 'info', 'vat-validation' );

					$this->data = array();
					$this->valid = true;

				} else {
					$instance->log( sprintf( 'VAT is invalid: %s', $args['vatNumber'] ), 'info', 'vat-validation' );

					$this->valid = false;
					$this->data = array();

					$this->errors->add( 'vat-id-invalid', __( 'The VAT ID you\'ve provided is invalid.', 'woocommerce-germanized-pro' ) );
				}

			} catch( SoapFault $e ) {
				$this->valid = false;
				$this->data  = array();

				$instance->log( sprintf( 'SOAP Error (%s) while performing CH VAT ID validation: %s', $e->getCode(), $e->getMessage() ), 'error', 'vat-validation' );

				$this->errors->add( 'vat-request-error', __( 'There was an error while validating your VAT ID. Please try again in a few minutes.', 'woocommerce-germanized-pro' ) );
			}
		}

		return apply_filters( 'woocommerce_gzdp_vat_validation_result', $this->valid, $country, $nr, $rs, $this->options, $this->errors );
	}

	public function get_error_messages() {
		return ( is_wp_error( $this->errors ) && wc_gzd_wp_error_has_errors( $this->errors ) ) ? $this->errors : false;
	}

	public function get_data() {
		return $this->data;
	}

	public function is_valid() {
		return $this->valid;
	}

	public function is_debug() {
		return ( $this->options['debug'] === true );
	}
}

?>