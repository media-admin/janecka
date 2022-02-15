/**
 * WordPress dependencies
 */
import { _x } from '@wordpress/i18n';
import classnames from 'classnames';

import {
    FontSizePicker,
    InspectorControls,
    withFontSizes,
    BlockControls,
    RichText,
} from '@wordpress/block-editor';

import { getPreviewItem, FORMAT_TYPES } from '@storeabill/settings';
import { getFontSizeStyle, convertFontSizeForPicker } from '@storeabill/utils';
import { replacePreviewWithPlaceholder, replacePlaceholderWithPreview, useColors } from "@storeabill/utils";

import { PanelBody } from "@wordpress/components";
import { compose } from "@wordpress/compose";

function ItemDifferentialTaxationNoticeEdit( {
    className,
    attributes,
    setAttributes,
    fontSize,
    setFontSize,
} ) {
    const { content } = attributes;

    const {
        TextColor,
        InspectorControlsColorPanel
    } = useColors(
        [
            { name: 'textColor', property: 'color' },
        ],
        [ fontSize.size ]
    );

    const classes = classnames( 'sab-block-item-content placeholder-wrapper sab-block-item-differential-taxation-notice', className, {
        [ fontSize.class ]: fontSize.class,
    } );

    return (
        <>
            <BlockControls>
                <span className="notice notice-warning sab-visibility-notice">{ _x( 'Conditional visibility', 'storeabill-core', 'storeabill' ) }</span>
            </BlockControls>
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
                  value={ content }
                  className={ classes }
                  onChange={ ( value ) =>
                    setAttributes( { content: value } )
                  }
                  allowedFormats={ FORMAT_TYPES }
                  style={ {
                      fontSize: getFontSizeStyle( fontSize ),
                  } }
                />
            </TextColor>
        </>
    );
}

const ItemDifferentialTaxationNoticeEditor = compose( [ withFontSizes( 'fontSize' ) ] )(
    ItemDifferentialTaxationNoticeEdit
);

export default ItemDifferentialTaxationNoticeEditor;