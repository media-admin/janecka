import { _x } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { getDefaultPlaceholderContent } from "@storeabill/utils";
import { title as iconTitle } from '@storeabill/icons';

import edit from './edit';
import save from './save';

const settings = {
    title: _x( 'Item Name', 'storeabill-core', 'storeabill' ),
    description: _x( 'Inserts the item name.', 'storeabill-core', 'storeabill' ),
    category: 'storeabill',
    icon: iconTitle,
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
        "content": {
            "type": 'string',
            "source": 'html',
            "selector": 'p.sab-block-item-content',
            "default": ''
        },
    },
    edit,
    save
};

registerBlockType( 'storeabill/item-name', settings );