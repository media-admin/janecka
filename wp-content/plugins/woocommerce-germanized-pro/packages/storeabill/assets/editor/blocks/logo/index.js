import { registerBlockType } from '@wordpress/blocks';
import { _x } from '@wordpress/i18n';
import { times, assign, cloneDeep } from 'lodash';
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import './editor.scss';

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
    if ( name !== 'core/image' || hasRegistered ) {
        return settings;
    }

    hasRegistered = true;
    const logo = cloneDeep( settings );

    logo.category = 'storeabill';
    logo.name     = 'storeabill/logo';
    logo.title    = _x( 'Logo', 'storeabill-core', 'storeabill' );
    logo.styles   = [];
    logo.example  = {};

    registerBlockType( 'storeabill/logo', logo );

    return settings;
}

addFilter(
    'blocks.registerBlockType',
    'storeabill/logo',
    blockTypeFilter
);