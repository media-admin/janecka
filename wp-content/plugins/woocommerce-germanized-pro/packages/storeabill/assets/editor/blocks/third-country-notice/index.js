import { _x } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { archive } from '@wordpress/icons';

import edit from './edit';
import save from './save';

/**
 * Internal dependencies
 */
import './editor.scss';

const settings = {
    title: _x( 'Third Country Notice', 'storeabill-core', 'storeabill' ),
    description: _x( 'Inserts a notice in case the invoice is issued to a third country.', 'storeabill-core', 'storeabill' ),
    category: 'storeabill',
    icon: archive,
    example: {},
    supports: {
        html: false,
        className: false
    },
    attributes: {
        "content": {
            "type": 'string',
            "source": 'html',
            "selector": 'p',
            "default": _x( 'Tax-exempt export delivery.', 'storeabill-core', 'storeabill' )
        },
        "align": {
            "type": 'string',
            "default": 'left'
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
    },
    edit,
    save,
    deprecated: [
        {
            supports: {
                html: false,
                className: false
            },
            attributes: {
                "content": {
                    "type": 'string',
                    "source": 'html',
                    "selector": 'p',
                    "default": _x( 'Tax-exempt export delivery.', 'storeabill-core', 'storeabill' )
                },
                "align": {
                    "type": 'string',
                    "default": 'left'
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

registerBlockType( 'storeabill/third-country-notice', settings );