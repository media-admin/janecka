import { _x } from "@wordpress/i18n";
import { Toolbar, ToolbarButton, DropdownMenu, ToolbarGroup } from "@wordpress/components";
import { borderTop, borderBottom, borderLeft, borderRight, borderClear, borderHorizontal, borderOuter, borderInner } from '@storeabill/icons';
import { isArray, includes, cloneDeep, find } from 'lodash';

const BORDERS = [
    {
        'title' : _x( 'Top', 'storeabill-core', 'storeabill' ),
        'icon'  : borderTop,
        'border': 'top'
    },
    {
        'title' : _x( 'Bottom', 'storeabill-core', 'storeabill' ),
        'icon'  : borderBottom,
        'border': 'bottom'
    },
    {
        'title' : _x( 'Left', 'storeabill-core', 'storeabill' ),
        'icon'  : borderLeft,
        'border': 'left'
    },
    {
        'title' : _x( 'Right', 'storeabill-core', 'storeabill' ),
        'icon'  : borderRight,
        'border': 'right'
    },
    {
        'title' : _x( 'Outer', 'storeabill-core', 'storeabill' ),
        'icon'  : borderOuter,
        'border': 'outer'
    },
    {
        'title' : _x( 'Inner', 'storeabill-core', 'storeabill' ),
        'icon'  : borderInner,
        'border': 'inner'
    },
    {
        'title' : _x( 'Horizontal', 'storeabill-core', 'storeabill' ),
        'icon'  : borderHorizontal,
        'border': 'horizontal'
    },
];

export function BorderSelect( props ) {
    const {
        currentBorders,
        onChange,
        borders = BORDERS,
        isMultiSelect = true,
        label = _x( 'Select border', 'storeabill-core', 'storeabill' )
    } = props;

    let availableBorders = cloneDeep( borders );

    if ( isArray( borders ) && ! ( borders[0].hasOwnProperty( 'title' ) ) ) {
        availableBorders = BORDERS.filter( ( borderElement ) => {
            if ( includes( availableBorders, borderElement.border ) ) {
                return true;
            }
        } );
    }

    function applyOrUnset( border ) {
        let newBorders = cloneDeep( currentBorders );

        if ( ! isMultiSelect ) {
            newBorders = border === currentBorders ? '' : border;

            return onChange( newBorders );
        } else {
            if ( includes( currentBorders, border ) ) {
                newBorders = currentBorders.filter( e => e !== border );
            } else {
                newBorders.push( border );
            }

            return onChange( newBorders );
        }
    }

    if ( ! isMultiSelect ) {
        const currentBorder = currentBorders;

        const activeBorder = find(
            availableBorders,
            ( control ) => control.border === currentBorder
        );

        return (
            <ToolbarGroup>
                <DropdownMenu
                    icon={ activeBorder ? activeBorder.icon : borderClear }
                    label={ label }
                    controls={ availableBorders.map( ( control ) => {
                        const { border } = control;
                        const isActive = currentBorder === border;

                        return {
                            ...control,
                            isActive,
                            role: 'menuitemradio',
                            onClick: () => applyOrUnset( border ),
                        };
                    } ) }
                />
            </ToolbarGroup>
        );
    } else {
        return (
            <Toolbar>
                { availableBorders.map(
                    ( { title, icon, border } ) => {
                        return (
                            <ToolbarButton
                                key={ border }
                                icon={ icon }
                                title={ title }
                                onClick={ () => applyOrUnset( border ) }
                                isActive={ includes( currentBorders, border ) }
                            />
                        )
                    }
                ) }
            </Toolbar>
        );
    }
}

export function getBorderClasses( borders ) {
    return borders.map( ( type ) => {
        return 'has-border-' + type
    } );
}