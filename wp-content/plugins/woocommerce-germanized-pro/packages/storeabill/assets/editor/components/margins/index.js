import { withSelect, withDispatch } from '@wordpress/data';
import {__, _x} from '@wordpress/i18n';
import { compose } from '@wordpress/compose';
import { get, includes } from 'lodash';
import { TextControl } from '@wordpress/components';
import { formatMargins } from '@storeabill/settings';

/**
 * Internal dependencies
 */
import './editor.scss';

function Margins( {
    metaKey,
    margins,
    onUpdateMargins,
    defaultMargins,
    marginTypesSupported
} ) {

    const updateMargin = ( margin, type ) => {
        margins[ type ] = margin;

        onUpdateMargins( margins );
    };

    const marginTypeSupported = ( type ) => {
        let supported = marginTypesSupported ? marginTypesSupported : [ 'left', 'top', 'bottom', 'center' ];

        return ( includes( supported, type ) );
    };

    return (
        <div className="sab-margins-wrapper">
            { marginTypeSupported( 'left' ) &&
                <TextControl
                    label={ _x( 'Left', 'storeabill-core', 'storeabill' ) }
                    value={ margins['left'] }
                    onChange={ ( margin ) => updateMargin( margin, 'left' ) }
                    type="number"
                    step="0.1"
                />
            }
            { marginTypeSupported( 'top' ) &&
                <TextControl
                    label={ _x( 'Top', 'storeabill-core', 'storeabill' ) }
                    value={ margins['top'] }
                    onChange={ ( margin ) => updateMargin( margin, 'top' ) }
                    type="number"
                    step="0.1"
                />
            }
            { marginTypeSupported( 'right' ) &&
                <TextControl
                    label={ _x( 'Right', 'storeabill-core', 'storeabill' ) }
                    value={ margins['right'] }
                    onChange={ ( margin ) => updateMargin( margin, 'right' ) }
                    type="number"
                    step="0.1"
                />
            }
            { marginTypeSupported( 'bottom' ) &&
                <TextControl
                    label={ _x( 'Bottom', 'storeabill-core', 'storeabill' ) }
                    value={ margins['bottom'] }
                    onChange={ ( margin ) => updateMargin( margin, 'bottom' ) }
                    type="number"
                    step="0.1"
                />
            }
        </div>
    );
}

const applyWithSelect = withSelect( ( select, { metaKey, defaultMargins } ) => {

    const { getEditedPostAttribute } = select( 'core/editor' );

    const meta          = getEditedPostAttribute( 'meta' );
    let documentMargins = meta[ metaKey ];
    let newMargins      = formatMargins( documentMargins, defaultMargins, 'edit' );

    return {
        margins: newMargins,
    };
} );


const applyWithDispatch = withDispatch( ( dispatch, { metaKey } ) => {

    const { editPost } = dispatch( 'core/editor' );

    return {
        onUpdateMargins: ( margins ) => {
            let props = {};
            props[ metaKey ] = margins;

            editPost( { meta: props } );
        },
    }
} );

export default compose(
    applyWithSelect,
    applyWithDispatch,
)( Margins );