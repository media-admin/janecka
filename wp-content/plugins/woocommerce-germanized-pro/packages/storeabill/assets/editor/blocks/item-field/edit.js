/**
 * WordPress dependencies
 */
import { _x } from '@wordpress/i18n';
import classnames from 'classnames';

import {
    InspectorControls,
    AlignmentToolbar,
    BlockControls,
    RichText,
    RichTextToolbarButton,
    withColors,
    withFontSizes,
    FontSizePicker
} from '@wordpress/block-editor';

import { compose } from "@wordpress/compose";
import {PanelBody, Toolbar, RangeControl, ToggleControl} from "@wordpress/components";
import { getPreviewItem, getItemTotalKey, getSetting, FORMAT_TYPES } from '@storeabill/settings';
import {getBorderClasses} from "@storeabill/components/border-select";
import { useEffect } from "@wordpress/element";
import { getFontSizeStyle, convertFontSizeForPicker, useColors } from '@storeabill/utils';

function ItemFieldEdit( {
    attributes,
    setAttributes,
    className,
    borderColor,
    backgroundColor,
    textColor,
    fontSize,
    setFontSize,
} ) {
    const { placeholder } = attributes;

    useEffect( () => {
        setAttributes( {
            customBackgroundColor: '#f1f4ff',
            customFontSize: '11',
            customTextColor: '#93a3dc',
        } );
    }, [] );

    const classes = classnames( className, {
        'has-border-color': borderColor.color,
        'has-background-color': backgroundColor.color,
        'has-text-color': textColor.color,
        [borderColor.class]: borderColor.class,
        'item-field': 'item-field'
    } );

    const {
        InspectorControlsColorPanel,
        BorderColor,
        BackgroundColor,
        TextColor,
    } = useColors(
        [
            { name: 'borderColor', className: 'has-border-color' },
            { name: 'backgroundColor', className: 'has-background-color' },
            { name: 'textColor', className: 'has-text-color' },
            [ fontSize.size ]
        ]
    );

    return (
        <>
            <InspectorControls>
                <PanelBody>
                    <FontSizePicker
                        value={ convertFontSizeForPicker( fontSize.size ) }
                        onChange={ setFontSize }
                    />
                </PanelBody>
            </InspectorControls>
            { InspectorControlsColorPanel }
            <BackgroundColor>
                <div className={ classes } style={ {
                    borderColor: borderColor.color,
                    fontSize: getFontSizeStyle( fontSize ),
                    color: textColor.color,
                } }>
                    <RichText
                        tagName="span"
                        placeholder={ _x( 'Placeholder', 'storeabill-core', 'storeabill' ) }
                        value={ placeholder }
                        onChange={ ( value ) =>
                            setAttributes( { placeholder: value } )
                        }
                        allowedFormats={ FORMAT_TYPES }
                        className='field-placeholder'
                    />
                </div>
            </BackgroundColor>
        </>
    );
}

export default compose( [
    withFontSizes( 'fontSize' ),
    withColors( 'borderColor', 'backgroundColor', 'textColor' ),
] )( ItemFieldEdit );