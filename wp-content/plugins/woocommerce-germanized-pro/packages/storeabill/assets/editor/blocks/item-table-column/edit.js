/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import {
	InnerBlocks,
	BlockControls,
	InspectorControls,
	AlignmentToolbar,
	RichText
} from '@wordpress/block-editor';
import { PanelBody, RangeControl } from '@wordpress/components';
import { withDispatch, withSelect } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { _x } from '@wordpress/i18n';
import { useRef } from "@wordpress/element";
import { FORMAT_TYPES, ITEM_TABLE_BLOCK_TYPES } from '@storeabill/settings';

function ColumnEdit( {
  attributes,
  setAttributes,
  className,
  hasChildBlocks,
} ) {

	const { width, align, headingTextColor, headingBackgroundColor, headingFontSize } = attributes;

	const classes = classnames( className, 'block-core-columns', {
		[`is-horizontally-aligned-${ align }`]: align,
	} );

	return (
		<div className={ classes }>
			<BlockControls>
				<AlignmentToolbar
					value={ align }
					onChange={ ( updatedAlignment ) => setAttributes( { align: updatedAlignment } ) }
				/>
			</BlockControls>
			<InspectorControls>
				<PanelBody title={ _x( 'Column settings', 'storeabill-core', 'storeabill' ) }>
					<RangeControl
						label={ _x( 'Percentage width', 'storeabill-core', 'storeabill' ) }
						value={ width || '' }
						onChange={ ( nextWidth ) => {
							setAttributes( { width: nextWidth } );
						} }
						min={ 0 }
						max={ 100 }
						step={ 0.1 }
						required
						allowReset
						placeholder={
							width === undefined ? _x( 'Auto', 'storeabill-core', 'storeabill' ) : undefined
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div className="item-column-heading" style={ {
				backgroundColor: headingBackgroundColor,
			} }>
				<RichText
					tagName="span"
					className="item-column-heading-text"
					placeholder={ _x( 'Write heading…', 'storeabill-core', 'storeabill' ) }
					value={ attributes.heading }
					onChange={ ( value ) =>
						setAttributes( { heading: value } )
					}
					allowedFormats={ [
						'core/bold',
						'core/italic',
						'core/text-color',
						'core/underline',
						'storeabill/document-shortcode',
					] }
					style={ {
						color: headingTextColor,
						fontSize: headingFontSize,
					} }
				/>
			</div>
			<InnerBlocks
				templateLock={ false }
				allowedBlocks={ ITEM_TABLE_BLOCK_TYPES }
				renderAppender={
					hasChildBlocks
						? undefined
						: () => <InnerBlocks.ButtonBlockAppender />
				}
			/>
		</div>
	);
}

export default compose(
	withSelect( ( select, ownProps ) => {
		const { clientId } = ownProps;
		const { getBlockOrder, getBlockRootClientId, getBlockAttributes } = select( 'core/block-editor' );
		const attributes = getBlockAttributes( getBlockRootClientId( clientId ) );

		return {
			hasChildBlocks: getBlockOrder( clientId ).length > 0,
		};
	} ),
	withDispatch( ( dispatch, ownProps, registry ) => {
		return {
			updateAlignment( verticalAlignment ) {
				const { clientId, setAttributes } = ownProps;
				const { updateBlockAttributes } = dispatch(
					'core/block-editor'
				);
				const { getBlockRootClientId } = registry.select(
					'core/block-editor'
				);

				// Update own alignment.
				setAttributes( { verticalAlignment } );

				// Reset Parent Columns Block
				const rootClientId = getBlockRootClientId( clientId );
				updateBlockAttributes( rootClientId, {
					verticalAlignment: null,
				} );
			},
		};
	} )
)( ColumnEdit );
