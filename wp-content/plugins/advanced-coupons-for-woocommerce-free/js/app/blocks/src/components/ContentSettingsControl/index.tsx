// #region [Imports] ===================================================================================================

// Libraries
import { ToggleControl } from '@wordpress/components';

// Types
import {IContentVisibility, IAttributes} from "../../types/settings";

// #endregion [Imports]

// #region [Variables] =================================================================================================

declare var acfwfBlocksi18n: any;
const {
  contentDisplaySettings
} = acfwfBlocksi18n;

// #endregion [Variables]

// #region [Interfaces] ================================================================================================

interface IProps {
  onChange: (IAttributes) => void;
  settings: IContentVisibility;
}

// #endregion [Interfaces]

// #region [Component] =================================================================================================

const ContentSettingsControl = (props: IProps) => {
  const {onChange, settings} = props;
  const {discount_value, description, usage_limit, schedule} = settings;

  return (
    <>
      <ToggleControl 
        label={contentDisplaySettings.displayDiscountValue}
        checked={discount_value}
        onChange={ () => onChange({...settings, discount_value: !discount_value}) }
      />
      <ToggleControl 
        label={contentDisplaySettings.displayDescription}
        checked={description}
        onChange={ () => onChange({...settings, description: !description}) }
      />
      <ToggleControl 
        label={contentDisplaySettings.displayUsageLimit}
        checked={usage_limit}
        onChange={ () => onChange({...settings, usage_limit: !usage_limit}) }
      />
      <ToggleControl 
        label={contentDisplaySettings.displaySchedule}
        checked={schedule}
        onChange={ () => onChange({...settings, schedule: !schedule}) }
      />
    </>
  );
}

export default ContentSettingsControl;

// #endregion [Component]
