/**
 * WordPress dependencies
 */
import { _x } from '@wordpress/i18n';
import classnames from 'classnames';

import {
	FontSizePicker,
	InspectorControls,
	withFontSizes,
	RichText,
	BlockControls,
	AlignmentToolbar,
} from '@wordpress/block-editor';

import { PanelBody } from "@wordpress/components";
import { compose } from "@wordpress/compose";
import { getSetting } from '@storeabill/settings';
import { getFontSizeStyle, convertFontSizeForPicker, useColors } from '@storeabill/utils';
import { useRef, useState, useEffect } from "@wordpress/element";

import ServerSideRender from '@wordpress/server-side-render';

function DynamicContentEdit( {
	attributes,
	setAttributes,
	fontSize,
	setFontSize
 } ) {
	const { align } = attributes;

	const classes = classnames( 'dynamic-content', {
		[ `has-text-align-${ align }` ]: align,
		[ fontSize.class ]: fontSize.class,
	} );

	const ref 		= useRef();
	const blockName = 'storeabill/' + attributes.blockName;

	const [ serverSideRenderResult, setServerSideRenderResult ] = useState( '' );

	const {
		TextColor,
		InspectorControlsColorPanel,
	} = useColors(
		[
			{ name: 'textColor', property: 'color' },
		],
		[ fontSize.size ]
	);

	useEffect( () => setServerSideRenderResult(
		<ServerSideRender
			block={ blockName }
			attributes={ attributes }
		/>
	), [] );

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
				<PanelBody title={ _x( 'Typography', 'storeabill-core', 'storeabill' ) }>
					<FontSizePicker
						value={ convertFontSizeForPicker( fontSize.size ) }
						onChange={ setFontSize }
					/>
				</PanelBody>
			</InspectorControls>
			{ InspectorControlsColorPanel }
			<TextColor>
				<div className={ classes } style={ {
					fontSize: getFontSizeStyle( fontSize )
				} }>
					{ serverSideRenderResult }
				</div>
			</TextColor>
		</>
	);
}

const DynamicContentEditWrapper = compose( [ withFontSizes( 'fontSize' ) ] )(
	DynamicContentEdit
);

export default DynamicContentEditWrapper;