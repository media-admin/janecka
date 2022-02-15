<?php
/**
 * @license GPL-2.0-only
 *
 * Modified by storeabill on 06-July-2021 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace Vendidero\StoreaBill\Vendor\Mpdf\Tag;

use Vendidero\StoreaBill\Vendor\Mpdf\Strict;

use Vendidero\StoreaBill\Vendor\Mpdf\Cache;
use Vendidero\StoreaBill\Vendor\Mpdf\Color\ColorConverter;
use Vendidero\StoreaBill\Vendor\Mpdf\CssManager;
use Vendidero\StoreaBill\Vendor\Mpdf\Form;
use Vendidero\StoreaBill\Vendor\Mpdf\Image\ImageProcessor;
use Vendidero\StoreaBill\Vendor\Mpdf\Language\LanguageToFontInterface;
use Vendidero\StoreaBill\Vendor\Mpdf\Mpdf;
use Vendidero\StoreaBill\Vendor\Mpdf\Otl;
use Vendidero\StoreaBill\Vendor\Mpdf\SizeConverter;
use Vendidero\StoreaBill\Vendor\Mpdf\TableOfContents;

abstract class Tag
{

	use Strict;

	/**
	 * @var \Vendidero\StoreaBill\Vendor\Mpdf\Mpdf
	 */
	protected $mpdf;

	/**
	 * @var \Vendidero\StoreaBill\Vendor\Mpdf\Cache
	 */
	protected $cache;

	/**
	 * @var \Vendidero\StoreaBill\Vendor\Mpdf\CssManager
	 */
	protected $cssManager;

	/**
	 * @var \Vendidero\StoreaBill\Vendor\Mpdf\Form
	 */
	protected $form;

	/**
	 * @var \Vendidero\StoreaBill\Vendor\Mpdf\Otl
	 */
	protected $otl;

	/**
	 * @var \Vendidero\StoreaBill\Vendor\Mpdf\TableOfContents
	 */
	protected $tableOfContents;

	/**
	 * @var \Vendidero\StoreaBill\Vendor\Mpdf\SizeConverter
	 */
	protected $sizeConverter;

	/**
	 * @var \Vendidero\StoreaBill\Vendor\Mpdf\Color\ColorConverter
	 */
	protected $colorConverter;

	/**
	 * @var \Vendidero\StoreaBill\Vendor\Mpdf\Image\ImageProcessor
	 */
	protected $imageProcessor;

	/**
	 * @var \Vendidero\StoreaBill\Vendor\Mpdf\Language\LanguageToFontInterface
	 */
	protected $languageToFont;

	const ALIGN = [
		'left' => 'L',
		'center' => 'C',
		'right' => 'R',
		'top' => 'T',
		'text-top' => 'TT',
		'middle' => 'M',
		'baseline' => 'BS',
		'bottom' => 'B',
		'text-bottom' => 'TB',
		'justify' => 'J'
	];

	public function __construct(
		Mpdf $mpdf,
		Cache $cache,
		CssManager $cssManager,
		Form $form,
		Otl $otl,
		TableOfContents $tableOfContents,
		SizeConverter $sizeConverter,
		ColorConverter $colorConverter,
		ImageProcessor $imageProcessor,
		LanguageToFontInterface $languageToFont
	) {

		$this->mpdf = $mpdf;
		$this->cache = $cache;
		$this->cssManager = $cssManager;
		$this->form = $form;
		$this->otl = $otl;
		$this->tableOfContents = $tableOfContents;
		$this->sizeConverter = $sizeConverter;
		$this->colorConverter = $colorConverter;
		$this->imageProcessor = $imageProcessor;
		$this->languageToFont = $languageToFont;
	}

	public function getTagName()
	{
		$tag = get_class($this);
		return strtoupper(str_replace('Vendidero\StoreaBill\Vendor\Mpdf\Tag\\', '', $tag));
	}

	protected function getAlign($property)
	{
		$property = strtolower($property);
		return array_key_exists($property, self::ALIGN) ? self::ALIGN[$property] : '';
	}

	abstract public function open($attr, &$ahtml, &$ihtml);

	abstract public function close(&$ahtml, &$ihtml);

}
