import { select, dispatch } from '@wordpress/data';
import domReady from '@wordpress/dom-ready';
import { PanelRow, TextControl } from '@wordpress/components';

import { _x } from '@wordpress/i18n';

import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { registerPlugin } from '@wordpress/plugins';

import { getSetting } from '@storeabill/settings';

import MainPanelContent from './panel';
import './editor.scss';

const MainPanel = () => {
	const label = getSetting( 'documentTypeTitle' ) + ' ' + ( getSetting( 'isFirstPage' ) ? _x( 'First page', 'storeabill-core', 'storeabill' ) : _x( 'Default', 'storeabill-core', 'storeabill' ) );

	return (
		<PluginDocumentSettingPanel
			name="sab-document-main"
			title={<>{ _x( 'Template', 'storeabill-core', 'storeabill') } <span className="sab-label">{ label }</span></>}
			className="sab-document-main"
		>
			<MainPanelContent />
		</PluginDocumentSettingPanel>
	)
};

registerPlugin( 'storeabill-main-panel', { render: MainPanel, icon: '' } );

domReady(() => {
	const panelName = 'storeabill-main-panel/sab-document-main';

	if ( ! select( 'core/edit-post' ).isEditorPanelOpened( panelName ) ) {
		// Open panel by default
		dispatch( 'core/edit-post').toggleEditorPanelOpened( panelName );
	}
});