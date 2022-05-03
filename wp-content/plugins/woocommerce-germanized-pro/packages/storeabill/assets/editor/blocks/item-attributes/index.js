import { _x } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { formatListBullets } from '@wordpress/icons';

import edit from './edit';

/**
 * Internal dependencies
 */
import './editor.scss';
import save from "../document-date/save";

const settings = {
    title: _x( 'Item Attributes', 'storeabill-core', 'storeabill' ),
    description: _x( 'Inserts item attribute list.', 'storeabill-core', 'storeabill' ),
    category: 'storeabill',
    icon:  formatListBullets,
    parent: [ 'storeabill/item-table-column' ],
    example: {},
    attributes: {
        "textColor": {
            "type": "string"
        },
        "customTextColor": {
            "type": "string"
        },
        "fontSize": {
            "type": "string"
        },
        "isDisabled": {
            "type": "boolean",
            "default": false,
        },
        "itemType": {
            "type": "string",
            "default": "",
        },
        "customFontSize": {
            "type": "string"
        },
        "customAttributes": {
            "type": "array"
        },
    },
    styles: [
        {
            name: 'attribute-list',
            label: _x( 'List', 'storeabill-core', 'storeabill' ),
            isDefault: true
        },
        {
            name: 'attribute-line',
            label: _x( 'One line', 'storeabill-core', 'storeabill' ),
        }
    ],
    edit,
    deprecated: [
        {
            attributes: {
                "textColor": {
                    "type": "string"
                },
                "customTextColor": {
                    "type": "string"
                },
                "fontSize": {
                    "type": "string"
                },
                "customFontSize": {
                    "type": "number"
                },
                "customAttributes": {
                    "type": "array"
                },
            },
            isEligible( { customFontSize } ) {
                return typeof customFontSize === 'number';
            },
            migrate( attributes ) {
                return {
                    ...attributes,
                    customFontSize: attributes.customFontSize ? '' + attributes.customFontSize : undefined,
                };
            },
            save( attributes ) {
                return null;
            }
        },
    ]
};

registerBlockType( 'storeabill/item-attributes', settings );