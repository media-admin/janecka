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

function ItemPositionEdit( {
    className,
    attributes,
    setAttributes,
    fontSize,
    setFontSize,
} ) {
    const { content, itemType, currentPosition } = attributes;
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

    const classes = classnames( 'sab-block-item-content placeholder-wrapper sab-block-item-position', className, {
        [ fontSize.class ]: fontSize.class,
    } );

    const position = currentPosition;

    return (
        <div>
            <InspectorControls>
                <PanelBody title={ _x( 'Typography', 'storeabill-core', 'storeabill' ) }>
                    <FontSizePicker
                        value={ convertFontSizeForPicker( fontSize.size ) }
                        onChange={ setFontSize }
                    />
                </PanelBody>
            </InspectorControls>
            { InspectorControlsColorPanel }
            <TextColor>
                <RichText
                  tagName="p"
                  value={ replacePlaceholderWithPreview( content, position, '{content}', false, _x( 'Item Position', 'storeabill-core', 'storeabill' ) ) }
                  placeholder={ replacePlaceholderWithPreview( undefined, position, '{content}', false, _x( 'Item Position', 'storeabill-core', 'storeabill' ) ) }
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
    );
}

const ItemPositionEditor = compose( [ withFontSizes( 'fontSize' ) ] )(
    ItemPositionEdit
);

export default ItemPositionEditor;