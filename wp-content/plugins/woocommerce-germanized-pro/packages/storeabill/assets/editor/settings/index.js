/**
 * Internal dependencies
 */
import { getSetting } from './get-setting';
import { allSettings } from "./settings-init";
import { ITEM_TOTAL_TYPES, ITEM_META_TYPES } from './default-constants';

import { select, useSelect } from "@wordpress/data";
import { isEmpty, includes, concat, isObject, isArray } from 'lodash';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { ToolbarButton } from "@wordpress/components";
import { __, _x } from '@wordpress/i18n';

export * from './default-constants';
export { setSetting } from './set-setting';
export { getSetting };

export function getItemMetaTypes( itemType = 'product' ) {
	return ITEM_META_TYPES.hasOwnProperty( itemType ) ? ITEM_META_TYPES[ itemType ] : [];
}

export function blockHasParent( clientId, blocks ) {
	if ( ! Array.isArray( blocks ) ) {
		blocks = [ blocks ];
	}

	const parents = select( 'core/block-editor' ).getBlockParents( clientId );

	if ( parents.length > 0 ) {
		// Check outermost root parent
		const rootParent = select( 'core/block-editor' ).getBlock( parents[0] );

		if ( includes( blocks, rootParent.name ) ) {
			return true;
		}
	}

	return false;
}

export function documentTypeSupports( what ) {
	const supports = getSetting( 'supports' );

	return includes( supports, what );
}

export function getDefaultInnerBlocks( blockType ) {
	const defaultBlocks = getSetting( 'defaultInnerBlocks' );

	return defaultBlocks.hasOwnProperty( blockType ) ? defaultBlocks[ blockType ] : [];
}

export function getItemMetaTypePreview( metaType, itemType = 'product' ) {
	const metaTypeData    = getItemMetaTypes( itemType );
	const defaultMetaData = metaTypeData.filter( ( type ) => {
		if ( metaType === type.type ) {
			return true;
		}
	} );

	const defaultPreviewData = defaultMetaData.length > 0 ? defaultMetaData[0].preview : '';
	const previewData = getPreviewItem( itemType );

	const previewMeta = previewData['meta_data'].filter( ( meta ) => {
		if ( metaType === meta.key ) {
			return true;
		}
	} );

	if ( previewMeta.length > 0 ) {
		return previewMeta[0].value;
	} else {
		return defaultPreviewData;
	}
}

export function getPreview() {
	return getSetting( 'preview', {} );
}

export function getPreviewItem( itemType = 'product' ) {
	const preview = getPreview();

	return preview[itemType + '_items'][0];
}

export function getItemTotalTypeDefaultTitle( totalType ) {
	const defaultTitle = ITEM_TOTAL_TYPES.filter( ( type ) => {
		if ( totalType === type.type ) {
			return true;
		}
	} );

	return defaultTitle && defaultTitle[0] ? defaultTitle[0].default : '';
}

export function getItemTotalTypeTitle( totalType ) {
	const defaultTitle = ITEM_TOTAL_TYPES.filter( ( type ) => {
		if ( totalType === type.type ) {
			return true;
		}
	} );

	return defaultTitle && defaultTitle[0] ? defaultTitle[0].title : '';
}

export function getPreviewTotal( totalType = 'total' ) {
	const preview = getPreview();

	let totals = preview['totals'];

	let typeTotals = totals.filter(
		( total ) => total.type === totalType
	);

	if ( typeTotals.length > 0 ) {
		return typeTotals[0]['total_formatted'];
	}

	return 0;
}

export function getPreviewTaxRate() {
	const preview = getPreview();
	let items 	  = preview['tax_items'];

	if ( items.length > 0 ) {
		return items[0]['rate']['percent'];
	}

	return '{rate}';
}

export function getPreviewDiscountNotice() {
	const preview = getPreview();

	return preview.formatted_discount_notice;
}

export function getPreviewFeeName() {
	const preview = getPreview();
	let items 	  = preview['fee_items'];

	if ( items.length > 0 ) {
		return items[0]['name'];
	}

	return '{name}';
}

export function formatMargins( margins, defaultMargins, context = 'read' ) {

	let newMargins = {
		top: margins['top'] ? margins['top'] : defaultMargins['top'],
		left: margins['left'] ? margins['left'] : defaultMargins['left'],
		right: margins['right'] ? margins['right'] : defaultMargins['right'],
		bottom: margins['bottom'] ? margins['bottom'] : defaultMargins['bottom'],
	};

	if ( 'edit' === context ) {
		const marginTypesSupported = getSetting( 'marginTypesSupported' );

		let editMargins = {};

		marginTypesSupported.forEach( ( marginType ) => {
			editMargins[ marginType ] = newMargins[ marginType ];
		});

		return editMargins;
	}

	return newMargins;
}

export function isDocumentTemplate() {
	if ( select( 'core/editor' ).getCurrentPostType() === 'document_template' ) {
		return true;
	}

	return false;
}

export function getAllowedBlockTypes() {
	return getSetting( 'allowedBlockTypes' );
}

export function getDocumentStylesBlock() {
	const { getBlocks } = select( 'core/block-editor' );
	let blocks = getBlocks();
	let innerBlock = undefined;

	let innerBlocks = blocks.filter( ( block ) => {
		if ( block.name === 'storeabill/document-styles' ) {
			return block;
		}
	} );

	if ( innerBlocks.length > 0 ) {
		innerBlock = innerBlocks[0];
	}

	return innerBlock;
}

export function getFonts() {
	return getSetting( 'fonts' );
}

export function getFont( fontName ) {
	const fonts = getFonts();

	const filteredItems = fonts.filter(
		( { name } ) => {
			if ( name === fontName ) {
				return true;
			}
		}
	);

	if ( ! isEmpty( filteredItems ) ) {
		return filteredItems[0];
	}

	return undefined;
}

