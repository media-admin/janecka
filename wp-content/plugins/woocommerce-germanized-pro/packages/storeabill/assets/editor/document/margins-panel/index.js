import { select } from '@wordpress/data';
import domReady from '@wordpress/dom-ready';
import { PanelRow } from '@wordpress/components';

import { _x } from '@wordpress/i18n';
import { isDocumentTemplate, getSetting } from '@storeabill/settings';
import Margins from '@storeabill/components/margins';

import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { registerPlugin } from '@wordpress/plugins';

const MarginsPanel = () => {
    return (
        <PluginDocumentSettingPanel
            name="sab-margins-panel"
            title={_x( 'Margins (cm)', 'storeabill-core', 'storeabill')}
            className="sab-margins-panel"
        >
            <PanelRow className="sab-margins-panel-row">
                <Margins
                    metaKey="_margins"
                    defaultMargins={ getSetting( 'defaultMargins' ) }
                    marginTypesSupported={ getSetting( 'marginTypesSupported' ) }
                />
            </PanelRow>
        </PluginDocumentSettingPanel>
    )
};

registerPlugin( 'storeabill-margins-panel', { render: MarginsPanel, icon: '' } );