/**
 * WordPress dependencies
 */
import { _x } from '@wordpress/i18n';
import classnames from 'classnames';

import {
    InspectorControls,
} from '@wordpress/block-editor';

import { PanelBody, Toolbar, RangeControl } from "@wordpress/components";
import { getPreviewItem, getItemTotalKey, getSetting, FORMAT_TYPES } from '@storeabill/settings';

function ItemImageEdit( {
    attributes,
    setAttributes,
    className
} ) {
    const { customWidth } = attributes;
    const placeholderUrl  = getSetting( 'assets_url' ) + 'images/placeholder.png';

    const styles = {
        maxWidth: customWidth + 'px',
        width: customWidth + 'px'
    };

    return (
        <>
            <InspectorControls>
                <PanelBody>
                    <RangeControl
                        label={ _x( 'Width', 'storeabill-core', 'storeabill' ) }
                        value={ customWidth }
                        onChange={ ( newWidth ) =>
                            setAttributes( { customWidth: newWidth } )
                        }
                        min={ 25 }
                        max={ 100 }
                    />
                </PanelBody>
            </InspectorControls>
            <div>
                <img src={ placeholderUrl } style={ styles } alt="" className="sab-document-image-placeholder" />
            </div>
        </>
    );
}

export default ItemImageEdit;