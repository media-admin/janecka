import { registerBlockType } from '@wordpress/blocks';
import { _x } from '@wordpress/i18n';
import { createBlock } from '@wordpress/blocks';
import { group } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import edit from './edit';
import save from './save';

const settings = {
    category: 'storeabill',
    title: _x( 'Footer', 'storeabill-core', 'storeabill' ),
    description: _x( 'Inserts a footer block.', 'storeabill-core', 'storeabill' ),
    icon: group,
    attributes: {
        "tagName": {
            "type": "string",
            "default": "div"
        }
    },
    supports: {
        anchor: true,
        html: false,
        multiple: false,
    },
    transforms: {
        from: [
            {
                type: 'block',
                isMultiBlock: true,
                blocks: [ '*' ],
                __experimentalConvert( blocks ) {
                    // Avoid transforming a single `core/group` Block
                    if (
                      blocks.length === 1 &&
                      blocks[ 0 ].name === 'storeabill/footer'
                    ) {
                        return;
                    }

                    // Clone the Blocks to be Grouped
                    // Failing to create new block references causes the original blocks
                    // to be replaced in the switchToBlockType call thereby meaning they
                    // are removed both from their original location and within the
                    // new group block.
                    const groupInnerBlocks = blocks.map( ( block ) => {
                        return createBlock(
                          block.name,
                          block.attributes,
                          block.innerBlocks
                        );
                    } );

                    return createBlock(
                      'storeabill/footer',
                      groupInnerBlocks
                    );
                },
            },
        ],
    },
    edit,
    save,
};

registerBlockType( 'storeabill/footer', settings );
