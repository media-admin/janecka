/**
 * WordPress dependencies
 */
import { _x } from '@wordpress/i18n';
import classnames from 'classnames';
import { BlockControls, AlignmentToolbar } from '@wordpress/block-editor';
import { PanelBody } from "@wordpress/components";
import { getPreview, FORMAT_TYPES } from '@storeabill/settings';
import {
  FontSizePicker,
  InspectorControls,
  withFontSizes,
  RichText,
} from '@wordpress/block-editor';

import { replacePreviewWithPlaceholder, replacePlaceholderWithPreview, replaceOptionalPlaceholderWithPreview, getFontSizeStyle, convertFontSizeForPicker, useColors } from "@storeabill/utils";
import { compose } from "@wordpress/compose";

const PageNumberEdit = ( {
  attributes,
  setAttributes,
  fontSize,
  setFontSize,
    className,
} ) => {

    const { align, content } = attributes;

    const currentPageNo = 1;
    const totalPages    = 1;

    let defaultContent = _x( 'Page', 'storeabill-page-number', 'storeabill' ) + ' <span class="current-page-no-placeholder-content sab-tooltip" contenteditable="false" data-tooltip="' + _x( 'Page number', 'storeabill-core', 'storeabill' ) + '"><span class="editor-placeholder"></span>{current_page_no}</span> ' + _x( 'of', 'storeabill-page-number', 'storeabill' ) + ' <span class="total-pages-placeholder-content sab-tooltip" contenteditable="false" data-tooltip="' + _x( 'Total pages', 'storeabill-core', 'storeabill' ) + '"><span class="editor-placeholder"></span>{total_pages}</span>';

    if ( ! content ) {
        setAttributes( { content: defaultContent } );
    }

    let innerContent = content ? content : defaultContent;

    const classes = classnames( 'document-page-number placeholder-wrapper', className, {
        [ `has-text-align-${ align }` ]: align,
        [ fontSize.class ]: fontSize.class,
    } );

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
            <BlockControls>
                <AlignmentToolbar
                    value={ align }
                    onChange={ ( updatedAlignment ) => setAttributes( { align: updatedAlignment } ) }
                />
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
                  value={ replaceOptionalPlaceholderWithPreview( replaceOptionalPlaceholderWithPreview( innerContent, currentPageNo, '{current_page_no}' ), totalPages, '{total_pages}' ) }
                  placeholder={ replaceOptionalPlaceholderWithPreview( replaceOptionalPlaceholderWithPreview( defaultContent, currentPageNo, '{current_page_no}' ), totalPages, '{total_pages}' ) }
                  className={ classes }
                  onChange={ ( value ) =>
                    setAttributes( { content: replacePreviewWithPlaceholder( replacePreviewWithPlaceholder( value, '{current_page_no}', 'current-page-no-placeholder-content' ), '{total_pages}', 'total-pages-placeholder-content' ) } )
                  }
                  allowedFormats={ FORMAT_TYPES }
                  style={ {
                    fontSize: getFontSizeStyle( fontSize )
                  } }
                />
            </TextColor>
        </>
    );
};

const PageNumberEditor = compose( [ withFontSizes( 'fontSize' ) ] )(
    PageNumberEdit
);

export default PageNumberEditor;