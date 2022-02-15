/**
 * WordPress dependencies
 */
import { _x } from '@wordpress/i18n';
import { compose } from "@wordpress/compose";
import { withDispatch, withSelect } from "@wordpress/data";
import { get, isEqual, isEmpty } from 'lodash';
import { Component } from "@wordpress/element";

import { formatMargins, getSetting, getCurrentFonts, getFontsCSS } from '@storeabill/settings';
import { getFontSizeStyle } from '@storeabill/utils';

class DocumentStylesEdit extends Component {

    constructor() {
        super( ...arguments );

        this.state = {
            fontsFacetsCSS: '',
            fontsInlineCSS: '',
        };
    }

    componentDidMount() {
        this.updateFontsCSS();
        this.applyWrapperStyles();
    }

    updateFontsCSS() {
        if ( ! isEmpty( this.props.fonts ) && ! getSetting( 'isFirstPage' ) ) {
            getFontsCSS( this.props.fonts ).then( css => {
                this.setState({
                    'fontsFacetsCSS': css['facets'],
                    'fontsInlineCSS': css['inline'],
                });
            } ).catch( () => {
                this.setState({
                    'fontsFacetsCSS': '',
                    'fontsInlineCSS': '',
                });
            } );
        } else {
            this.setState({
                'fontsFacetsCSS': '',
                'fontsInlineCSS': '',
            });
        }
    }

    componentDidUpdate( prevProps, prevState ) {

        if ( ! isEqual( this.props.fonts, prevProps.fonts ) ) {
            this.updateFontsCSS();
        }

        // After lazy loading fonts, adjust font faces + family
        if ( this.state.fontsFacetsCSS !== prevState.fontsFacetsCSS ) {
            this.addFonts();
        }

        if ( ! isEqual( this.props.fonts, prevProps.fonts ) ||
            ! isEqual( this.props.pdfAttachment, prevProps.pdfAttachment ) ||
            ! isEqual( this.props.margins, prevProps.margins ) ||
            ! isEqual( this.props.fontSize, prevProps.fontSize ) ||
            ! isEqual( this.props.color, prevProps.color )
        ) {
            this.applyWrapperStyles();
        }
    }

    addFonts() {
        const { fontsFacetsCSS, fontsInlineCSS } = this.state;

        if ( jQuery( 'style#sab-block-editor-inline-css' ) <= 0 ) {
            jQuery( '<style id="sab-block-editor-inline-css">' ).appendTo( 'head' );
        }

        const $facetsWrapper = jQuery( 'style#sab-block-editor-inline-css' );
        const existingFacets = $facetsWrapper.html().trim();

        if ( existingFacets !== fontsFacetsCSS ) {
            $facetsWrapper.html( fontsFacetsCSS );
        }

        jQuery( 'body' ).find( '.sab-font-inline' ).remove();
        jQuery( 'body' ).append( '<style type="text/css" class="sab-font-inline">' + fontsInlineCSS + '</style>' );
    }

    getAttachmentThumb( image, sizeSlug, attribute ) {
        return get( image, [ 'media_details', 'sizes', sizeSlug, attribute ] );
    }

    applyWrapperStyles() {
        const $mainWrapper = jQuery( '.editor-styles-wrapper' );
        const $wrapper     = $mainWrapper.find( '.block-editor-block-list__layout:first' );

        const { pdfAttachment, margins, fonts, fontSize, color } = this.props;

        if ( fontSize ) {
            $wrapper.css( 'font-size', getFontSizeStyle( fontSize ) );
        }

        if ( color ) {
            $wrapper.css( 'color', color );
        }

        if ( getSetting( 'isFirstPage' ) ) {
            $wrapper.addClass( 'sab-is-first-page' );
        }

        let hasBackground = false;

        if ( pdfAttachment ) {
            const previewThumb = this.getAttachmentThumb( pdfAttachment, 'full', 'source_url' );

            if ( previewThumb ) {
                $wrapper.css( 'background-image', 'linear-gradient(to bottom, rgba(255,255,255,0.7) 0%,rgba(255,255,255,0.7) 100%), url(' + previewThumb + ')' );
                $wrapper.addClass( 'has-background-image' );

                hasBackground = true;
            }
        }

        if ( ! hasBackground ) {
            $wrapper.css( 'background-image', 'none' );
            $wrapper.removeClass( 'has-background-image' );
        }

        $wrapper.css( 'padding-left', margins['left'] + 'cm' );
        $wrapper.css( 'padding-right', margins['right'] + 'cm' );
        $wrapper.css( 'padding-top', margins['top'] + 'cm' );
        $wrapper.css( 'padding-bottom', margins['bottom'] + 'cm' );
    }

    render() {
        return null;
    }
}

export default compose(
    withSelect( ( select, { attributes } ) => {

        const { getMedia } = select( 'core' );
        const { getEditedPostAttribute } = select( 'core/editor' );

        const meta            = getEditedPostAttribute( 'meta' );
        const pdfAttachmentId = meta['_pdf_template_id'];
        const documentMargins = meta['_margins'];
        const defaultMargins  = getSetting( 'defaultMargins' );
        const fontSize        = meta['_font_size'];
        const defaultFontSize = getSetting( 'defaultFontSize' );
        const color           = meta['_color'];
        const defaultColor    = getSetting( 'defaultColor' );
        const fonts           = getCurrentFonts();

        let newMargins   = formatMargins( documentMargins, defaultMargins );
        const attachment = pdfAttachmentId ? getMedia( pdfAttachmentId ) : null;

        return {
            pdfAttachment: attachment,
            margins: newMargins,
            fonts: fonts,
            fontSize: fontSize ? fontSize : defaultFontSize,
            color: color ? color : defaultColor
        };
    } )
)( DocumentStylesEdit );