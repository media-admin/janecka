/**
 * External dependencies
 */
import classnames from 'classnames';
import {
	getColorClassName,
	getFontSizeClass,
	RichText,
} from '@wordpress/block-editor';

import { getFontSizeStyle } from "@storeabill/utils";

export default function save( { attributes } ) {

	const {
		content,
		textColor,
		customTextColor,
		fontSize,
		customFontSize,
	} = attributes;

	const textClass = getColorClassName( 'color', textColor );
	const fontSizeClass = getFontSizeClass( fontSize );

	const className = classnames( 'sab-block-item-content', {
		'has-text-color': textColor || customTextColor,
		[ fontSizeClass ]: fontSizeClass,
		[ textClass ]: textClass,
	} );

	const styles = {
		color: textClass ? undefined : customTextColor,
		fontSize: fontSizeClass ? undefined : getFontSizeStyle( customFontSize ),
	};

	return (
		<RichText.Content
			tagName="p"
			className={ className }
			value={ content }
			style={ styles }
		/>
	);
}