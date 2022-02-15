// #region [Imports] ===================================================================================================

// Libraries
import React, { useContext } from "react";
import { Switch } from "antd";


// Contexts
import {UpsellContext} from "../../UpsellProvider";

// Types
import { ISectionField } from "../../../types/section";
import { isNull } from "lodash";


// #endregion [Imports]

// #region [Interfaces] ================================================================================================

interface IProps {
  field: ISectionField;
}

// #endregion [Interfaces]

// #region [Component] =================================================================================================

const PremiumModule = (props: IProps) => {

  const { id } = props.field;
  const context = useContext(UpsellContext);

  return <Switch 
  key={ id }
  checked={false} 
  defaultChecked={false} 
  onChange={ () => context.setShowModal(true) } 
/>
}

export default PremiumModule

// #endregion [Component]
