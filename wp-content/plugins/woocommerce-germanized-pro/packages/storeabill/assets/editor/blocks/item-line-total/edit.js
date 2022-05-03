/**
 * WordPress dependencies
 */
import { _x } from '@wordpress/i18n';
import classnames from 'classnames';

import {
	FontSizePicker,
	InspectorControls,
	withFontSizes,
	BlockControls, RichText,
} from '@wordpress/block-editor';

import { PanelBody, Toolbar } from "@wordpress/components";
import { compose } from "@wordpress/compose";

import { getPreviewItem, getItemTotalKey, DISCOUNT_TOTAL_TYPES, FORMAT_TYPES } from '@storeabill/settings';
import { replacePreviewWithPlaceholder, replacePlaceholderWithPreview, getFontSizeStyle, convertFontSizeForPicker, useColors } from "@storeabill/utils";
import { DiscountTotalTypeSelect } from "@storeabill/components/discount-total-type-select";

import { arrowRight, settings } from '@storeabill/icons';
import { isEmpty } from 'lodash';
import { withSelect } from "@wordpress/data";

function ItemLineTotalEdit( {
	attributes,
	setAttributes,
	fontSize,
	setFontSize,
	className,
	showPricesIncludingTax
} ) {
	const { discountTotalType, content, itemType } = attributes;
	let item = getPreviewItem( itemType );

	const {
		TextColor,
		InspectorControlsColorPanel
	} = useColors(
		[
			{ name: 'textColor', property: 'color' },
		],
		[fontSize.size]
	);

	const classes = classnames( 'sab-block-item-content placeholder-wrapper sab-block-item-line-total', className, {
		[ fontSize.class ]: fontSize.class,
	} );

	const total = item[ getItemTotalKey( 'total', showPricesIncludingTax, discountTotalType ) ];

	return (
		<>
			<BlockControls>
				<DiscountTotalTypeSelect
					currentType={ discountTotalType }
					onChange={ ( value ) => setAttributes( { discountTotalType: value } ) }
				/>
			</BlockControls>
			<InspectorControls>
				<PanelBody title={ _x( 'Typography', 'storeabill-core', 'storeabill' ) }>
					<FontSizePicker
						value={ convertFontSizeForPicker( fontSize.size ) }
						onChange={ setFontSize }
					/>
				</PanelBody>
			</InspectorControls>
			{ InspectorControlsColorPanel }
			<div>
				<TextColor>
					<RichText
						tagName="p"
						value={ replacePlaceholderWithPreview( content, total, '{content}', false, _x( 'Item Line Total', 'storeabill-core', 'storeabill' ) ) }
						placeholder={ replacePlaceholderWithPreview( undefined, total, '{content}', false, _x( 'Item Line Total', 'storeabill-core', 'storeabill' ) ) }
						className={ classes }
						onChange={ ( value ) =>
							setAttributes( { content: replacePreviewWithPlaceholder( value, '{content}' ) } )
						}
						allowedFormats={ FORMAT_TYPES }
						style={ {
							fontSize: getFontSizeStyle( fontSize )
						} }
					/>
				</TextColor>
			</div>
		</>
	);
}

export default compose(
	withFontSizes( 'fontSize' ),
	withSelect( ( select, ownProps ) => {
		const { clientId } = ownProps;
		const { getBlockRootClientId, getBlockAttributes } = select( 'core/block-editor' );

		const columnClientId  = getBlockRootClientId( clientId );
		const tableClientId   = getBlockRootClientId( columnClientId );
		let tableAttributes   = getBlockAttributes( tableClientId );

		if ( tableAttributes ) {
			ownProps.attributes.showPricesIncludingTax = tableAttributes.showPricesIncludingTax;
		} else {
			tableAttributes = ownProps.attributes;
		}

		return {
			showPricesIncludingTax: tableAttributes.showPricesIncludingTax
		};
	} ) )( ItemLineTotalEdit );