<?php
/**
 * @license GPL-2.0-only
 *
 * Modified by storeabill on 06-July-2021 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace Vendidero\StoreaBill\Vendor\Mpdf\Tag;

abstract class SubstituteTag extends Tag
{

	public function close(&$ahtml, &$ihtml)
	{
		$tag = $this->getTagName();
		if ($this->mpdf->InlineProperties[$tag]) {
			$this->mpdf->restoreInlineProperties($this->mpdf->InlineProperties[$tag]);
		}
		unset($this->mpdf->InlineProperties[$tag]);
		$ltag = strtolower($tag);
		$this->mpdf->$ltag = false;
	}
}
