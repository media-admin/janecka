import { _x } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { euro } from '@storeabill/icons';

import edit from './edit';
import save from './save';

/**
 * Internal dependencies
 */
import './editor.scss';

const settings = {
    title: _x( 'Item Totals', 'storeabill-core', 'storeabill' ),
    description: _x( 'Insert item totals.', 'storeabill-core', 'storeabill' ),
    icon: euro,
    category: 'storeabill',
    supports: {
        html: false,
    },
    attributes: {
        "hasDenseLayout": {
            "type": "boolean",
            "default": false,
        },
    },
    edit,
    save,
};

registerBlockType( 'storeabill/item-totals', settings );