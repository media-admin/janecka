/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { getColorClassName, InnerBlocks, RichText } from '@wordpress/block-editor';

export default function save( { attributes } ) {
	const { width, heading, align, headingBackgroundColor, headingTextColor, headingFontSize } = attributes;

	const wrapperClasses = classnames( 'wp-block-storeabill-item-table-column', {
		[`is-horizontally-aligned-${ align }`]: align,
	} );

	let style;

	if ( Number.isFinite( width ) ) {
		style = { flexBasis: width + '%' };
	}

	return (
		<div className={ wrapperClasses } style={ style }>
			<RichText.Content
				tagName="span"
				className="item-column-heading-text"
				value={ heading }
			/>
			<InnerBlocks.Content />
		</div>
	);
}
