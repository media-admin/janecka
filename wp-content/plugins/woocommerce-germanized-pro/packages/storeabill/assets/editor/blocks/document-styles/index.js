import { _x } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';

import edit from './edit';

/**
 * Internal dependencies
 */
import './editor.scss';

const settings = {
    title: _x( 'Document Styles', 'storeabill-core', 'storeabill' ),
    description: _x( 'Watches for changes within the document and adjusts preview styles.', 'storeabill-core', 'storeabill' ),
    category: 'storeabill',
    supports: {
        html: false,
        inserter: false,
    },
    example: {},
    attributes: {},
    edit,
};

registerBlockType( 'storeabill/document-styles', settings );