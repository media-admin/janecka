import { _x } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { getDefaultPlaceholderContent } from "@storeabill/utils";
import { discount } from '@storeabill/icons';

import edit from './edit';
import save from './save';

const settings = {
    title: _x( 'Item Discount', 'storeabill-core', 'storeabill' ),
    description: _x( 'Inserts item discount.', 'storeabill-core', 'storeabill' ),
    category: 'storeabill',
    icon: discount,
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
        "discountType": {
            "type": "string",
            "default": "absolute"
        },
        "hideIfEmpty": {
            "type": "boolean",
            "default": false,
        },
        "showPricesIncludingTax": {
            "type": "boolean",
        },
        "content": {
            "type": 'string',
            "source": 'html',
            "selector": 'p.sab-block-item-content',
            "default": ''
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
                "discountType": {
                    "type": "string",
                    "default": "absolute"
                },
                "hideIfEmpty": {
                    "type": "boolean",
                    "default": false,
                },
                "showPricesIncludingTax": {
                    "type": "boolean",
                },
                "content": {
                    "type": 'string',
                    "source": 'html',
                    "selector": 'p.sab-block-item-content',
                    "default": ''
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

registerBlockType( 'storeabill/item-discount', settings );