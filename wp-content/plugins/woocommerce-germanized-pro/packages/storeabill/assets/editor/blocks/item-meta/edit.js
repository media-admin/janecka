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

import { getPreviewItem, FORMAT_TYPES, getItemMetaTypes, getItemMetaTypePreview } from '@storeabill/settings';
import { settings, arrowRight } from '@storeabill/icons';
import { isEmpty } from 'lodash';

import { replacePreviewWithPlaceholder, replacePlaceholderWithPreview, getFontSizeStyle, convertFontSizeForPicker, useColors } from "@storeabill/utils";

import { PanelBody, ToggleControl, Toolbar, DropdownMenu, ToolbarGroup } from "@wordpress/components";
import { compose } from "@wordpress/compose";

function MetaTypeSelect( props ) {
    const {
        value,
        onChange,
        types = [],
        label = _x( 'Change meta type', 'storeabill-core', 'storeabill' ),
        isCollapsed = true,
    } = props;

    return (
        <ToolbarGroup>
            <DropdownMenu
                label={ label }
                icon={ settings }
                controls={ types.map( ( control ) => {
                    const { type } = control;
                    const isActive = value === type;

                    return {
                        ...control,
                        isActive: isActive,
                        icon: isEmpty( control.icon ) ? arrowRight : control.icon,
                        role: isCollapsed ? 'menuitemradio' : undefined,
                        onClick: () => onChange( type )
                    };
                } ) }
            />
        </ToolbarGroup>
    );
}

function ItemMetaEdit( {
   className,
   attributes,
   setAttributes,
   fontSize,
   setFontSize,
} ) {
    const { metaType, content, hideIfEmpty } = attributes;
    let item = getPreviewItem();
    const preview = getItemMetaTypePreview( metaType );

    const {
        TextColor,
        InspectorControlsColorPanel
    } = useColors(
        [
            { name: 'textColor', property: 'color' },
        ],
        [ fontSize.size ]
    );

    const classes = classnames( 'sab-block-item-content placeholder-wrapper sab-block-item-meta', className, {
        [ fontSize.class ]: fontSize.class,
    } );

    return (
        <div>
            <BlockControls>
                <MetaTypeSelect
                    label={ _x( 'Change type', 'storeabill-core', 'storeabill' ) }
                    value={ metaType }
                    types={ getItemMetaTypes() }
                    onChange={ ( newType ) =>
                        setAttributes( { metaType: newType } )
                    }
                />
            </BlockControls>
            <InspectorControls>
                <PanelBody>
                    <ToggleControl
                        label={ _x( 'Hide if empty', 'storeabill-core', 'storeabill' ) }
                        checked={ hideIfEmpty }
                        onChange={ () => setAttributes( { hideIfEmpty: ! hideIfEmpty } ) }
                    />
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
                    value={ replacePlaceholderWithPreview( content, preview, '{content}' ) }
                    placeholder=""
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

const ItemMetaEditor = compose( [ withFontSizes( 'fontSize' ) ] )(
    ItemMetaEdit
);

export default ItemMetaEditor;