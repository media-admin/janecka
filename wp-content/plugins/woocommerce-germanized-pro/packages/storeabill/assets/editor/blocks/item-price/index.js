import { _x } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { getDefaultPlaceholderContent } from "@storeabill/utils";
import { euro } from '@storeabill/icons';

import edit from './edit';
import save from './save';

const settings = {
    title: _x( 'Item Price', 'storeabill-core', 'storeabill' ),
    description: _x( 'Inserts the item price.', 'storeabill-core', 'storeabill' ),
    category: 'storeabill',
    icon: euro,
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
        "customFontSize": {
            "type": "string"
        },
        "discountTotalType": {
            "type": "string",
            "default": "before_discounts"
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
                "discountTotalType": {
                    "type": "string",
                    "default": "before_discounts"
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

registerBlockType( 'storeabill/item-price', settings );