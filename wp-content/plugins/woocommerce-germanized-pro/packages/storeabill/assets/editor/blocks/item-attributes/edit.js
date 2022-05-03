/**
 * WordPress dependencies
 */
import { _x } from '@wordpress/i18n';
import classnames from 'classnames';

import {
    FontSizePicker,
    InspectorControls,
    withFontSizes,
} from '@wordpress/block-editor';

import { getPreviewItem, getSetting } from '@storeabill/settings';
import { PanelBody, FormTokenField } from "@wordpress/components";
import { compose } from "@wordpress/compose";
import { withState } from '@wordpress/compose';
import { getFontSizeStyle, convertFontSizeForPicker, useColors } from '@storeabill/utils';

const ItemAttributesEdit = ( {
    attributes,
    setAttributes,
    fontSize,
    setFontSize,
    className
} ) => {
    const { customAttributes, itemType } = attributes;
    let item = getPreviewItem( itemType );

    const {
        TextColor,
        InspectorControlsColorPanel
    } = useColors(
        [
            { name: 'textColor', property: 'color' },
        ],
        [ fontSize.size ]
    );

    const classes = classnames( 'sab-block-item-content sab-block-item-attributes', className, {
        [ fontSize.class ]: fontSize.class,
    } );

    return (
        <>
            <InspectorControls>
                <PanelBody title={ _x( 'Additional Attributes', 'storeabill-core', 'storeabill' ) }>
                    <FormTokenField
                        suggestions={ getSetting( 'attribute_slugs' ) }
                        value={ customAttributes }
                        onChange={ tokens => setAttributes( { customAttributes: tokens } ) }
                        label={ _x( 'Add attribute slugs', 'storeabill-core', 'storeabill' ) }
                    />
                </PanelBody>
                <PanelBody title={ _x( 'Typography', 'storeabill-core', 'storeabill' ) }>
                    <FontSizePicker
                        value={ convertFontSizeForPicker( fontSize.size ) }
                        onChange={ setFontSize }
                    />
                </PanelBody>
            </InspectorControls>
            { InspectorControlsColorPanel }
            <div>
                <TextColor>
                    <div
                        className={ classes }
                        dangerouslySetInnerHTML={ {
                            __html: item.attributes_formatted,
                        } }
                        style={ {
                            fontSize: getFontSizeStyle( fontSize )
                        } }
                    />
                </TextColor>
            </div>
        </>
    );
};

const ItemAttributesEditor = compose( [ withFontSizes( 'fontSize' ) ] )(
    ItemAttributesEdit
);

export default ItemAttributesEditor;