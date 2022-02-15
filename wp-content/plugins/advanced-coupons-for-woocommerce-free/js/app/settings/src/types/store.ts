// #region [Imports] ===================================================================================================

import { ISection } from "./section";
import { ISettingValue } from "./settings";

// #endregion [Imports]

// #region [Types] =====================================================================================================

export interface IStore {
    sections: ISection[];
    settingValues: ISettingValue[];
    page: string;
}

// #endregion [Types]