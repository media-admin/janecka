/**
 * WordPress dependencies
 */
import { useState, useMemo, useRef, useCallback } from '@wordpress/element';
import { __, _x } from '@wordpress/i18n';
import { create, insert, isCollapsed, applyFormat, removeFormat, getActiveFormat } from '@wordpress/rich-text';
import { Button, SelectControl, Spinner, TextControl, TreeSelect } from "@wordpress/components";
import { addQueryArgs } from "@wordpress/url";
import apiFetch from "@wordpress/api-fetch";
import { find, reject, isEmpty, debounce, get } from 'lodash';

import { getSetting, getShortcodeTitle } from "@storeabill/settings";
import OptgroupSelect from '@storeabill/components/optgroup-select';
import { getRectangleFromRange } from "@wordpress/dom";
import { URLPopover } from "@wordpress/block-editor";

const ShortcodePopoverAtLink = ( { addingShortcode, ...props } ) => {
	// There is no way to open a text formatter popover when another one is mounted.
	// The first popover will always be dismounted when a click outside happens, so we can store the
	// anchor Rect during the lifetime of the component.
	const anchorRect = useMemo( () => {
		const selection = window.getSelection();
		const range =
			selection.rangeCount > 0 ? selection.getRangeAt( 0 ) : null;
		if ( !range ) {
			return;
		}

		if ( addingShortcode ) {
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

	if ( !anchorRect ) {
		return null;
	}

	return <URLPopover anchorRect={ anchorRect } { ...props } />;
};

const InlineShortcodePicker = ( { name, value, onChange, stopAddingShortcode, removeShortcode, isActive, currentShortcode, availableShortcodes } ) => {

	const [isLoading, setisLoading] = useState();

	const onShortcodeChangeCallback = useCallback( ( shortcodeQuery ) => {
			const newURL = addQueryArgs( '/sab/v1/preview_shortcodes', {
				'query': shortcodeQuery,
				'document_type': getSetting( 'documentType' )
			} );

			setisLoading( true );

			// GET
			apiFetch( { path: newURL } ).then( ( { content, shortcode } ) => {
				setisLoading( false );
				onShortcodeChange( shortcodeQuery, (isEmpty( content ) ? shortcode : content) );
			} ).catch( () => {
					setisLoading( false );
					stopAddingShortcode();
				}
			);
		},
		[onChange, stopAddingShortcode, removeShortcode, isActive]
	);

	const onShortcodeChange = useCallback(
		( shortcode, shortcodeValue ) => {

			if ( shortcode ) {
				const format = {
					type: name,
					attributes: {
						class: 'sab-tooltip',
						contenteditable: 'false',
						tooltip: getShortcodeTitle( shortcode ),
						shortcode: shortcode,
					},
				};

				const toInsert = applyFormat(
					create( {
						html: '<span class="editor-placeholder"></span>' + shortcodeValue,
						__unstableIsEditableTree: false
					} ),
					format,
					0,
					shortcodeValue.length + 1
				);

				if ( !isActive && isCollapsed( value ) ) {
					onChange( insert( value, toInsert ) );
				} else if ( isActive ) {
					value = removeShortcode( value );
					onChange( insert( value, toInsert ) );
				}

				stopAddingShortcode();
			} else {
				onChange( removeShortcode( value ) );

				stopAddingShortcode();
			}
		},
		[onChange, stopAddingShortcode, removeShortcode, isActive]
	);

	return (
		<>
			<div className="sab-shortcode-inner-wrapper">
				<OptgroupSelect
					label={ isLoading ?
						<Spinner /> : _x( 'Select shortcode', 'storeabill-core', 'storeabill' ) }
					value={ currentShortcode ? currentShortcode : '-1' }
					options={ availableShortcodes }
					onChange={ onShortcodeChangeCallback }
					style={ { minWidth: '200px' } }
					defaultOption={ { 'value': '-1', 'label': _x( 'Choose a shortcode', 'storeabill-core', 'storeabill' ) } }
				/>
				{ currentShortcode &&
				<Button isSecondary={ true } onClick={ () => onShortcodeChange( undefined ) }>
					{ _x( 'Remove shortcode', 'storeabill-core', 'storeabill' ) }
				</Button>
				}
			</div>
		</>
	);
};

const InlineShortcodeUI = ( {
	isActive,
	activeAttributes,
	addingShortcode,
	value,
	onChange,
	removeShortcode,
	stopAddingShortcode,
	availableShortcodes,
	name
} ) => {
	return (
		<ShortcodePopoverAtLink
			value={ value }
			isActive={ isActive }
			addingShortcode={ addingShortcode }
			onClose={ stopAddingShortcode }
			className="components-inline-shortcode-popover"
		>
			<InlineShortcodePicker
				name={ name }
				value={ value }
				onChange={ onChange }
				stopAddingShortcode={ stopAddingShortcode }
				removeShortcode={ removeShortcode }
				isActive={ isActive }
				currentShortcode={ activeAttributes.shortcode }
				availableShortcodes={ availableShortcodes }
			/>
		</ShortcodePopoverAtLink>
	);
};

export default InlineShortcodeUI;
