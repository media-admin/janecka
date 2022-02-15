import { withSelect, withDispatch, select } from '@wordpress/data';
import { _x } from '@wordpress/i18n';
import { compose } from '@wordpress/compose';
import { get, isEmpty } from 'lodash';
import { PanelRow, TextControl, Button, SelectControl, FormTokenField } from '@wordpress/components';
import { getSetting } from '@storeabill/settings';

function MainPanelContent( {
	title,
	onUpdateTitle,
} ) {

	const onImportLayout = function( documentType ) {
		const answer = window.confirm( _x( 'Are your sure that you want to import the layout? Your current layout will be overridden partially.', 'storeabill-core', 'storeabill' ) );

		if ( answer ) {
			window.location.href = getSetting( 'mergeBaseUrl' ) + '&base_document_type=' + documentType;;
		}
	};

	const documentTypes = Object.entries( getSetting( 'documentTypes' ) ).map( ( data ) => {
		return {
			'label': data[1],
			'value': data[0]
		};
	} );

	return (
		<>
		{ ! getSetting( 'isFirstPage' ) &&
			<PanelRow className="sab-document-description">
				<TextControl
					label={ _x( 'Description', 'storeabill-core', 'storeabill' ) }
					value={ title }
					onChange={ onUpdateTitle }
					type="text"
				/>
			</PanelRow>
		}
		{ ! getSetting( 'isFirstPage' ) &&
			<PanelRow className="sab-document-merge">
				<SelectControl
					label={ _x( 'Import Layout', 'storeabill-core', 'storeabill' ) }
					value={ '' }
					onChange={ onImportLayout }
					help={ _x( 'Import an existing active layout from a different document type into your current layout.', 'storeabill-core', 'storeabill' ) }
					options={ [
						{ value: '', label: _x( 'Select a document type', 'storeabill-core', 'storeabill' ), disabled: true },
						...documentTypes
					] }
				/>
			</PanelRow>
		}
		{ getSetting( 'isFirstPage' ) &&
			<PanelRow className="sab-document-description">
				<span>{ getSetting( 'title' ) }</span>
			</PanelRow>
		}
		{ getSetting( 'linkedEditLink' ) &&
			<PanelRow className="sab-document-linked">
				{ getSetting( 'isFirstPage' ) &&
					<Button
						isSecondary={ true }
						isSmall={ true }
						href={ getSetting( 'linkedEditLink' ) }
					>
						{ _x( 'Manage default page', 'storeabill-core', 'storeabill' ) }
					</Button>
				}
				{ ! getSetting( 'isFirstPage' ) &&
				<Button
					isSecondary={ true }
					isSmall={ true }
					href={ getSetting( 'linkedEditLink' ) }
					target="_blank"
				>
					{ _x( 'Manage first page', 'storeabill-core', 'storeabill' ) }
				</Button>
				}
			</PanelRow>
		}
		{ ! isEmpty( getSetting( 'lineItemTypes' ) && getSetting( 'lineItemTypes' ).length > 1 ) &&
			<PanelRow className="sab-document-line-item-types">
				<span>{ _x( 'Line Item Types', 'storeabill-core', 'storeabill' ) }</span>
				<span>
				{
					Object.values( getSetting( 'lineItemTypes' ) ).map( function( itemType ) {
						return (
							<span key={ itemType } className="sab-label sab-label-light">{ itemType }</span>
						);
					} )
				}
				</span>
			</PanelRow>
		}
		</>
	);
}

const applyWithSelect = withSelect( ( select ) => {

	const { getEditedPostAttribute } = select( 'core/editor' );
	const title = getEditedPostAttribute( 'title' );

	return {
		title: title
	};
} );


const applyWithDispatch = withDispatch( ( dispatch, { metaKey } ) => {

	const { editPost } = dispatch( 'core/editor' );

	return {
		onUpdateTitle: ( title ) => {
			editPost( { title: title } );
		}
	}
} );

export default compose(
	applyWithSelect,
	applyWithDispatch,
)( MainPanelContent );