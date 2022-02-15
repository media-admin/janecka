<?php
/**
 * @license GPL-2.0-only
 *
 * Modified by storeabill on 06-July-2021 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace Vendidero\StoreaBill\Vendor\Mpdf;

use Vendidero\StoreaBill\Vendor\Mpdf\Color\ColorConverter;
use Vendidero\StoreaBill\Vendor\Mpdf\Color\ColorModeConverter;
use Vendidero\StoreaBill\Vendor\Mpdf\Color\ColorSpaceRestrictor;
use Vendidero\StoreaBill\Vendor\Mpdf\Fonts\FontCache;
use Vendidero\StoreaBill\Vendor\Mpdf\Fonts\FontFileFinder;
use Vendidero\StoreaBill\Vendor\Mpdf\Image\ImageProcessor;
use Vendidero\StoreaBill\Vendor\Mpdf\Pdf\Protection;
use Vendidero\StoreaBill\Vendor\Mpdf\Pdf\Protection\UniqidGenerator;
use Vendidero\StoreaBill\Vendor\Mpdf\Writer\BaseWriter;
use Vendidero\StoreaBill\Vendor\Mpdf\Writer\BackgroundWriter;
use Vendidero\StoreaBill\Vendor\Mpdf\Writer\ColorWriter;
use Vendidero\StoreaBill\Vendor\Mpdf\Writer\BookmarkWriter;
use Vendidero\StoreaBill\Vendor\Mpdf\Writer\FontWriter;
use Vendidero\StoreaBill\Vendor\Mpdf\Writer\FormWriter;
use Vendidero\StoreaBill\Vendor\Mpdf\Writer\ImageWriter;
use Vendidero\StoreaBill\Vendor\Mpdf\Writer\JavaScriptWriter;
use Vendidero\StoreaBill\Vendor\Mpdf\Writer\MetadataWriter;
use Vendidero\StoreaBill\Vendor\Mpdf\Writer\OptionalContentWriter;
use Vendidero\StoreaBill\Vendor\Mpdf\Writer\PageWriter;
use Vendidero\StoreaBill\Vendor\Mpdf\Writer\ResourceWriter;
use Vendidero\StoreaBill\Vendor\Psr\Log\LoggerInterface;

class ServiceFactory
{

	public function getServices(
		Mpdf $mpdf,
		LoggerInterface $logger,
		$config,
		$restrictColorSpace,
		$languageToFont,
		$scriptToLanguage,
		$fontDescriptor,
		$bmp,
		$directWrite,
		$wmf
	) {
		$sizeConverter = new SizeConverter($mpdf->dpi, $mpdf->default_font_size, $mpdf, $logger);

		$colorModeConverter = new ColorModeConverter();
		$colorSpaceRestrictor = new ColorSpaceRestrictor(
			$mpdf,
			$colorModeConverter,
			$restrictColorSpace
		);
		$colorConverter = new ColorConverter($mpdf, $colorModeConverter, $colorSpaceRestrictor);

		$tableOfContents = new TableOfContents($mpdf, $sizeConverter);

		$cacheBasePath = $config['tempDir'] . '/mpdf';

		$cache = new Cache($cacheBasePath, $config['cacheCleanupInterval']);
		$fontCache = new FontCache(new Cache($cacheBasePath . '/ttfontdata', $config['cacheCleanupInterval']));

		$fontFileFinder = new FontFileFinder($config['fontDir']);

		$cssManager = new CssManager($mpdf, $cache, $sizeConverter, $colorConverter);

		$otl = new Otl($mpdf, $fontCache);

		$protection = new Protection(new UniqidGenerator());

		$writer = new BaseWriter($mpdf, $protection);

		$gradient = new Gradient($mpdf, $sizeConverter, $colorConverter, $writer);

		$formWriter = new FormWriter($mpdf, $writer);

		$form = new Form($mpdf, $otl, $colorConverter, $writer, $formWriter);

		$hyphenator = new Hyphenator($mpdf);

		$remoteContentFetcher = new RemoteContentFetcher($mpdf, $logger);

		$imageProcessor = new ImageProcessor(
			$mpdf,
			$otl,
			$cssManager,
			$sizeConverter,
			$colorConverter,
			$colorModeConverter,
			$cache,
			$languageToFont,
			$scriptToLanguage,
			$remoteContentFetcher,
			$logger
		);

		$tag = new Tag(
			$mpdf,
			$cache,
			$cssManager,
			$form,
			$otl,
			$tableOfContents,
			$sizeConverter,
			$colorConverter,
			$imageProcessor,
			$languageToFont
		);

		$fontWriter = new FontWriter($mpdf, $writer, $fontCache, $fontDescriptor);
		$metadataWriter = new MetadataWriter($mpdf, $writer, $form, $protection, $logger);
		$imageWriter = new ImageWriter($mpdf, $writer);
		$pageWriter = new PageWriter($mpdf, $form, $writer, $metadataWriter);
		$bookmarkWriter = new BookmarkWriter($mpdf, $writer);
		$optionalContentWriter = new OptionalContentWriter($mpdf, $writer);
		$colorWriter = new ColorWriter($mpdf, $writer);
		$backgroundWriter = new BackgroundWriter($mpdf, $writer);
		$javaScriptWriter = new JavaScriptWriter($mpdf, $writer);

		$resourceWriter = new ResourceWriter(
			$mpdf,
			$writer,
			$colorWriter,
			$fontWriter,
			$imageWriter,
			$formWriter,
			$optionalContentWriter,
			$backgroundWriter,
			$bookmarkWriter,
			$metadataWriter,
			$javaScriptWriter,
			$logger
		);

		return [
			'otl' => $otl,
			'bmp' => $bmp,
			'cache' => $cache,
			'cssManager' => $cssManager,
			'directWrite' => $directWrite,
			'fontCache' => $fontCache,
			'fontFileFinder' => $fontFileFinder,
			'form' => $form,
			'gradient' => $gradient,
			'tableOfContents' => $tableOfContents,
			'tag' => $tag,
			'wmf' => $wmf,
			'sizeConverter' => $sizeConverter,
			'colorConverter' => $colorConverter,
			'hyphenator' => $hyphenator,
			'remoteContentFetcher' => $remoteContentFetcher,
			'imageProcessor' => $imageProcessor,
			'protection' => $protection,

			'languageToFont' => $languageToFont,
			'scriptToLanguage' => $scriptToLanguage,

			'writer' => $writer,
			'fontWriter' => $fontWriter,
			'metadataWriter' => $metadataWriter,
			'imageWriter' => $imageWriter,
			'formWriter' => $formWriter,
			'pageWriter' => $pageWriter,
			'bookmarkWriter' => $bookmarkWriter,
			'optionalContentWriter' => $optionalContentWriter,
			'colorWriter' => $colorWriter,
			'backgroundWriter' => $backgroundWriter,
			'javaScriptWriter' => $javaScriptWriter,

			'resourceWriter' => $resourceWriter
		];
	}

}
