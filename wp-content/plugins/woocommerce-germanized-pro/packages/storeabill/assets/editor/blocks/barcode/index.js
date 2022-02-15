import { _x } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { getDefaultPlaceholderContent } from "@storeabill/utils";
import { getSetting } from "@storeabill/settings";
import { qrCode } from '@storeabill/icons';

import edit from './edit';

/**
 * Internal dependencies
 */
import './editor.scss';

const settings = {
    title: _x( 'Barcode', 'storeabill-core', 'storeabill' ),
    description: _x( 'Inserts a barcode.', 'storeabill-core', 'storeabill' ),
    category: 'storeabill',
    example: {},
    icon: qrCode,
    supports: {
        html: false,
        className: false,
    },
    attributes: {
        "textColor": {
            "type": "string"
        },
        "customTextColor": {
            "type": "string"
        },
        "align": {
            "type": 'string',
            "default": 'left'
        },
        "barcodeType": {
            "type": "string",
            "default": ""
        },
        "size": {
            "type": "string",
            "default": "normal",
        },
        "codeType": {
            "type": "string",
            "default": 'order_number'
        }
    },
    edit
};

registerBlockType( 'storeabill/barcode', settings );