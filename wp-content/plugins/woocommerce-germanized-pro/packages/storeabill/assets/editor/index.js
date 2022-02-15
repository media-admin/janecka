/**
 * External dependencies
 */
import { getCategories, setCategories, createBlock, registerBlockType } from '@wordpress/blocks';
import { select, dispatch } from '@wordpress/data';
import domReady from '@wordpress/dom-ready';
import { _x } from '@wordpress/i18n';
import { getDocumentStylesBlock, isDocumentTemplate, getSetting, getShortcodePreview } from '@storeabill/settings';
import { replacePreviewWithPlaceholder } from "@storeabill/utils";
import { addFilter, addAction } from '@wordpress/hooks';
import { subscribe } from '@wordpress/data';

import { isEmpty, includes } from 'lodash';

/**
 * Internal dependencies
 */
import './editor.scss';

function enforceBlockOrder( blocks ) {
    let length = blocks.length;

    /**
     * Move footer to the last index.
     */
    blocks.map( function( block ) {
        if ( 'storeabill/footer' === block.name ) {
            const {
                moveBlockToPosition
            } = dispatch( 'core/editor' );

            moveBlockToPosition( block.clientId, '', '', length );
        }
    } );
}

/**
 * Force existence of our global DocumentStyles block which
 * dynamically updates editor wrapper styles on meta updates.
 */
domReady( () => {
    /**
     * Use ugly timeout hack until wp.data is set up.
     * @see https://github.com/WordPress/gutenberg/issues/28032
     */
    setTimeout(() => {
        if ( ! isDocumentTemplate() ) {
            return;
        }

        const stylesBlock = getDocumentStylesBlock();

        if ( ! stylesBlock ) {
            let insertedBlock = createBlock( 'storeabill/document-styles', {} );

            dispatch( 'core/block-editor' ).insertBlock( insertedBlock, null, '', false );

            // Hide the styles block
            jQuery( 'body' ).find( '.sab-block-hider' ).remove();
            jQuery( 'body' ).append( '<style type="text/css" class="sab-block-hider">tr#block-navigation-block-' + insertedBlock.clientId + ' { display: none !important }</style>' );
        } else {
            jQuery( 'body' ).find( '.sab-block-hider' ).remove();
            jQuery( 'body' ).append( '<style type="text/css" class="sab-block-hider">tr#block-navigation-block-' + stylesBlock.clientId + ' { display: none !important }</style>' );
        }

        const { getBlocks } = select( 'core/block-editor' );
        let initialBlocks = getBlocks();

        enforceBlockOrder( initialBlocks );

        /**
         * Subscribe to state changes. In case the block length changes (e.g. new blocks inserted)
         * make sure that the footer is always the last block available to prevent overlapping.
         */
        const unsubscribeOrderEnforcement = subscribe( function() {
            let currentBlocks = getBlocks();

            if ( currentBlocks.length !== initialBlocks.length ) {
                initialBlocks = currentBlocks;

                enforceBlockOrder( currentBlocks );
            }
        });

        /**
         * Find all shortcodes that need refresh and call API for a new result.
         */
        const $shortcodes = jQuery( '.document-shortcode-needs-refresh' );

        if ( $shortcodes.length > 0 ) {
            $shortcodes.each( function() {
                const $shortcode = jQuery( this );
                $shortcode.hide();

                const shortcodeQuery = $shortcode.data( 'shortcode' );

                getShortcodePreview( $shortcode.data( 'shortcode' ) ).then( ( { content, shortcode } ) => {
                    const clientId        = $shortcode.parents( '.wp-block' ).data( 'block' );
                    let   blockAttributes = select( 'core/block-editor' ).getBlockAttributes( clientId );

                    if ( blockAttributes.length > 0 ) {
                        for ( var key of Object.keys( blockAttributes ) ) {
                            let val = blockAttributes[ key ];

                            if ( ( typeof val === 'string' || val instanceof String ) && val.includes( shortcodeQuery ) ) {
                                val = replacePreviewWithPlaceholder( val, ( ! isEmpty( content ) ? content : shortcode ), shortcodeQuery, true );

                                /**
                                 * Update the attribute to make sure further
                                 * shortcode adjustments use the updated content.
                                 */
                                blockAttributes[ key ] = val;

                                dispatch( 'core/block-editor' ).updateBlockAttributes( clientId, {
                                    key: val
                                } );
                            }
                        }
                    }

                    $shortcode.show();
                } );
            } );
        }
    }, 0 );
});

const excludedFromFirstPageFilter = [
    'storeabill/header',
    'storeabill/footer',
    'storeabill/document-styles'
];

/**
 * In case a first page template is edited only header and footer should be addable to the root.
 * All other blocks should only be added inside header or footer.
 *
 * @param settings
 * @param name
 * @returns {*}
 */
function firstPageBlockTypeFilter( settings, name ) {
    if ( getSetting( 'isFirstPage' ) ) {
        if ( excludedFromFirstPageFilter.indexOf( name ) === -1 ) {
            if ( ! settings.hasOwnProperty( 'parent' ) || ( settings.parent.toString() === [ 'core/post-content' ].toString() ) ) {
                return Object.assign({}, settings, {
                    parent: [ 'storeabill/header', 'storeabill/footer', 'core/column' ]
                });
            }
        }
    }

    return settings;
}

addFilter(
  'blocks.registerBlockType',
  'storeabill/show-hide-blocks',
  firstPageBlockTypeFilter
);

setCategories( [
    ...getCategories().filter( ( { slug } ) => slug !== 'storeabill' ), {
        slug: 'storeabill',
        title: _x( 'StoreaBill', 'storeabill-core', 'storeabill' )
    },
] );
