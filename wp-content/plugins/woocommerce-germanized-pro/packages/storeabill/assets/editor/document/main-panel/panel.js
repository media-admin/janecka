import { withSelect, withDispatch, select } from '@wordpress/data';
import { _x } from '@wordpress/i18n';
import { compose } from '@wordpress/compose';
import { get, isEmpty, includes, remove, cloneDeep } from 'lodash';
import { PanelRow, TextControl, Button, SelectControl, ToggleControl, PanelHeader, Tooltip } from '@wordpress/components';
import { getSetting } from '@storeabill/settings';

function MainPanelContent( {
	title,
    itemTypes,
	onUpdateTitle,
	onUpdateLineItemTypes
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
		{ ! getSetting( 'isFirstPage' ) && ! isEmpty( getSetting( 'availableLineItemTypes' ) ) &&
			<>
				<PanelRow className="sab-document-line-item-types">
					<span className="sab-title">{ _x( 'Additional Line Item Types', 'storeabill-core', 'storeabill' ) }</span>
				</PanelRow>
				<PanelRow className="sab-document-line-item-types">
					<span className="sab-help">{ _x( 'Please make sure to check your total types after updating line item types as the subtotal might change.', 'storeabill-core', 'storeabill' ) }</span>
				</PanelRow>
			</>
		}
		{ ! getSetting( 'isFirstPage' ) && ! isEmpty( getSetting( 'availableLineItemTypes' ) ) &&
			Object.entries( getSetting( 'availableLineItemTypes' ) ).map( ( data ) => {
				return (
					<PanelRow className={"sab-document-line-item-types-" + data[0]} key={ data[0] }>
						<ToggleControl
							label={ data[1] }
							checked={ includes( itemTypes, data[0] ) }
							onChange={ ( isChecked ) => {
								let newItemTypes = cloneDeep( itemTypes );

								if ( isChecked && ! includes( newItemTypes, data[0] ) ) {
									newItemTypes.push( data[0] );
								} else if ( ! isChecked && includes( newItemTypes, data[0] ) ) {
									remove( newItemTypes, function( el ) {
										return el === data[0];
									} );
								}
								return onUpdateLineItemTypes( newItemTypes );
							} }
						/>
					</PanelRow>
				)
			} )
		}
		</>
	);
}

const applyWithSelect = withSelect( ( select ) => {

	const { getEditedPostAttribute } = select( 'core/editor' );
	const title = getEditedPostAttribute( 'title' );
	const meta  = getEditedPostAttribute( 'meta' );
	const newItemTypes = meta['_line_item_types'] ? meta['_line_item_types'] : getSetting( 'lineItemTypes' );

	return {
		title: title,
		itemTypes: newItemTypes
	};
} );


const applyWithDispatch = withDispatch( ( dispatch, { metaKey } ) => {

	const { editPost } = dispatch( 'core/editor' );

	return {
		onUpdateTitle: ( title ) => {
			editPost( { title: title } );
		},
		onUpdateLineItemTypes: ( lineItemTypes ) => {
			editPost( { meta: { '_line_item_types': lineItemTypes } } );
		},
	}
} );

export default compose(
	applyWithSelect,
	applyWithDispatch,
)( MainPanelContent );