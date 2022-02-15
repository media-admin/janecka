/**
 * WordPress dependencies
 */
import { _x } from '@wordpress/i18n';
import classnames from 'classnames';

import {
	InspectorControls,
	BlockControls,
	AlignmentToolbar,
} from '@wordpress/block-editor';

import { PanelBody, TextControl, Toolbar, DropdownMenu, ToolbarGroup, SelectControl, Icon } from "@wordpress/components";
import { getSetting, getBarcodeTypes, getBarcodeCodeTypes } from '@storeabill/settings';
import { settings, arrowRight, qrCode, barcode } from '@storeabill/icons';
import { useColors } from '@storeabill/utils';

function BarcodeEdit( {
	attributes,
	setAttributes,
	className
 } ) {
	const { align, barcodeType, codeType, size } = attributes;

	const {
		TextColor,
		InspectorControlsColorPanel,
	} = useColors(
		[
			{ name: 'textColor', property: 'color' },
		]
	);

	const classes = classnames( 'sab-barcode', className, {
		[ `has-text-align-${ align }` ]: align,
		[ `size-${ size }` ]: size,
		[ `barcode-type-${ barcodeType }` ]: barcodeType
	} );

	let renderIcon = barcode;

	if ( 'QR' === barcodeType ) {
		renderIcon = qrCode;
	}

	const barcodeTypes = getBarcodeTypes();
	const barcodeCodeTypes = getBarcodeCodeTypes();

	return (
		<>
			<BlockControls>
				<AlignmentToolbar
					value={ align }
					onChange={ ( newAlign ) =>
						setAttributes( { align: newAlign } )
					}
				/>
			</BlockControls>
			<InspectorControls>
				<PanelBody>
					<SelectControl
						label={ _x( 'Type', 'storeabill-core', 'storeabill' ) }
						value={ barcodeType }
						onChange={ ( value ) => setAttributes({ barcodeType: value }) }
						options={ Object.keys( barcodeTypes ).map( ( key ) => {
							return {
								'label': barcodeTypes[ key ],
								'value': key
							}
						} ) }
					/>
					<SelectControl
						label={ _x( 'Data', 'storeabill-core', 'storeabill' ) }
						value={ codeType }
						onChange={ ( value ) => setAttributes({ codeType: value }) }
						options={ Object.keys( barcodeCodeTypes ).map( ( key ) => {
							return {
								'label': barcodeCodeTypes[ key ],
								'value': key
							}
						} ) }
					/>
					<SelectControl
						label={ _x( 'Size', 'storeabill-core', 'storeabill' ) }
						value={ size }
						onChange={ ( value ) => setAttributes({ size: value }) }
						options={ [
							{ label: _x( 'Small', 'storeabill-size', 'storeabill-core' ), value: 'small' },
							{ label: _x( 'Normal', 'storeabill-size', 'storeabill-core' ), value: 'normal' },
							{ label: _x( 'Medium', 'storeabill-size', 'storeabill-core' ), value: 'medium' },
							{ label: _x( 'Big', 'storeabill-size', 'storeabill-core' ), value: 'big' },
						] }
					/>
				</PanelBody>
			</InspectorControls>
			{ InspectorControlsColorPanel }
			<TextColor>
				<div className={ classes }>
					<div className={"sab-barcode-wrap"}>
						<Icon
							icon={ () => (
								renderIcon
							) }
						/>
						<span className={"sab-placeholder-warning"}>{ _x( 'Placeholder', 'storeabill-core', 'storeabill' ) }</span>
					</div>
				</div>
			</TextColor>
		</>
	);
}

export default BarcodeEdit;