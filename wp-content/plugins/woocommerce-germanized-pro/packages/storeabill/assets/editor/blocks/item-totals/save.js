/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { InnerBlocks, getColorClassName } from '@wordpress/block-editor';

export default function save( { attributes } ) {

    const className = classnames( {} );
    const style = {};

    return (
        <div className={ className ? className : undefined } style={ style }>
            <InnerBlocks.Content />
        </div>
    );
}
