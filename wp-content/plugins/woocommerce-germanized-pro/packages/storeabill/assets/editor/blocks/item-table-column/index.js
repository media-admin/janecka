import { createBlock, registerBlockType } from '@wordpress/blocks';
import { __, _x } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { createHigherOrderComponent } from '@wordpress/compose';
import { times, assign, cloneDeep, map } from 'lodash';
import { addFilter } from '@wordpress/hooks';
import { withDispatch, useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import edit from './edit';
import save from './save';

let hasRegistered = false;

/**
 * Lets hook into the blockType register function and
 * check for the core/table block to clone and create a new block from it.
 * Using domReady is too late as blocks need to be registered before.
 *
 * @param settings
 * @param name
 * @returns {*}
 */
function blockTypeFilter( settings, name ) {
    if ( name !== 'core/column' || hasRegistered ) {
        return settings;
    }

    hasRegistered = true;

    const itemTableColumn = cloneDeep( settings );

    itemTableColumn.apiVersion = 1;
    itemTableColumn.category   = 'storeabill';
    itemTableColumn.name       = 'storeabill/item-table-column';
    itemTableColumn.parent     = [ 'storeabill/item-table' ];
    itemTableColumn.title      = _x( 'Column', 'storeabill-core', 'storeabill' );
    itemTableColumn.edit       = edit;
    itemTableColumn.save       = save;

    itemTableColumn.supports.lightBlockWrapper = false;

    itemTableColumn.attributes = {
        "align": {
            "type": "string",
            "default": "left"
        },
        "width": {
            "type": "number",
        },
        "isDisabled": {
            "type": "boolean",
            "default": false,
        },
        "itemType": {
            "type": "string",
            "default": "",
        },
        "headingTextColor": {
            "type": "string"
        },
        "headingFontSize": {
            "type": "number"
        },
        "headingBackgroundColor": {
            "type": "string"
        },
        "heading": {
            "type": "string",
            "default": _x( 'Column Heading', 'storeabill-core', 'storeabill' ),
            "source": "html",
            "selector": "span.item-column-heading-text"
        }
    };

    itemTableColumn.getEditWrapperProps = function( attributes ) {
        const { width } = attributes;
        if ( Number.isFinite( width ) ) {
            return {
                style: {
                    flexBasis: width + '%',
                },
                'data-has-explicit-width': true,
            };
        } else {
            return {};
        }
    };

    registerBlockType( 'storeabill/item-table-column', itemTableColumn );

    return settings;
}

addFilter(
    'blocks.registerBlockType',
    'storeabill/item-table-column',
    blockTypeFilter
);