import { _x } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { getDefaultPlaceholderContent } from "@storeabill/utils";
import { field as iconField } from '@storeabill/icons';

import edit from './edit';

/**
 * Internal dependencies
 */
import './editor.scss';

const settings = {
    title: _x( 'Item Field', 'storeabill-core', 'storeabill' ),
    description: _x( 'Inserts an input field for placeholder purposes.', 'storeabill-core', 'storeabill' ),
    category: 'storeabill',
    icon: iconField,
    parent: [ 'storeabill/item-table-column' ],
    example: {},
    attributes: {
        "placeholder": {
            "type": "string",
            "default": "",
        },
        "customBorderColor" : {
            "type": "string",
        },
        "borderColor": {
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
        "customTextColor" : {
            "type": "string",
        },
        "textColor": {
            "type": "string"
        },
        "fontSize": {
            "type": "string"
        },
        "customFontSize": {
            "type": "string"
        },
        "backgroundColor": {
            "type": "string"
        },
        "customBackgroundColor": {
            "type": "string",
        },
    },
    edit,
    deprecated: [
        {
            attributes: {
                "placeholder": {
                    "type": "string",
                    "default": "",
                },
                "customBorderColor" : {
                    "type": "string",
                },
                "borderColor": {
                    "type": "string"
                },
                "customTextColor" : {
                    "type": "string",
                },
                "textColor": {
                    "type": "string"
                },
                "fontSize": {
                    "type": "string"
                },
                "customFontSize": {
                    "type": "number"
                },
                "backgroundColor": {
                    "type": "string"
                },
                "customBackgroundColor": {
                    "type": "string",
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

registerBlockType( 'storeabill/item-field', settings );