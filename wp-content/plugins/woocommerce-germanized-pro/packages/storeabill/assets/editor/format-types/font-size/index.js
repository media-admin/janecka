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
	isCollapsed, remove,
} from '@wordpress/rich-text';
import {
	RichTextToolbarButton,
	RichTextShortcut,
} from '@wordpress/block-editor';

import { fontSize as icon } from '@storeabill/icons';

/**
 * Internal dependencies
 */
import InlineFontSizeUI from './inline';
import './editor.scss';

const name = 'storeabill/font-size';
const title = _x( 'Font size', 'storeabill-core', 'storeabill' );

class FontSizeEdit extends Component {
	constructor() {
		super( ...arguments );

		this.addFontSize 			  = this.addFontSize.bind( this );
		this.stopAddingFontSize = this.stopAddingFontSize.bind( this );
		this.onRemoveFormat 	  = this.onRemoveFormat.bind( this );

		this.state = {
			addingFontSize: false,
		};
	}

	addFontSize() {
		const { value, onChange } = this.props;
		const text = getTextContent( slice( value ) );

		if ( text ) {
			this.setState( { addingFontSize: true } );
		} else {
			this.onRemoveFormat();
		}
	}

	stopAddingFontSize() {
		this.setState( { addingFontSize: false } );
		this.props.onFocus();
	}

	onRemoveFormat() {
		const { value, onChange } = this.props;

		onChange( removeFormat( value, name ) );
	}

	render() {
		const {
			isActive,
			activeAttributes,
			value,
			onChange,
			onFocus,
		} = this.props;

		return (
			<>
				<RichTextToolbarButton
					key={ isActive ? 'text-size' : 'text-size-not-active' }
					className="format-library-text-size-button"
					icon={ icon }
					title={ title }
					onClick={ this.addFontSize }
				/>
				{ this.state.addingFontSize && (
					<InlineFontSizeUI
						addingFontSize={ this.state.addingFontSize }
						stopAddingFontSize={ this.stopAddingFontSize }
						removeFontSize={ this.onRemoveFormat }
						activeAttributes={ activeAttributes }
						value={ value }
						onChange={ onChange }
						name={ name }
						onFocus={ onFocus }
					/>
				) }
			</>
		);
	}
}

export const fontSize = {
	name,
	title,
	tagName: 'span',
	className: 'has-inline-text-size',
	attributes: {
		style: 'style',
		class: 'class',
	},
	edit: FontSizeEdit
};