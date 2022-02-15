/**
 * External dependencies
 */
import { isEmpty } from 'lodash';

/**
 * WordPress dependencies
 */
import { useInstanceId } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import { BaseControl } from '@wordpress/components';

export default function OptgroupSelect( {
	 help,
	 label,
	 multiple = false,
	 onChange,
	 options = [],
	 className,
	 hideLabelFromVision,
	 defaultOption = {},
	 ...props
 } ) {
	const instanceId = useInstanceId( OptgroupSelect );
	const id = `inspector-select-control-${ instanceId }`;
	const onChangeValue = ( event ) => {
		if ( multiple ) {
			const selectedOptions = [ ...event.target.options ].filter(
				( { selected } ) => selected
			);
			const newValues = selectedOptions.map( ( { value } ) => value );
			onChange( newValues );
			return;
		}
		onChange( event.target.value );
	};

	// Disable reason: A select with an onchange throws a warning

	/* eslint-disable jsx-a11y/no-onchange */
	return (
		! isEmpty( options ) && (
			<BaseControl
				label={ label }
				hideLabelFromVision={ hideLabelFromVision }
				id={ id }
				help={ help }
				className={ className }
			>
				<select
					id={ id }
					className="components-select-control__input"
					onChange={ onChangeValue }
					aria-describedby={ !! help ? `${ id }__help` : undefined }
					multiple={ multiple }
					{ ...props }
				>
					{ defaultOption && (
						<option disabled="disabled" value={ defaultOption.value }>{ defaultOption.label }</option>
					) }
					{ options.map( ( group, index ) => (
						<optgroup
							label={ group.label }
							key={ `${ group.label }-${ group.value }-${ index }` }
						>
							{ group.children.map( ( option, optionIndex ) => (
								<option
									key={ `${ option.label }-${ option.value }-${ optionIndex }` }
									value={ option.value }
									disabled={ option.disabled }
								>
									{ option.label }
								</option>
							) ) }
						</optgroup>
					) ) }
				</select>
			</BaseControl>
		)
	);
	/* eslint-enable jsx-a11y/no-onchange */
}
