import { _x } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { getDefaultPlaceholderContent } from "@storeabill/utils";
import { address } from '@storeabill/icons';

import edit from './edit';
import save from './save';

const settings = {
    title: _x( 'Shipping Address', 'storeabill-core', 'storeabill' ),
    description: _x( 'Inserts the shipping address.', 'storeabill-core', 'storeabill' ),
    category: 'storeabill',
    icon: address,
    supports: {
        html: false,
    },
    example: {},
    attributes: {
        "align": {
            "type": "string",
            "default": "left",
        },
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
            "type": "string"
        },
        "content": {
            "type": 'string',
            "source": 'html',
            "selector": 'p.address-content',
            "default": getDefaultPlaceholderContent( '{content}' )
        },
        "hideIfEqualsBilling": {
            "type": "boolean",
            "default": false,
        },
    },
    edit,
    save,
    deprecated: [
        {
            supports: {
                html: false,
            },
            attributes: {
                "align": {
                    "type": "string",
                    "default": "left",
                },
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
                "content": {
                    "type": 'string',
                    "source": 'html',
                    "selector": 'p.address-content',
                    "default": getDefaultPlaceholderContent( '{content}' )
                },
                "hideIfEqualsBilling": {
                    "type": "boolean",
                    "default": false,
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
                return save( attributes );
            }
        },
    ]
};

registerBlockType( 'storeabill/shipping-address', settings );