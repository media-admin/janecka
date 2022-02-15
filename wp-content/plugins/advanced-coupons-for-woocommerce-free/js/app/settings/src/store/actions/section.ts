// #region [Imports] ===================================================================================================

// Types
import { ISection } from "../../types/section";

// #endregion [Imports]

// #region [Action Payloads] ===========================================================================================

export interface IReadSectionsActionPayload {
  id?: string | null;
  processingCB?: () => void;
  successCB?: (arg: any) => void;
  failCB?: (arg: any) => void;
}

export interface IReadSectionActionPayload {
  id: string | null;
  processingCB?: () => void;
  successCB?: (arg: any) => void;
  failCB?: (arg: any) => void;
}

export interface ISetStoreSectionsActionPayload {
  data: ISection[];
}

export interface ISetStoreSectionActionPayload {
  data: ISection;
}

export interface IToggleModuleSectionActionPayload {
  module: string;
  show: boolean;
}

// #endregion [Action Payloads]

// #region [Action Types] ==============================================================================================

export enum ESectionActionTypes {
  READ_SECTIONS = "READ_SECTIONS",
  READ_SECTION = "READ_SECTION",
  SET_STORE_SECTIONS = "SET_STORE_SECTIONS",
  SET_STORE_SECTION = "SET_STORE_SECTION",
  TOGGLE_MODULE_SECTION = "TOGGLE_MODULE_SECTION",
}

// #endregion [Action Types]

// #region [Action Creators] ===========================================================================================

export const SectionActions = {
  readSections: (payload: IReadSectionsActionPayload) => ({
    type: ESectionActionTypes.READ_SECTIONS,
    payload,
  }),
  readSection: (payload: IReadSectionActionPayload) => ({
    type: ESectionActionTypes.READ_SECTION,
    payload,
  }),
  setStoreSectionItems: (payload: ISetStoreSectionsActionPayload) => ({
    type: ESectionActionTypes.SET_STORE_SECTIONS,
    payload,
  }),
  setStoreSectionItem: (payload: ISetStoreSectionActionPayload) => ({
    type: ESectionActionTypes.SET_STORE_SECTION,
    payload,
  }),
  toggleModuleSection: (payload: IToggleModuleSectionActionPayload) => ({
    type: ESectionActionTypes.TOGGLE_MODULE_SECTION,
    payload,
  }),
};

// #endregion [Action Creators]