export function getCurrentFonts() {
	const { getEditedPostAttribute } = select( 'core/editor' );
	const meta = getEditedPostAttribute( 'meta' );

	return meta['_fonts'] ? meta['_fonts'] : undefined;
}

export function getFontsCSS( fonts ) {
	fonts = fonts || getCurrentFonts();

	const newURL = addQueryArgs( '/sab/v1/preview_fonts/css', {
		'fonts': fonts,
		'display_types': getSetting( 'fontDisplayTypes' )
	} );

	// GET
	return apiFetch( { path: newURL } );
}

export function getItemTotalKey( prefix, incTax = true, discountType = '' ) {
	let key = prefix;

	if ( 'before_discounts' === discountType ) {
		key = key + '_subtotal';

		if ( includes( prefix, 'total' ) ) {
			key = prefix.replace( 'total', '' );
		}

		if ( 'total' === prefix ) {
			key = 'subtotal';
		}
	}

	if ( false === incTax ) {
		if ( includes( prefix, '_total' ) ) {
			key = key.replace( '_total', '' );
		}

		key = key + '_net';
	}

	return key + '_formatted';
}

export function getShortcodeCategoryTitle( category ) {
	let title = '';

	if ( 'document' === category ) {
		title = _x( 'Document', 'storeabill-core', 'storeabill' );
	} else if ( 'document_item' === category ) {
		title = _x( 'Document Item', 'storeabill-core', 'storeabill' );
	} else if ( 'document_total' === category ) {
		title = _x( 'Document Total', 'storeabill-core', 'storeabill' );
	} else if ( 'setting' === category ) {
		title = _x( 'Settings', 'storeabill-core', 'storeabill' );
	}

	return title;
}

export function getAvailableShortcodeTree( forType = '', blockName = '', hasHeaderOrFooterParent = false ) {
	const shortcodes = getSetting( 'shortcodes' );
	const entries 	 = Object.entries( shortcodes );
	const globals    = [ 'blocks', 'setting' ];
	let shortcodeObj = {};

	entries.forEach( ( element, index ) => {
		const category = element[0];

		if ( forType.length > 0 && forType !== category && ! includes( globals, category ) ) {
			return;
		} else if ( category === 'blocks' && blockName.length === 0 ) {
			return;
		}
		let innerShortcodes = [];
		let title 			= getShortcodeCategoryTitle( category );

		if ( isArray( element[1] ) ) {
			innerShortcodes = element[1].flat();
		} else {
			if ( blockName.length > 0 ) {
				innerShortcodes = element[1].hasOwnProperty( blockName ) ? element[1][ blockName ] : [];
				const blockType = select( 'core/blocks' ).getBlockType( blockName );

				title = ( blockType ? blockType.title : blockName );
			}
		}

		if ( ! shortcodeObj.hasOwnProperty( category ) ) {
			shortcodeObj[ category ] = {
				'label': title,
				'value': category,
				'children': {},
			};
		}

		innerShortcodes.map( ( obj ) => {
			if ( ! shortcodeObj[ category ]['children'].hasOwnProperty( obj.shortcode ) ) {
				/**
				 * Maybe skip shortcodes available for header and footer only
				 */
				if ( ! hasHeaderOrFooterParent && obj.hasOwnProperty( 'headerFooterOnly' ) && obj.headerFooterOnly ) {
					return;
				}

				shortcodeObj[ category ]['children'][ obj.shortcode ] = {
					value: obj.shortcode,
					label: obj.title
				};
			}
		} );
	} );

	let shortcodeList = [];

	Object.entries( shortcodeObj ).map( ( data ) => {
		const children = Object.values( data[1].children ).flat();

		if ( ! isEmpty( children ) ) {
			shortcodeList.push( {
				'value': data[1].value,
				'label': data[1].label,
				'children': children
			} );
		}
	} );

	return shortcodeList;
}

export function getShortcodeData( shortcode ) {
	const shortcodes   = getSetting( 'shortcodes' );
	const entries 	   = Object.entries( shortcodes );
	const shortcodeObj = {};

	entries.forEach( ( element, index ) => {
		let innerShortcodes = [];

		if ( ! isArray( element[1] ) ) {
			innerShortcodes = Object.values( element[1] ).flat();
		} else {
			innerShortcodes = element[1].flat();
		}

		innerShortcodes.map( ( obj ) => {
			if ( ! shortcodeObj.hasOwnProperty( obj.shortcode ) ) {
				shortcodeObj[ obj.shortcode ] = obj;
			}
		} );
	} );

	return shortcodeObj.hasOwnProperty( shortcode ) ? shortcodeObj[ shortcode ] : false;
}

export function getShortcodeTitle( shortcode ) {
	const shortcodeData = getShortcodeData( shortcode );

	return shortcodeData ? shortcodeData.title : '';
}

export function getDateTypes() {
	return getSetting( 'dateTypes' );
}

export function getBarcodeTypes() {
	return getSetting( 'barcodeTypes' );
}

export function getBarcodeCodeTypes() {
	return getSetting( 'barcodeCodeTypes' );
}

export function getDateTypeTitle( dateType ) {
	const dateTypes = getSetting( 'dateTypes' );
	let title = _x( 'Date', 'storeabill-core', 'storeabill' );

	Object.entries( dateTypes ).map( ( data ) => {
		if ( data[0] === dateType ) {
			title = data[1];
		}
	} );

	return title;
}

export function getShortcodePreview( shortcodeQuery ) {
	const newURL = addQueryArgs( '/sab/v1/preview_shortcodes', {
		'query': shortcodeQuery,
		'document_type': getSetting( 'documentType' )
	} );

	// GET
	return apiFetch( { path: newURL } );
}