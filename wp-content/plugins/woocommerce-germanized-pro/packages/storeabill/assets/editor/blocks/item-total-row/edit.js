/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import {
	AlignmentToolbar,
	BlockControls,
	InspectorControls,
	RichText,
	RichTextToolbarButton,
	withColors,
	withFontSizes,
	FontSizePicker
} from '@wordpress/block-editor';
import { PanelBody, RangeControl, Slot, ToggleControl, ToolbarButton } from '@wordpress/components';
import { __, _x } from '@wordpress/i18n';

import { BorderSelect, getBorderClasses } from '@storeabill/components/border-select';
import { replacePreviewWithPlaceholder, replacePlaceholderWithPreview, getFontSizeStyle, convertFontSizeForPicker, useColors } from "@storeabill/utils";

/**
 * External dependencies
 */
import { find, includes, isEmpty } from 'lodash';

/**
 * WordPress dependencies
 */
import { Toolbar, DropdownMenu, ToolbarGroup } from '@wordpress/components';
import { compose } from "@wordpress/compose";
import {useSelect, withSelect} from "@wordpress/data";
import { getSetting, formatPrice } from '@storeabill/settings';

import { ITEM_TOTAL_TYPES, getPreviewTotal, FORMAT_TYPES, getPreviewTaxRate, getItemTotalTypeDefaultTitle, getItemTotalTypeTitle, getPreviewFeeName, getPreviewDiscountNotice, getPreviewVoucherNotice } from '@storeabill/settings';
import { settings, arrowRight } from '@storeabill/icons';

import {useRef} from "@wordpress/element";

function TypeSelect( props ) {
	const {
		value,
		onChange,
		types = ITEM_TOTAL_TYPES,
		label = _x( 'Change total type', 'storeabill-core', 'storeabill' ),
		isCollapsed = true,
	} = props;

	const activeType = find(
		types,
		( control ) => control.type === value
	);

	return (
		<ToolbarGroup>
			<DropdownMenu
				icon={ settings }
				label={ label }
				controls={ types.map( ( control ) => {
					const { type } = control;
					const isActive = value === type;

					return {
						...control,
						icon: isEmpty( control.icon ) ? arrowRight : control.icon,
						isActive: isActive,
						role: isCollapsed ? 'menuitemradio' : undefined,
						onClick: () => onChange( type )
					};
				} ) }
			/>
		</ToolbarGroup>
	);
}

