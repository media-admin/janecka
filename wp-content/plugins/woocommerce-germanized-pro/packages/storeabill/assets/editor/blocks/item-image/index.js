import { _x } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { getDefaultPlaceholderContent } from "@storeabill/utils";
import { img as iconImg } from '@storeabill/icons';

import edit from './edit';

const settings = {
    title: _x( 'Item Image', 'storeabill-core', 'storeabill' ),
    description: _x( 'Inserts the item image.', 'storeabill-core', 'storeabill' ),
    category: 'storeabill',
    icon: iconImg,
    parent: [ 'storeabill/item-table-column' ],
    example: {},
    attributes: {
        "customWidth": {
            "type": "number"
        },
        "isDisabled": {
            "type": "boolean",
            "default": false,
        },
        "itemType": {
            "type": "string",
            "default": "",
        },
    },
    edit
};

registerBlockType( 'storeabill/item-image', settings );