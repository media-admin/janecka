// #region [Imports] ===================================================================================================

// Libs
import cloneDeep from "lodash/cloneDeep";

// Types
import { ISection } from "../../types/section";

// Actions
import {
  ISetStoreSectionsActionPayload,
  ISetStoreSectionActionPayload,
  IToggleModuleSectionActionPayload,
  ESectionActionTypes,
} from "../actions/section";

// #endregion [Imports]

// #region [Reducer] ===================================================================================================

export default (
  sections: ISection[] = [],
  action: { type: string; payload: any }
) => {
  switch (action.type) {
    case ESectionActionTypes.SET_STORE_SECTIONS: {
      const { data } = action.payload as ISetStoreSectionsActionPayload;
      return data;
    }

    case ESectionActionTypes.SET_STORE_SECTION: {
      const { data } = action.payload as ISetStoreSectionActionPayload;
      const idx = sections.findIndex((i) => i.id === data.id);

      if (idx < 0) return sections;

      const clonedSections = cloneDeep(sections);

      clonedSections[idx] = { ...clonedSections[idx], ...data };

      return clonedSections;
    }

    case ESectionActionTypes.TOGGLE_MODULE_SECTION: {
      const {
        module,
        show,
      } = action.payload as IToggleModuleSectionActionPayload;
      const idx = sections.findIndex((i) => i.module && i.module === module);

      if (idx < 0) return sections;

      const clonedSections = cloneDeep(sections);

      clonedSections[idx].show = show;

      return clonedSections;
    }

    default:
      return sections;
  }
};

// #endregion [Reducer]
