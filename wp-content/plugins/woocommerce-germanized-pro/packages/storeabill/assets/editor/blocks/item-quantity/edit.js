/**
 * WordPress dependencies
 */
import { _x } from '@wordpress/i18n';
import classnames from 'classnames';

import {
    FontSizePicker,
    InspectorControls,
    withFontSizes,
    RichText,
} from '@wordpress/block-editor';

import { getPreviewItem, FORMAT_TYPES } from '@storeabill/settings';
import { replacePreviewWithPlaceholder, replacePlaceholderWithPreview, getFontSizeStyle, convertFontSizeForPicker, useColors } from "@storeabill/utils";

import { PanelBody } from "@wordpress/components";
import { compose } from "@wordpress/compose";

const ItemQuantityEdit = ( {
    attributes,
    setAttributes,
    fontSize,
    setFontSize,
    className
} ) => {
    const { content, itemType } = attributes;
    let item = getPreviewItem( itemType );

    const quantity = item.quantity;

    const {
        TextColor,
        InspectorControlsColorPanel
    } = useColors(
        [
            { name: 'textColor', property: 'color' },
        ],
        [ fontSize.size ]
    );

    const classes = classnames( 'sab-block-item-content placeholder-wrapper sab-block-item-quantity', className, {
        [ fontSize.class ]: fontSize.class,
    } );

    return (
        <>
            <InspectorControls>
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
                    <RichText
                      tagName="p"
                      value={ replacePlaceholderWithPreview( content, quantity, '{content}', false, _x( 'Quantity', 'storeabill-core', 'storeabill' ) ) }
                      placeholder={ replacePlaceholderWithPreview( undefined, quantity, '{content}', false, _x( 'Quantity', 'storeabill-core', 'storeabill' ) ) }
                      className={ classes }
                      onChange={ ( value ) =>
                        setAttributes( { content: replacePreviewWithPlaceholder( value, '{content}' ) } )
                      }
                      allowedFormats={ FORMAT_TYPES }
                      style={ {
                          fontSize: getFontSizeStyle( fontSize )
                      } }
                    />
                </TextColor>
            </div>
        </>
    );
};

const ItemQuantityEditor = compose( [ withFontSizes( 'fontSize' ) ] )(
    ItemQuantityEdit
);

export default ItemQuantityEditor;