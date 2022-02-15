import { select } from '@wordpress/data';
import domReady from '@wordpress/dom-ready';
import { PanelRow } from '@wordpress/components';

import PDFUpload from '@storeabill/components/pdf-upload';
import { _x } from '@wordpress/i18n';
import { isDocumentTemplate } from '@storeabill/settings';

import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { registerPlugin } from '@wordpress/plugins';
/**
 * Internal dependencies
 */

const BackgroundPanel = () => {
    return (
        <PluginDocumentSettingPanel
            name="sab-document-template"
            title={_x( 'Background', 'storeabill-core', 'storeabill')}
            className="sab-document-template"
        >
            <PanelRow className="sab-document-template-panel">
                <PDFUpload metaKey="_pdf_template_id" />
            </PanelRow>
        </PluginDocumentSettingPanel>
    )
};

registerPlugin( 'storeabill-background-panel', { render: BackgroundPanel, icon: '' } );