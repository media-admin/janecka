import domReady from '@wordpress/dom-ready';
import { PanelRow, FontSizePicker, ColorPicker } from '@wordpress/components';
import { withSelect, withDispatch } from '@wordpress/data';
import { compose } from '@wordpress/compose'

import { _x } from '@wordpress/i18n';
import { get, isEmpty, cloneDeep, merge } from 'lodash';
import { isDocumentTemplate, getSetting } from '@storeabill/settings';
import FontSelect from '@storeabill/components/fonts';
import { convertFontSizeForPicker } from "@storeabill/utils";

import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { registerPlugin } from '@wordpress/plugins';

function EditFontsPanel( {
    fontSize,
    onUpdateFontSize,
    color,
    onUpdateColor
} ) {
    const fontDisplayTypes = getSetting( 'fontDisplayTypes' );

    return (
        <PluginDocumentSettingPanel
            name="sab-fonts-panel"
            title={_x( 'Typography', 'storeabill-core', 'storeabill')}
            className="sab-fonts-panel"
        >
            <PanelRow>
                <ColorPicker
                    color={ color }
                    onChangeComplete={ onUpdateColor }
                    disableAlpha
                />
            </PanelRow>
            <PanelRow>
                <FontSizePicker
                    fontSizes={ getSetting( 'customFontSizes' ) }
                    value={ convertFontSizeForPicker( fontSize ) }
                    onChange={ onUpdateFontSize }
                    withSlider={ false }
                    fallbackFontSize={ getSetting( 'defaultFontSize' ) }
                />
            </PanelRow>
            <PanelRow className="sab-fonts-panel-row">
                { Object.keys( fontDisplayTypes ).map( function( displayType, index) {
                    return (
                        <FontSelect
                            key={ displayType }
                            displayType={ displayType }
                        />
                    )
                })}
            </PanelRow>
        </PluginDocumentSettingPanel>
    )
}

const applyWithSelect = withSelect( ( select ) => {
    const { getEditedPostAttribute } = select( 'core/editor' );
    const meta        = getEditedPostAttribute( 'meta' );
    const newFontSize = meta['_font_size'] ? meta['_font_size'] : getSetting( 'defaultFontSize' );
    const newColor    = meta['_color'] ? meta['_color'] : getSetting( 'defaultColor' );

    return {
        fontSize: newFontSize,
        color: newColor
    };
} );

const applyWithDispatch = withDispatch( ( dispatch ) => {
    const { editPost } = dispatch( 'core/editor' );

    return {
        onUpdateFontSize: ( fontSize ) => {
            editPost( { meta: { '_font_size': fontSize ? fontSize.toString() : '' } } );
        },
        onUpdateColor: ( color ) => {
            editPost( { meta: { '_color': color ? color.hex : getSetting( 'defaultColor' ) } } );
        }
    }
} );

const FontsPanel = compose(
    applyWithSelect,
    applyWithDispatch,
)( EditFontsPanel );

if ( ! getSetting( 'isFirstPage' ) ) {
    registerPlugin( 'storeabill-fonts-panel', { render: FontsPanel, icon: '' } );
}