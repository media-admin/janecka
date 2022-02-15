<?php
/**
 * @license GPL-2.0-only
 *
 * Modified by storeabill on 06-July-2021 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace Vendidero\StoreaBill\Vendor\Mpdf\Tag;

class IndexInsert extends Tag
{

	public function open($attr, &$ahtml, &$ihtml)
	{
		$indexCollationLocale = '';
		if (isset($attr['COLLATION'])) {
			$indexCollationLocale = $attr['COLLATION'];
		}

		$indexCollationGroup = '';
		if (isset($attr['COLLATION-GROUP'])) {
			$indexCollationGroup = $attr['COLLATION-GROUP'];
		}

		$usedivletters = 1;
		if (isset($attr['USEDIVLETTERS']) && (strtoupper($attr['USEDIVLETTERS']) === 'OFF'
				|| $attr['USEDIVLETTERS'] == -1
				|| $attr['USEDIVLETTERS'] === '0')) {
			$usedivletters = 0;
		}
		$links = isset($attr['LINKS']) && (strtoupper($attr['LINKS']) === 'ON' || $attr['LINKS'] == 1);
		$this->mpdf->InsertIndex($usedivletters, $links, $indexCollationLocale, $indexCollationGroup);
	}

	public function close(&$ahtml, &$ihtml)
	{
	}
}
