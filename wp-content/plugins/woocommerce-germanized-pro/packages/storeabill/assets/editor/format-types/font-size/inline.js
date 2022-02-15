/**
 * External dependencies
 */
import { uniqueId } from 'lodash';

/**
 * WordPress dependencies
 */
import { useState, useMemo, useRef, useCallback } from '@wordpress/element';
import { __, _x } from '@wordpress/i18n';
import { Popover, withSpokenMessages } from '@wordpress/components';
import { create, insert, isCollapsed, applyFormat, removeFormat, getActiveFormat } from '@wordpress/rich-text';
import { Button, SelectControl, Spinner, FontSizePicker, TextControl } from "@wordpress/components";
import { addQueryArgs } from "@wordpress/url";
import apiFetch from "@wordpress/api-fetch";
import { find, reject, isEmpty, debounce, get } from 'lodash';

import { getSetting } from "@storeabill/settings";
import { getRectangleFromRange } from "@wordpress/dom";
import { URLPopover } from "@wordpress/block-editor";
import { useSelect } from "@wordpress/data";

export function getActiveFontSize( formatName, formatValue ) {
	const activeFontSizeFormat = getActiveFormat( formatValue, formatName );

	if ( ! activeFontSizeFormat ) {
		return;
	}

	const styleFontSize = activeFontSizeFormat.attributes.style;

	if ( styleFontSize ) {
		return styleFontSize.replace( /\D/g, '' );
	}
}

const FontSizePopoverAtLink = ( { addingFontSize, ...props } ) => {
	// There is no way to open a text formatter popover when another one is mounted.
	// The first popover will always be dismounted when a click outside happens, so we can store the
	// anchor Rect during the lifetime of the component.
	const anchorRect = useMemo( () => {
		const selection = window.getSelection();
		const range =
			selection.rangeCount > 0 ? selection.getRangeAt( 0 ) : null;
		if ( ! range ) {
			return;
		}

		if ( addingFontSize ) {
			return getRectangleFromRange( range );
		}

		let element = range.startContainer;

		// If the caret is right before the element, select the next element.
		element = element.nextElementSibling || element;

		while ( element.nodeType !== window.Node.ELEMENT_NODE ) {
			element = element.parentNode;
		}

		const closest = element.closest( 'span' );
		if ( closest ) {
			return closest.getBoundingClientRect();
		}
	}, [] );

	if ( ! anchorRect ) {
		return null;
	}

	return <URLPopover anchorRect={ anchorRect } { ...props } />;
};

const InlineFontSizePicker = ( { name, value, onChange, stopAddingFontSize } ) => {
	const fontSizes = useSelect( ( select ) => {
		const { getSettings } = select( 'core/block-editor' );
		return get( getSettings(), [ 'fontSizes' ], [] );
	} );

	const onFontSizeChange = useCallback(
		( fontSize ) => {
			if ( fontSize ) {
				onChange(
					applyFormat( value, {
						type: name,
						attributes: {
							style: `font-size:${ fontSize }px`,
						}
					} )
				);
			} else {
				onChange( removeFormat( value, name ) );
				stopAddingFontSize();
			}
		},
		[ fontSizes, onChange, stopAddingFontSize ]
	);

	const activeFontSize = useMemo( () => getActiveFontSize( name, value, fontSizes ), [
		name,
		value,
		fontSizes,
	] );

	return (
		<>
			<div className="sab-font-size-wrapper">
				<div className="individual-font-size">
					<TextControl
						value={ activeFontSize ? activeFontSize : '' }
						type="text"
						onChange={ onFontSizeChange }
						label={ _x( 'Individual size', 'storeabill-core', 'storeabill' ) }
					/>
					{activeFontSize &&
						<Button
							isSecondary
							onClick={ () => onFontSizeChange( false ) }
						>
							{_x('Reset', 'storeabill-core', 'storeabill')}
						</Button>
					}
				</div>
			</div>
		</>
	);
};

const InlineFontSizeUI = ( {
	isActive,
	activeAttributes,
	addingFontSize,
	value,
	onChange,
	onFocus,
	removeFontSize,
	stopAddingFontSize,
	name
} ) => {
	return (
		<FontSizePopoverAtLink
			value={ value }
			addingFontSize={ addingFontSize }
			onClose={ stopAddingFontSize }
			className="components-inline-font-size-popover"
		>
			<InlineFontSizePicker name={ name } value={ value } onChange={ onChange } stopAddingFontSize={ stopAddingFontSize } />
		</FontSizePopoverAtLink>
	);
};

export default InlineFontSizeUI;
