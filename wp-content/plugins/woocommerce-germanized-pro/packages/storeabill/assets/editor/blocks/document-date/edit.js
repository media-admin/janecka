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

import { Component } from "@wordpress/element";
import { PanelBody, TextControl, Toolbar, DropdownMenu, ToolbarGroup } from "@wordpress/components";
import { compose } from "@wordpress/compose";
import { FORMAT_TYPES, getPreview, getSetting, getShortcodePreview, getDateTypes, getDateTypeTitle } from '@storeabill/settings';
import { replacePreviewWithPlaceholder, replacePlaceholderWithPreview, getFontSizeStyle, convertFontSizeForPicker, useColors } from "@storeabill/utils";
import { settings, arrowRight } from '@storeabill/icons';

import LazyTextControl from '@storeabill/components/lazy-textcontrol';

import { find, reject, isEmpty, debounce, get } from 'lodash';

import { useEffect, useRef, useState } from "@wordpress/element";

function DateTypeSelect( props ) {
	const {
		value,
		onChange,
		types = {},
		label = _x( 'Change date type', 'storeabill-core', 'storeabill' ),
		isCollapsed = true,
	} = props;

	return (
		<ToolbarGroup>
			<DropdownMenu
				label={ label }
				icon={ settings }
				controls={ Object.keys( types ).map( ( key ) => {
					const isActive = value === key;

					const control = {
						title: types[ key ],
						type : key,
						icon: '',
						default: ''
					};

					return {
						...control,
						isActive: isActive,
						icon: isEmpty( control.icon ) ? arrowRight : control.icon,
						role: isCollapsed ? 'menuitemradio' : undefined,
						onClick: () => onChange( key )
					};
				} ) }
			/>
		</ToolbarGroup>
	);
}

function DocumentDateEdit( {
	attributes,
	setAttributes,
	fontSize,
	setFontSize,
	className
 } ) {
	const { content, align, format, dateType } = attributes;
	const [ shortcodeResult, setShortcodeResult ] = useState( '' );

	useEffect( () => {
		getShortcodePreview( 'document?data=' + dateType + '&format=' + format ).then( ( { content, shortcode } ) => {
			setShortcodeResult( ( isEmpty( content ) ? shortcode : content ) );
		} );
	}, [ format, dateType ] );

	const classes = classnames( 'document-date placeholder-wrapper', className, {
		[ `has-text-align-${ align }` ]: align,
		[ fontSize.class ]: fontSize.class,
	} );

	const ref = useRef();
	const currentDateTypeTitle = getDateTypeTitle( dateType );

	const {
		TextColor,
		InspectorControlsColorPanel,
	} = useColors(
		[
			{ name: 'textColor', property: 'color' },
		],
		[ fontSize.size ]
	);

	const onUpdateFormat = function( newFormat ) {
		setAttributes( {
			'format': newFormat,
		} );
	};

	return (
		<>
			<BlockControls>
				<DateTypeSelect
					label={ _x( 'Change date type', 'storeabill-core', 'storeabill' ) }
					value={ dateType }
					types={ getDateTypes() }
					onChange={ ( newType ) =>
						setAttributes( { dateType: newType } )
					}
				/>
				<AlignmentToolbar
					value={ align }
					onChange={ ( newAlign ) =>
						setAttributes( { align: newAlign } )
					}
				/>
			</BlockControls>
			<InspectorControls>
				<PanelBody>
					<LazyTextControl
						label={ _x( 'Date format', 'storeabill-core', 'storeabill' ) }
						value={ format }
						onChange={ onUpdateFormat }
						timeout={ 1500 }
						type="text"
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
			<TextColor>
				<RichText
					tagName="p"
					value={ replacePlaceholderWithPreview( content, shortcodeResult, '{content}', '', currentDateTypeTitle ) }
					placeholder={ replacePlaceholderWithPreview( undefined, shortcodeResult, '{content}', '', currentDateTypeTitle ) }
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
		</>
	);
}

const DocumentDateEditWrapper = compose( [ withFontSizes( 'fontSize' ) ] )(
	DocumentDateEdit
);

export default DocumentDateEditWrapper;