function TotalRowEdit( {
	attributes,
	setAttributes,
	borderColor,
	className,
	fontSize,
	setFontSize,
} ) {

	const { heading, totalType, borders, content, hideIfEmpty } = attributes;

	let total 		     = getPreviewTotal( totalType );
	const title			 = getItemTotalTypeTitle( totalType );
	const defaultContent = '<span class="item-total-inner-content placeholder-content sab-tooltip" data-tooltip="' + title + '" contenteditable="false"><span class="editor-placeholder"></span>{total}</span>';
	let innerHeading     = heading ? heading : getItemTotalTypeDefaultTitle( totalType );

	const { itemTypes } = useSelect(
		( select ) => {
			const { getEditedPostAttribute } = select( 'core/editor' );
			const meta  = getEditedPostAttribute( 'meta' );

			return {
				itemTypes: meta['_line_item_types'] ? meta['_line_item_types'] : getSetting( 'lineItemTypes' ),
			};
		}
	);

	if ( 'subtotal' === totalType.substring( 0, 8 ) || 'line_subtotal' === totalType.substring( 0, 13 ) ) {

		/**
		 * Dynamically calculate subtotals for custom line item types.
		 */
		if ( itemTypes.length > 0 ) {
			let totalPlain = parseFloat( getPreviewTotal( totalType, false ) );

			itemTypes.map( ( itemType, i ) => {
				let itemTypeTotalGetter = itemType + '_subtotal';

				if ( totalType.includes( '_after' ) || 'voucher' === itemType ) {
					itemTypeTotalGetter = itemType;
				}

				if ( totalType.includes( '_net' ) ) {
					itemTypeTotalGetter = itemType + '_net';
				}

				let itemTypeTotal = parseFloat( getPreviewTotal( itemTypeTotalGetter, false ) );

				if ( 'voucher' === itemType ) {
					itemTypeTotal = itemTypeTotal * -1;
				}

				totalPlain = totalPlain + itemTypeTotal;
			});

			total = formatPrice( totalPlain );
		}
	}

	if ( 'taxes' === totalType || '_taxes' === totalType.substring( totalType.length - 6 ) || 'nets' === totalType || '_nets' === totalType.substring( totalType.length - 5 ) || 'gross_tax_shares' === totalType || '_gross_tax_shares' === totalType.substring( totalType.length - 17 ) ) {
		innerHeading = innerHeading.replace( '%s', '<span class="document-shortcode sab-tooltip" data-tooltip="' + _x( 'Tax Rate', 'storeabill-core', 'storeabill' ) + '" contenteditable="false" data-shortcode="document_total?data=rate&total_type=' + totalType + '"><span class="editor-placeholder"></span>' + getPreviewTaxRate() + '</span>' ).replace( '%%', '%' );
	} else if ( 'fees' === totalType ) {
		innerHeading = innerHeading.replace( '%s', '<span class="document-shortcode sab-tooltip" data-tooltip="' + _x( 'Fee name', 'storeabill-core', 'storeabill' ) + '" contenteditable="false" data-shortcode="document_total?data=name&total_type=' + totalType + '"><span class="editor-placeholder"></span>' + getPreviewFeeName() + '</span>' ).replace( '%%', '%' );
	} else if ( 'discount' === totalType || 'discount_net' === totalType ) {
		innerHeading = innerHeading.replace( '%s', '<span class="document-shortcode sab-tooltip" data-tooltip="' + _x( 'Discount notice', 'storeabill-core', 'storeabill' ) + '" contenteditable="false" data-shortcode="document_total?data=notice&total_type=' + totalType + '"><span class="editor-placeholder"></span>' + getPreviewDiscountNotice() + '</span>' ).replace( '%%', '%' );
	} else if ( 'voucher' === totalType || 'vouchers' === totalType ) {
		innerHeading = innerHeading.replace( '%s', '<span class="document-shortcode sab-tooltip" data-tooltip="' + _x( 'Coupon Code', 'storeabill-core', 'storeabill' ) + '" contenteditable="false" data-shortcode="document_total?data=code&total_type=' + totalType + '"><span class="editor-placeholder"></span>' + getPreviewVoucherNotice() + '</span> ' + _x( '(Multipurpose)', 'storeabill-core', 'storeabill' ) ).replace( '%%', '%' );
	}

	const classes = classnames( className, 'item-total-row', getBorderClasses( borders ), {
		'has-border-color': borderColor.color,
		[borderColor.class]: borderColor.class
	} );

	const {
		InspectorControlsColorPanel,
		BorderColor,
		TextColor
	} = useColors(
		[
			{ name: 'borderColor', className: 'has-border-color' },
			{ name: 'textColor', property: 'color' },
		],
		[ fontSize.size ]
	);

	const itemTotalClasses = classnames(
		'item-total-row-data',
	);

	return (
		<div className={ classes } style={ {
			borderColor: borderColor.color,
			fontSize: getFontSizeStyle( fontSize ),
		} }>
			<BlockControls>
				<TypeSelect
					label={ _x( 'Change type', 'storeabill-core', 'storeabill' ) }
					value={ totalType }
					onChange={ ( newType ) =>
						setAttributes( { totalType: newType } )
					}
				/>
				<BorderSelect
					label={ _x( 'Adjust border', 'storeabill-core', 'storeabill' ) }
					currentBorders={ borders }
					isMultiSelect={ true }
					borders={ ['top', 'bottom'] }
					onChange={ ( newBorder ) =>
						setAttributes( { borders: newBorder } )
					}
				/>
			</BlockControls>
			<InspectorControls>
				<PanelBody>
					<ToggleControl
						label={ _x( 'Hide if amount equals zero', 'storeabill-core', 'storeabill' ) }
						checked={ hideIfEmpty }
						onChange={ () => setAttributes( { hideIfEmpty: ! hideIfEmpty } ) }
					/>
					<FontSizePicker
						value={ convertFontSizeForPicker( fontSize.size ) }
						onChange={ setFontSize }
					/>
				</PanelBody>
			</InspectorControls>
			{ InspectorControlsColorPanel }

			<TextColor>
				<div className="item-total-row-heading">
					<RichText
						tagName="span"
						placeholder={ _x( 'Insert heading', 'storeabill-core', 'storeabill' ) }
						value={ innerHeading }
						onChange={ ( value ) =>
							setAttributes( { heading: value } )
						}
						allowedFormats={ FORMAT_TYPES }
						className='item-total-heading placeholder-wrapper'
					/>
				</div>
				<div className={ itemTotalClasses }>
					<RichText
						tagName="span"
						value={ replacePlaceholderWithPreview( content, total, '{total}', defaultContent, title ) }
						placeholder={ replacePlaceholderWithPreview( undefined, total, '{total}', defaultContent, title ) }
						className='item-total-content placeholder-wrapper'
						onChange={ ( value ) =>
							setAttributes( { content: replacePreviewWithPlaceholder( value, '{total}' ) } )
						}
						allowedFormats={ FORMAT_TYPES }
					/>
				</div>
			</TextColor>
		</div>
	);
}

export default compose( [
	withFontSizes( 'fontSize' ),
	withColors( 'borderColor', 'backgroundColor', { textColor: 'color' } ),
] )( TotalRowEdit );
