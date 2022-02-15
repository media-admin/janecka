/**
 * WordPress dependencies
 */
import { _x } from '@wordpress/i18n';
import classnames from 'classnames';

import { BlockControls, AlignmentToolbar } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from "@wordpress/components";
import { getPreview, FORMAT_TYPES } from '@storeabill/settings';
import {
    FontSizePicker,
    InspectorControls,
    withFontSizes,
    RichText,
} from '@wordpress/block-editor';

import { replacePreviewWithPlaceholder, replacePlaceholderWithPreview, getFontSizeStyle, convertFontSizeForPicker, useColors } from "@storeabill/utils";
import { compose } from "@wordpress/compose";

const ShippingAddressEdit = ( {
  attributes,
  setAttributes,
  fontSize,
  setFontSize,
    className
} ) => {

    const { align, content, hideIfEqualsBilling } = attributes;
    let preview = getPreview();

    const address = preview.formatted_shipping_address;

    const {
        TextColor,
        InspectorControlsColorPanel
    } = useColors(
        [
            { name: 'textColor', property: 'color' },
        ],
        [ fontSize.size ]
    );

    const classes = classnames( 'document-shipping-address placeholder-wrapper', className, {
        [ `has-text-align-${ align }` ]: align,
        [ fontSize.class ]: fontSize.class,
    } );

    return (
        <>
            <BlockControls>
                <AlignmentToolbar
                    value={ align }
                    onChange={ ( updatedAlignment ) => setAttributes( { align: updatedAlignment } ) }
                />
            </BlockControls>
            <InspectorControls>
                <PanelBody>
                    <ToggleControl
                        label={ _x( 'Hide in case equals billing address', 'storeabill-core', 'storeabill' ) }
                        checked={ hideIfEqualsBilling }
                        onChange={ () => setAttributes( { hideIfEqualsBilling: ! hideIfEqualsBilling } ) }
                    />
                    <FontSizePicker
                        value={ convertFontSizeForPicker( fontSize.size ) }
                        onChange={ setFontSize }
                    />
                </PanelBody>
            </InspectorControls>
            { InspectorControlsColorPanel }
            { hideIfEqualsBilling && <span className="notice notice-warning sab-visibility-notice">{ _x( 'Conditional visibility', 'storeabill-core', 'storeabill' ) }</span> }
            <div className="shipping-address-wrapper">
                <TextColor>
                    <RichText
                        tagName="p"
                        value={ replacePlaceholderWithPreview( content, address, '{content}' ) }
                        placeholder={ replacePlaceholderWithPreview( undefined, address, '{content}' ) }
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

const ShippingAddressEditor = compose( [ withFontSizes( 'fontSize' ) ] )(
    ShippingAddressEdit
);

export default ShippingAddressEditor;