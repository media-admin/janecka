/**
 * External dependencies
 */
import classnames from 'classnames';
import { dropRight, get, map, times } from 'lodash';

/**
 * WordPress dependencies
 */
import {__, _x} from '@wordpress/i18n';
import {PanelBody, RangeControl, ToggleControl} from '@wordpress/components';
import { useRef, useEffect } from '@wordpress/element';
import {
    InspectorControls,
    InnerBlocks,
    BlockControls,
    BlockVerticalAlignmentToolbar,
} from '@wordpress/block-editor';
import { withDispatch, useDispatch, useSelect } from '@wordpress/data';
import { createBlock } from '@wordpress/blocks';
import { getDefaultInnerBlocks } from '@storeabill/settings';

/**
 * Allowed blocks constant is passed to InnerBlocks precisely as specified here.
 * The contents of the array should never change.
 * The array should contain the name of each block that is allowed.
 * In columns block, the only block we allow is 'core/column'.
 *
 * @constant
 * @type {string[]}
 */
const ALLOWED_BLOCKS = [ 'storeabill/item-total-row' ];

function ItemTotalsEditContainer( {
    attributes,
    setAttributes,
    className,
    updateColumns,
    clientId,
} ) {
    const { count } = useSelect(
        ( select ) => {
            return {
                count: select( 'core/block-editor' ).getBlockCount( clientId ),
            };
        },
        [ clientId ]
    );

    const { hasDenseLayout } = attributes;
    const ref = useRef();
    const classes = classnames( className, { 'has-dense-layout': hasDenseLayout } );

    return (
        <>
            <InspectorControls>
                <PanelBody>
                    <ToggleControl
                        label={ _x( 'Enable dense layout', 'storeabill-core', 'storeabill' ) }
                        checked={ hasDenseLayout }
                        onChange={ () => setAttributes( { hasDenseLayout: !hasDenseLayout } ) }
                    />
                </PanelBody>
            </InspectorControls>
            <div className={ classes } ref={ ref }>
                <InnerBlocks
                    allowedBlocks={ ALLOWED_BLOCKS }
                    templateLock={ false }
                    renderAppender={
                        count > 0
                            ? undefined
                            : () => <InnerBlocks.ButtonBlockAppender />
                    }
                />
            </div>
        </>
    );
}

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

function ItemTotalsEdit( props ) {
    const { clientId, name } = props;

    const {
        blockType,
        hasInnerBlocks,
    } = useSelect(
        ( select ) => {
            const {
                getBlockType,
            } = select( 'core/blocks' );

            return {
                blockType: getBlockType( name ),
                hasInnerBlocks:
                    select( 'core/block-editor' ).getBlocks( clientId ).length >
                    0,
            };
        },
        [ clientId, name ]
    );

    const { replaceInnerBlocks } = useDispatch( 'core/block-editor' );

    useEffect( () => {
        if ( ! hasInnerBlocks ) {
            const defaultBlocks = getDefaultInnerBlocks( name );

            replaceInnerBlocks(
                props.clientId,
                createBlocksFromDefault(
                    defaultBlocks
                )
            );
        }
    }, [ hasInnerBlocks, replaceInnerBlocks ]);

    return <ItemTotalsEditContainer { ...props } />;
}

export default ItemTotalsEdit;
