/**
 * WordPress dependencies
 */
import { __, _x } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import {
	getTextContent,
	applyFormat,
	removeFormat,
	slice,
	isCollapsed,
	remove,
} from '@wordpress/rich-text';
import {
	RichTextToolbarButton,
	RichTextShortcut,
} from '@wordpress/block-editor';

import { compose, ifCondition } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';
import { shortcode as icon } from '@storeabill/icons';
import { getAvailableShortcodeTree, blockHasParent } from '@storeabill/settings';

import { find, reject, isEmpty, includes, concat } from 'lodash';

/**
 * Internal dependencies
 */
import InlineShortcodeUI from './inline';
import './editor.scss';

const name = 'storeabill/document-shortcode';
const title = _x( 'Shortcode', 'storeabill-core', 'storeabill' );

function filterFormats( formats, index, formatType ) {
	const newFormats = formats[index].filter(
		( { type } ) => type !== formatType
	);

	if ( newFormats.length ) {
		formats[index] = newFormats;
	} else {
		delete formats[index];
	}
}

class ShortcodeEdit extends Component {
	constructor() {
		super( ...arguments );

		this.addShortcode = this.addShortcode.bind( this );
		this.stopAddingShortcode = this.stopAddingShortcode.bind( this );
		this.onRemoveFormat = this.onRemoveFormat.bind( this );
		this.removeShortcode = this.removeShortcode.bind( this );

		this.state = {
			addingShortcode: false,
		};
	}

	addShortcode() {
		const { value, onChange, isActive } = this.props;
		const text = getTextContent( slice( value ) );

		this.setState( { addingShortcode: true } );
	}

	stopAddingShortcode() {
		this.setState( { addingShortcode: false } );
		this.props.onFocus();
	}

	removeShortcode( value ) {
		let startIndex = value.start;
		let endIndex = value.end;
		const formats = value.formats;
		const newFormats = formats.slice();
		const formatName = name;

		// Calculate start and end index to be removed (just like removeFormat does)
		if ( startIndex === endIndex ) {
			let format = find( newFormats[startIndex], {
				type: formatName
			} );

			if ( format ) {
				while ( find( newFormats[startIndex], format ) ) {
					filterFormats( newFormats, startIndex, formatName );
					startIndex --;
				}

				endIndex ++;

				while ( find( newFormats[endIndex], format ) ) {
					filterFormats( newFormats, endIndex, formatName );
					endIndex ++;
				}
			}
		} else {
			for ( let i = startIndex; i < endIndex; i ++ ) {
				if ( newFormats[i] ) {
					filterFormats( newFormats, i, formatName );
				}
			}
		}

		value = removeFormat( value, formatName );
		value = remove( value, startIndex, endIndex );

		return value;
	}

	onRemoveFormat() {
		const { value, onChange } = this.props;

		let newValue = this.removeShortcode( value );

		onChange( newValue );
	}

	render() {

		const {
			isActive,
			activeAttributes,
			value,
			onChange,
			onFocus,
			availableShortcodes
		} = this.props;

		if ( isEmpty( availableShortcodes ) ) {
			return null;
		}

		return (
			<>
				<RichTextToolbarButton
					key={ isActive ? 'shortcode' : 'shortcode-not-active' }
					className="format-library-shortcode-button"
					icon={ icon }
					title={ title }
					isActive={ isActive }
					onClick={ this.addShortcode }
				/>
				{ this.state.addingShortcode && (
					<InlineShortcodeUI
						addingShortcode={ this.state.addingShortcode }
						stopAddingShortcode={ this.stopAddingShortcode }
						removeShortcode={ this.removeShortcode }
						isActive={ isActive }
						activeAttributes={ activeAttributes }
						availableShortcodes={ availableShortcodes }
						value={ value }
						onChange={ onChange }
						name={ name }
					/>
				) }
			</>
		);
	}
}

const ShortcodeEditWrapper = compose(
	withSelect( function ( select ) {
		const selectedBlock   = select( 'core/block-editor' ).getSelectedBlock();
		let blockType 		  = 'document';
		const blockName	      = selectedBlock ? selectedBlock.name : '';
		let hasHeaderOrFooter = false;

		if ( includes( blockName, 'item-' ) ) {
			blockType = 'document_item';
		}

		if ( includes( blockName, 'item-total' ) ) {
			blockType = 'document_item_total';
		}

		if ( selectedBlock ) {
			hasHeaderOrFooter = blockHasParent( selectedBlock.clientId, [ 'storeabill/footer', 'storeabill/header' ] );
		}

		const availableShortcodes = getAvailableShortcodeTree( blockType, blockName, hasHeaderOrFooter );

		return {
			availableShortcodes: availableShortcodes
		}
	} ),
)( ShortcodeEdit );

export const shortcode = {
	name,
	title,
	tagName: 'span',
	className: 'document-shortcode',
	attributes: {
		shortcode: 'data-shortcode',
		tooltip: 'data-tooltip'
	},
	edit: ShortcodeEditWrapper
};