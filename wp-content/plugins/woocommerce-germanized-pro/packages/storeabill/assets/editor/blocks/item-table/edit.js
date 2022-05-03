/**
 * External dependencies
 */
import classnames from 'classnames';
import { dropRight, get, map, times, includes, cloneDeep } from 'lodash';

/**
 * WordPress dependencies
 */
import { __, _x } from '@wordpress/i18n';
import { PanelBody, RangeControl, TextControl, ToggleControl, Toolbar, Disabled } from '@wordpress/components';
import { useRef, useEffect, useCallback } from '@wordpress/element';
import { compose } from "@wordpress/compose";
import {
	InspectorControls,
	InnerBlocks,
	BlockControls,
	BlockVerticalAlignmentToolbar,
	withColors,
	BlockPreview,
	BlockList,
	BlockEditorProvider,
	getColorClassName,
	ContrastChecker,
	__experimentalPanelColorGradientSettings as PanelColorGradientSettings,
	withFontSizes,
	FontSizePicker
} from '@wordpress/block-editor';
import { withDispatch, useDispatch, useSelect } from '@wordpress/data';
import { createBlock } from '@wordpress/blocks';

import { documentTypeSupports, getDefaultInnerBlocks, getSetting } from '@storeabill/settings';
import { useColors } from '@storeabill/utils';
import { BorderSelect, getBorderClasses } from '@storeabill/components/border-select';

/**
 * Internal dependencies
 */
import {
	hasExplicitColumnWidths,
	getMappedColumnWidths,
	getRedistributedColumnWidths,
	toWidthPrecision,
} from './utils';

/**
 * Allowed blocks constant is passed to InnerBlocks precisely as specified here.
 * The contents of the array should never change.
 * The array should contain the name of each block that is allowed.
 * In columns block, the only block we allow is 'core/column'.
 *
 * @constant
 * @type {string[]}
 */
const ALLOWED_BLOCKS = ['storeabill/item-table-column'];

