import { _x } from "@wordpress/i18n";
import { Toolbar, ToolbarButton, DropdownMenu, ToolbarGroup } from "@wordpress/components";
import { arrowRight, settings } from '@storeabill/icons';
import { isEmpty } from 'lodash';
import { DISCOUNT_TOTAL_TYPES } from '@storeabill/settings';

export function DiscountTotalTypeSelect( props ) {
    const {
        currentType,
        onChange,
        label = _x( 'Discount total type', 'storeabill-core', 'storeabill' )
    } = props;

    if ( isEmpty( DISCOUNT_TOTAL_TYPES ) ) {
        return '';
    }

    const getDiscountTypes = () => {
        const discountTypes = [];

        for ( const [ key, value ] of Object.entries( DISCOUNT_TOTAL_TYPES ) ) {
            discountTypes.push( {
                'type': key,
                'title': value
            } );
        }

        return discountTypes;
    };

    return (
        <ToolbarGroup>
            <DropdownMenu
                icon={ settings }
                label={ label }
                controls={ getDiscountTypes().map( ( control ) => {
                    const { type } = control;
                    const isActive = currentType === type;

                    return {
                        ...control,
                        isActive,
                        icon: isEmpty( control.icon ) ? arrowRight : control.icon,
                        role: 'menuitemradio',
                        onClick: () => onChange( type )
                    };
                } ) }
            />
        </ToolbarGroup>
    );
}