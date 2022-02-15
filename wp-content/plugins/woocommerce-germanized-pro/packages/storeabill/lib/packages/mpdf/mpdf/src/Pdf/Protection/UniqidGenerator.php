<?php
/**
 * @license GPL-2.0-only
 *
 * Modified by storeabill on 06-July-2021 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace Vendidero\StoreaBill\Vendor\Mpdf\Pdf\Protection;

class UniqidGenerator
{

	public function __construct()
	{
		if (!function_exists('random_int') || !function_exists('random_bytes')) {
			throw new \Vendidero\StoreaBill\Vendor\Mpdf\MpdfException(
				'Unable to set PDF file protection, CSPRNG Functions are not available. '
				. 'Use paragonie/random_compat polyfill or upgrade to PHP 7.'
			);
		}
	}

	/**
	 * @return string
	 */
	public function generate()
	{
		$chars = 'ABCDEF1234567890';
		$id = '';

		for ($i = 0; $i < 32; $i++) {
			$id .= $chars[random_int(0, 15)];
		}

		return md5($id);
	}
}