function ColumnsEditContainer( {
   attributes,
   className,
   updateColumns,
   borderColor,
   headingBackgroundColor,
   headingTextColor,
   updateHeadingBackgroundColor,
   updateHeadingTextColor,
   updateChildBlocks,
   setBorderColor,
   clientId,
   setAttributes,
   headingFontSize,
   updateHeadingFontSize,
} ) {

	const { count } = useSelect(
		( select ) => {
			return {
				count: select( 'core/block-editor' ).getBlockCount( clientId ),
			};
		},
		[clientId]
	);

	const { childBlocks } = useSelect(
		( select ) => {
			return {
				childBlocks: select( 'core/block-editor' ).getBlocks( clientId ),
			};
		},
		[clientId]
	);

	const { itemTypes } = useSelect(
		( select ) => {
			const { getEditedPostAttribute } = select( 'core/editor' );
			const meta  = getEditedPostAttribute( 'meta' );

			return {
				itemTypes: meta['_line_item_types'] ? meta['_line_item_types'] : getSetting( 'lineItemTypes' ),
			};
		}
	);

	const ref = useRef();
	const {
		InspectorControlsColorPanel,
		BorderColor,
	} = useColors(
		[
			{ name: 'borderColor', className: 'has-border-color' },
		],
		{
			colorDetector: { targetRef: ref },
		}
	);

	/**
	 * Older Gutenberg versions might add a surrounding wrapper div element in BlockList
	 * which prevents styles being applied. Remove it.
	 */
	const removeWrapperDiv = useCallback((node) => {
		if ( node !== null ) {
			let element = node.querySelector('* > div:not([class])');

			if ( element ) {
				element.replaceWith(...element.childNodes);
			}
		}
	}, []);

	const { borders, showPricesIncludingTax, hasDenseLayout } = attributes;
	const borderClasses = getBorderClasses( borders );
	let rowCount = 1;

	const classes = classnames( className, {
		'has-border-color': borderColor.color,
		[borderColor.class]: borderColor.class
	}, borderClasses, { 'has-dense-layout': hasDenseLayout } );

	return (
		<>
			<BlockControls>
				<BorderSelect
					currentBorders={ borders }
					onChange={ ( newBorders ) =>
						setAttributes( { borders: newBorders } )
					}
					borders={ ['outer', 'inner', 'horizontal'] }
				/>
			</BlockControls>
			<InspectorControls>
				<PanelBody>
					<RangeControl
						label={ _x( 'Columns', 'storeabill-core', 'storeabill' ) }
						value={ count }
						onChange={ ( value ) => updateColumns( count, value ) }
						min={ 2 }
						max={ 8 }
					/>
					{ documentTypeSupports( 'item_totals' ) &&
					<ToggleControl
						label={ _x( 'Show item prices including tax', 'storeabill-core', 'storeabill' ) }
						checked={ showPricesIncludingTax }
						onChange={ () => setAttributes( { showPricesIncludingTax: !showPricesIncludingTax } ) }
					/>
					}
					<ToggleControl
						label={ _x( 'Enable dense layout', 'storeabill-core', 'storeabill' ) }
						checked={ hasDenseLayout }
						onChange={ () => setAttributes( { hasDenseLayout: !hasDenseLayout } ) }
					/>
				</PanelBody>
				<PanelBody title={ _x( 'Heading Typography', 'storeabill-core', 'storeabill' ) }>
					<FontSizePicker
						value={ headingFontSize.size }
						onChange={ updateHeadingFontSize }
					/>
				</PanelBody>
				<PanelColorGradientSettings
					title={ _x( 'Colors', 'storeabill-core', 'storeabill' ) }
					settings={ [
						{
							colorValue: headingTextColor.color,
							onColorChange: updateHeadingTextColor,
							label: _x( 'Heading Text Color', 'storeabill-core', 'storeabill' ),
						},
						{
							colorValue: headingBackgroundColor.color,
							onColorChange: updateHeadingBackgroundColor,
							label: _x( 'Heading Background', 'storeabill-core', 'storeabill' ),
						},
						{
							colorValue: borderColor.color,
							onColorChange: setBorderColor,
							label: _x( 'Border Color', 'storeabill-core', 'storeabill' ),
						},
					] }
				>
				</PanelColorGradientSettings>
			</InspectorControls>

			<div className={ classnames( classes, ['sab-item-table-row-' + rowCount, 'sab-item-table-row-' + ( rowCount % 2 === 0 ? 'even' : 'odd' )] ) } ref={ ref } style={ {
				borderColor: borderColor.color,
			} }>
				<InnerBlocks
					templateLock={ 'insert' }
					allowedBlocks={ ALLOWED_BLOCKS }
					orientation={ 'horizontal' }
					horizontal={ true }
					useInlineMovers="before|after"
					isHorizontalLayouts
				/>
			</div>

			<Disabled>
				<div className="sab-item-table-placeholder-wrapper">
					<div className="sab-item-table-placeholders">
						{
							Object.entries( getSetting( 'availableLineItemTypes' ) ).map( ( data ) => {
								if ( includes( itemTypes, data[0] ) ) {
									let newBlocks = cloneDeep( childBlocks );
									rowCount = rowCount + 1;

									newBlocks.map( ( block, i ) => {
										block.attributes.itemType = data[0];

										block.innerBlocks.map( ( innerBlock, i ) => {
											innerBlock.attributes.itemType = data[0];

											if ( 'storeabill/item-position' === innerBlock.name ) {
												innerBlock.attributes.currentPosition = rowCount;
											}
										});
									});

									const blockClasses = classnames( classes, ['sab-item-table-placeholder-' + data[0]], 'sab-item-table-row-' + rowCount, 'sab-item-table-row-' + ( rowCount % 2 === 0 ? 'even' : 'odd' ) );

									return (
										<BlockEditorProvider value={ newBlocks } key={ data[0] }>
											<div className={ blockClasses } style={ {
												borderColor: borderColor.color,
											} }>
												<div className="block-editor-inner-blocks" ref={ removeWrapperDiv }>
													<BlockList className={ "block-editor-block-list__layout" } renderAppender={ false } settings={ { 'isOutlineMode': false, 'isFocusMode': false, 'isNavigationMode': false } } />
												</div>
											</div>
										</BlockEditorProvider>
									)
								}
							} )
						}
					</div>
				</div>
			</Disabled>
		</>
	);
}

