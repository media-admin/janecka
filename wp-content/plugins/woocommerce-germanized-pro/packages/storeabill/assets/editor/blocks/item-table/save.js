/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { InnerBlocks, getColorClassName, getColorObjectByColorValue } from '@wordpress/block-editor';
import { getBorderClasses } from '@storeabill/components/border-select';

export default function save( { attributes } ) {

    const {
        borderColor,
        className,
        borders
    } = attributes;

    const borderColorClass = getColorClassName(
        'border-color',
        borderColor
    );

    const borderClasses = getBorderClasses( borders );

    const classNames = classnames( className, {
        'has-border-color': borderColor,
        [ borderColorClass ]: borderColorClass,
    }, borderClasses );

    return (
        <div className={ classNames }>
            <InnerBlocks.Content />
        </div>
    );
}
