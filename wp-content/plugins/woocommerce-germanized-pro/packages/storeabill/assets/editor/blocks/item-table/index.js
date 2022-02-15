import { _x } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { table } from '@wordpress/icons';

import edit from './edit';
import save from './save';

/**
 * Internal dependencies
 */
import './editor.scss';

const settings = {
    title: _x( 'Item Table', 'storeabill-core', 'storeabill' ),
    description: _x( 'Inserts the item table.', 'storeabill-core', 'storeabill' ),
    category: 'storeabill',
    icon: table,
    supports: {
        html: false,
    },
    attributes: {
        'className': {
            "type": "string"
        },
        "customBorderColor" : {
            "type": "string"
        },
        "borderColor": {
            "type": "string"
        },
        "borders": {
            "type": "array",
            "default": ['horizontal']
        },
        "headingBackgroundColor": {
            "type": "string"
        },
        "headingTextColor": {
            "type": "string"
        },
        "customHeadingBackgroundColor": {
            "type": "string",
        },
        "customHeadingTextColor": {
            "type": "string"
        },
        "headingFontSize": {
            "type": "string"
        },
        "headingCustomFontSize": {
            "type": "string"
        },
        "hasDenseLayout": {
            "type": "boolean",
            "default": false,
        },
        "showPricesIncludingTax": {
            "type": "boolean",
            "default": true,
        },
    },
    edit,
    save,
    styles: [
        {
            name: 'default',
            label: _x( 'Normal', 'storeabill-core', 'storeabill' ),
            isDefault: true
        },
        {
            name: 'odd',
            label: _x( 'Odd highlight', 'storeabill-core', 'storeabill' ),
        },
        {
            name: 'even',
            label: _x( 'Even highlight', 'storeabill-core', 'storeabill' ),
        },
    ]
};

registerBlockType( 'storeabill/item-table', settings );