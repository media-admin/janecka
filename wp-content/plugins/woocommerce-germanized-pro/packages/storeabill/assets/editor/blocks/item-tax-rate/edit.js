/**
 * WordPress dependencies
 */
import { _x } from '@wordpress/i18n';
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

const ItemTaxRateEdit = ( {
   attributes,
   setAttributes,
   fontSize,
   setFontSize,
   className
} ) => {
    const { content, itemType } = attributes;
    let item = getPreviewItem( itemType );

    const taxRate = item.tax_rates[0].formatted_percentage_html;

    const {
        TextColor,
        InspectorControlsColorPanel
    } = useColors(
        [
            { name: 'textColor', property: 'color' },
        ],
        [ fontSize.size ]
    );

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
                        value={ replacePlaceholderWithPreview( content, taxRate, '{content}', false, _x( 'Tax Rate', 'storeabill-core', 'storeabill' ) ) }
                        placeholder={ replacePlaceholderWithPreview( undefined, taxRate, '{content}', false, _x( 'Tax Rate', 'storeabill-core', 'storeabill' ) ) }
                        className='sab-block-item-content placeholder-wrapper'
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

const ItemTaxRateEditor = compose( [ withFontSizes( 'fontSize' ) ] )(
    ItemTaxRateEdit
);

export default ItemTaxRateEditor;