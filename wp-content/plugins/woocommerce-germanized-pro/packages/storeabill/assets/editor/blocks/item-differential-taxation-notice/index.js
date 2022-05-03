import { _x } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { getDefaultPlaceholderContent } from "@storeabill/utils";
import { archive } from '@wordpress/icons';

import edit from './edit';
import save from './save';

const settings = {
    title: _x( 'Item Differential Taxation Notice', 'storeabill-core', 'storeabill' ),
    description: _x( 'Inserts a notice in case this item is subject to differential taxation.', 'storeabill-core', 'storeabill' ),
    category: 'storeabill',
    icon: archive,
    parent: [ 'storeabill/item-table-column' ],
    supports: {
        html: false,
    },
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
        "content": {
            "type": 'string',
            "source": 'html',
            "selector": 'p.sab-block-item-content',
            "default": _x( 'Subject to differential taxation under §25a UStG.', 'storeabill-core', 'storeabill' )
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
                "content": {
                    "type": 'string',
                    "source": 'html',
                    "selector": 'p.sab-block-item-content',
                    "default": _x( 'Subject to differential taxation under §25a UStG.', 'storeabill-core', 'storeabill' )
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

registerBlockType( 'storeabill/item-differential-taxation-notice', settings );