const ColumnsEditContainerWrapper = withDispatch(
	( dispatch, ownProps, registry ) => ({

		/**
		 * Updates the column count, including necessary revisions to child Column
		 * blocks to grant required or redistribute available space.
		 *
		 * @param {number} previousColumns Previous column count.
		 * @param {number} newColumns      New column count.
		 */
		updateColumns( previousColumns, newColumns ) {
			const { clientId } = ownProps;
			const { replaceInnerBlocks } = dispatch( 'core/block-editor' );
			const { getBlocks } = registry.select( 'core/block-editor' );

			let innerBlocks = getBlocks( clientId );
			const hasExplicitWidths = hasExplicitColumnWidths( innerBlocks );

			// Redistribute available width for existing inner blocks.
			const isAddingColumn = newColumns > previousColumns;

			if ( isAddingColumn && hasExplicitWidths ) {
				// If adding a new column, assign width to the new column equal to
				// as if it were `1 / columns` of the total available space.
				const newColumnWidth = toWidthPrecision( 100 / newColumns );

				// Redistribute in consideration of pending block insertion as
				// constraining the available working width.
				const widths = getRedistributedColumnWidths(
					innerBlocks,
					100 - newColumnWidth
				);

				innerBlocks = [
					...getMappedColumnWidths( innerBlocks, widths ),
					...times( newColumns - previousColumns, () => {
						return createBlock( 'storeabill/item-table-column', {} );
					} ),
				];

			} else if ( isAddingColumn ) {
				innerBlocks = [
					...innerBlocks,
					...times( newColumns - previousColumns, () => {
						return createBlock( 'storeabill/item-table-column' );
					} ),
				];
			} else {
				// The removed column will be the last of the inner blocks.
				innerBlocks = dropRight(
					innerBlocks,
					previousColumns - newColumns
				);

				if ( hasExplicitWidths ) {
					// Redistribute as if block is already removed.
					const widths = getRedistributedColumnWidths(
						innerBlocks,
						100
					);

					innerBlocks = getMappedColumnWidths( innerBlocks, widths );
				}
			}

			replaceInnerBlocks( clientId, innerBlocks, false );

			this.updateChildBlocks();
		},

		updateChildBlocks( attributes ) {

			const defaultAttributes = {
				headingBackgroundColor: ownProps.headingBackgroundColor ? ownProps.headingBackgroundColor.color : undefined,
				headingTextColor: ownProps.headingTextColor ? ownProps.headingTextColor.color : undefined,
				headingFontSize: ownProps.headingFontSize ? ownProps.headingFontSize.size : undefined,
				showPricesIncludingTax: ownProps.showPricesIncludingTax
			};

			attributes = attributes || defaultAttributes;

			const { clientId } = ownProps;
			const { updateBlockAttributes } = dispatch( 'core/block-editor' );
			const { getBlockOrder } = registry.select( 'core/block-editor' );

			// Update all child Column Blocks to match
			const innerBlockClientIds = getBlockOrder( clientId );

			innerBlockClientIds.forEach( ( innerBlockClientId ) => {
				updateBlockAttributes( innerBlockClientId, attributes );
			} );
		},

		updateHeadingFontSize( fontSize ) {
			const { setHeadingFontSize } = ownProps;

			let attributes = {
				headingFontSize: fontSize,
			};

			// Update own prop
			setHeadingFontSize( fontSize );

			this.updateChildBlocks( attributes );
		},

		updateHeadingBackgroundColor( backgroundColor ) {
			const { setHeadingBackgroundColor } = ownProps;

			let attributes = {
				headingBackgroundColor: backgroundColor,
			};

			// Update own prop
			setHeadingBackgroundColor( backgroundColor );

			this.updateChildBlocks( attributes );
		},

		updateHeadingTextColor( textColor ) {
			const { setHeadingTextColor } = ownProps;

			let attributes = {
				headingTextColor: textColor,
			};

			// Update own prop
			setHeadingTextColor( textColor );

			this.updateChildBlocks( attributes );
		}
	})
)( ColumnsEditContainer );

const createBlocksFromDefault = ( innerBlocksTemplate ) => {
	return map(
		innerBlocksTemplate,
		( { name, attributes, innerBlocks = [] } ) =>
			createBlock(
				name,
				attributes,
				createBlocksFromDefault( innerBlocks )
			)
	);
};

function ColumnsEdit( props ) {
	const { clientId, name, setAttributes, headingBackgroundColor, headingTextColor, setHeadingBackgroundColor, setHeadingTextColor, headingFontSize, setHeadingFontSize } = props;

	const defaultBlocks = getDefaultInnerBlocks( name );
	const defaultColumnAttributes = {
		headingBackgroundColor: '#e5e5e5',
		headingTextColor: 'black'
	};

	defaultBlocks.map( ( innerBlock ) => {
		innerBlock.attributes = {
			...innerBlock.attributes,
			...defaultColumnAttributes
		};

		return innerBlock;
	} );

	const {
		blockType,
		hasInnerBlocks,
		childBlocks,
	} = useSelect(
		( select ) => {
			const {
				getBlockType,
			} = select( 'core/blocks' );

			const childBlocks = select( 'core/block-editor' ).getBlocks( clientId );

			return {
				blockType: getBlockType( name ),
				childBlocks: childBlocks,
				hasInnerBlocks: childBlocks.length > 0,
			};
		},
		[clientId, name]
	);

	const { replaceInnerBlocks } = useDispatch( 'core/block-editor' );

	useEffect( () => {
		if ( ! hasInnerBlocks ) {
			replaceInnerBlocks(
				props.clientId,
				createBlocksFromDefault(
					defaultBlocks
				)
			);

			setAttributes( {
				borders: [ 'horizontal' ],
				customBorderColor: '#a9a9a9',
				customHeadingBackgroundColor: '#e5e5e5',
				headingTextColor: 'black'
			} );
		}
	}, [ hasInnerBlocks, replaceInnerBlocks ]);

	return <ColumnsEditContainerWrapper { ...props } />;
}

export default compose( [
	withColors( 'borderColor', { headingTextColor: 'color' }, { headingBackgroundColor: 'backgroundColor' } ),
	withFontSizes( 'headingFontSize' )
] )( ColumnsEdit );