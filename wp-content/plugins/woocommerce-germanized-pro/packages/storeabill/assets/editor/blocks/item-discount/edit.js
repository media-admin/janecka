/**
 * WordPress dependencies
 */
import { __, _x } from '@wordpress/i18n';
import classnames from 'classnames';

import {
	FontSizePicker,
	InspectorControls,
	withFontSizes,
	RichText,
	BlockControls,
} from '@wordpress/block-editor';

import {PanelBody, Toolbar, ToolbarButton, DropdownMenu, ToolbarGroup, ToggleControl} from "@wordpress/components";
import { compose } from "@wordpress/compose";

import { arrowRight, settings } from '@storeabill/icons';
import { isEmpty } from 'lodash';

import { getPreviewItem, getItemTotalKey, FORMAT_TYPES } from '@storeabill/settings';
import { replacePreviewWithPlaceholder, replacePlaceholderWithPreview, getFontSizeStyle, convertFontSizeForPicker, useColors } from "@storeabill/utils";
import { withSelect } from "@wordpress/data";

const DISCOUNT_TYPES = [
	{
		type: 'percentage',
		title: _x( 'Percentage (%)', 'storeabill-core', 'storeabill' )
	},
	{
		type: 'absolute',
		title: _x( 'Absolute (€)', 'storeabill-core', 'storeabill' )
	}
];

function ItemDiscountEdit( {
	 attributes,
	 setAttributes,
	 fontSize,
	 setFontSize,
	 className,
	 showPricesIncludingTax
} ) {
	const { content, discountType, hideIfEmpty, itemType } = attributes;
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

	const classes = classnames( 'sab-block-item-content placeholder-wrapper sab-block-item-discount', className, {
		[ fontSize.class ]: fontSize.class,
	} );

	const total = 'percentage' === discountType ? item.discount_percentage_formatted : item[getItemTotalKey( 'discount_total', showPricesIncludingTax )];

	return (
		<>
			<BlockControls>
				<ToolbarGroup>
					<DropdownMenu
						icon={ settings }
						label={ _x( 'Discount type', 'storeabill-core', 'storeabill' ) }
						controls={ DISCOUNT_TYPES.map( ( control ) => {
							const { type } = control;
							const isActive = discountType === type;

							return {
								...control,
								isActive,
								icon: isEmpty( control.icon ) ? arrowRight : control.icon,
								role: 'menuitemradio',
								onClick: () => setAttributes( { discountType: type } )
							};
						} ) }
					/>
				</ToolbarGroup>
			</BlockControls>
			<InspectorControls>
				<PanelBody>
					<ToggleControl
						label={ _x( 'Hide if amount equals zero', 'storeabill-core', 'storeabill' ) }
						checked={ hideIfEmpty }
						onChange={ () => setAttributes( { hideIfEmpty: ! hideIfEmpty } ) }
					/>
				</PanelBody>
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
						value={ replacePlaceholderWithPreview( content, total, '{content}', false, _x( 'Item Discount', 'storeabill-core', 'storeabill' ) ) }
						placeholder={ replacePlaceholderWithPreview( undefined, total, '{content}', false, _x( 'Item Discount', 'storeabill-core', 'storeabill' ) ) }
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
	} ) )( ItemDiscountEdit );