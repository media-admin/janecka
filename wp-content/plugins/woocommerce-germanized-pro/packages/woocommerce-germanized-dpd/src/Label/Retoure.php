<?php

namespace Vendidero\Germanized\DPD\Label;

use Vendidero\Germanized\Shipments\Interfaces\ShipmentReturnLabel;

defined( 'ABSPATH' ) || exit;

/**
 * DPD ReturnLabel class.
 */
class Retoure extends Simple implements ShipmentReturnLabel {

	protected function get_hook_prefix() {
		return 'woocommerce_gzd_dpd_return_label_get_';
	}

	public function get_type() {
		return 'return';
	}
}