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
import { FORMAT_TYPES, getPreview, getSetting } from '@storeabill/settings';
import { getFontSizeStyle, convertFontSizeForPicker, useColors } from '@storeabill/utils';
import { useRef } from "@wordpress/element";

function DocumentTitleEdit( {
	attributes,
	setAttributes,
	fontSize,
	setFontSize,
	className
 } ) {
	const { title, align } = attributes;
	const preview = getPreview();
	const formattedNumber = preview.formatted_number;

	const classes = classnames( 'document-title placeholder-wrapper', className, {
		[ `has-text-align-${ align }` ]: align,
		[ fontSize.class ]: fontSize.class,
	} );

	const ref = useRef();
	const defaultContent = getSetting( 'documentTypeTitle' ) + ' <span class="document-shortcode sab-tooltip" contenteditable="false" data-tooltip="' + _x( 'Formatted document number', 'storeabill-core', 'storeabill' ) + '" data-shortcode="document?data=formatted_number"><span class="editor-placeholder"></span>' + formattedNumber + '</span>';

	const {
		TextColor,
		InspectorControlsColorPanel,
	} = useColors(
		[
			{ name: 'textColor', property: 'color' },
		],
		[ fontSize.size ]
	);

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
				<RichText
					tagName="p"
					value={ title ? title : defaultContent }
					placeholder=""
					className={ classes }
					onChange={ ( value ) =>
						setAttributes( { title: value } )
					}
					allowedFormats={ FORMAT_TYPES }
					style={ {
						fontSize: getFontSizeStyle( fontSize )
					} }
				/>
			</TextColor>
		</>
	);
}

const DocumentTitleEditWrapper = compose( [ withFontSizes( 'fontSize' ) ] )(
	DocumentTitleEdit
);

export default DocumentTitleEditWrapper;