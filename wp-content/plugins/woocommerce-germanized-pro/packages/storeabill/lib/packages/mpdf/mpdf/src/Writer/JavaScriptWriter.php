<?php
/**
 * @license GPL-2.0-only
 *
 * Modified by storeabill on 06-July-2021 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace Vendidero\StoreaBill\Vendor\Mpdf\Writer;

use Vendidero\StoreaBill\Vendor\Mpdf\Strict;
use Vendidero\StoreaBill\Vendor\Mpdf\Mpdf;

final class JavaScriptWriter
{

	use Strict;

	/**
	 * @var \Vendidero\StoreaBill\Vendor\Mpdf\Mpdf
	 */
	private $mpdf;

	/**
	 * @var \Vendidero\StoreaBill\Vendor\Mpdf\Writer\BaseWriter
	 */
	private $writer;

	public function __construct(Mpdf $mpdf, BaseWriter $writer)
	{
		$this->mpdf = $mpdf;
		$this->writer = $writer;
	}

	public function writeJavascript() // _putjavascript
	{
		$this->writer->object();
		$this->mpdf->n_js = $this->mpdf->n;
		$this->writer->write('<<');
		$this->writer->write('/Names [(EmbeddedJS) ' . (1 + $this->mpdf->n) . ' 0 R ]');
		$this->writer->write('>>');
		$this->writer->write('endobj');

		$this->writer->object();
		$this->writer->write('<<');
		$this->writer->write('/S /JavaScript');
		$this->writer->write('/JS ' . $this->writer->string($this->mpdf->js));
		$this->writer->write('>>');
		$this->writer->write('endobj');
	}

}
