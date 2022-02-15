// #region [Imports] ===================================================================================================

// Types
import { ISettingValue } from "../../types/settings";
import { ISection } from "../../types/section";

// #endregion [Imports]

// #region [Action Payloads] ===========================================================================================

export interface ICreateSettingActionPayload {
    data: ISettingValue,
    processingCB?: () => void;
    successCB?: (arg: any) => void;
    failCB?: (arg: any) => void;
}

export interface IUpdateSettingActionPayload {
    data: ISettingValue;
    processingCB?: () => void;
    successCB?: (arg: any) => void;
    failCB?: (arg: any) => void;
}

export interface IDeleteSettingActionPayload {
    id: string;
    processingCB?: () => void;
    successCB?: (arg: any) => void;
    failCB?: (arg: any) => void;
}

export interface IReadSettingsActionPayload {
    processingCB?: () => void;
    successCB?: (arg: any) => void;
    failCB?: (arg: any) => void;
}

export interface IRehydrateStoreSettingsActionPayload {
    data?: ISection;
    processingCB?: () => void;
    successCB?: (arg: any) => void;
    failCB?: (arg: any) => void;
}

export interface IRehydrateStoreSettingActionPayload {
    id: string;
    data?: ISettingValue;
    processingCB?: () => void;
    successCB?: (arg: any) => void;
    failCB?: (arg: any) => void;
}

export interface ISetStoreSettingsActionPayload {
    data: ISettingValue[];
}

export interface ISetStoreSettingActionPayload {
    data: ISettingValue;
}

// #endregion [Action Payloads]

// #region [Action Types] ==============================================================================================

export enum ESettingActionTypes {
    CREATE_SETTING = "CREATE_SETTING",
    UPDATE_SETTING = "UPDATE_SETTING",
    DELETE_SETTING = "DELETE_SETTING",
    READ_SETTINGS = "READ_SETTINGS",
    REHYDRATE_STORE_SETTINGS = "REHYDRATE_STORE_SETTINGS",
    REHYDRATE_STORE_SETTING = "REHYDRATE_STORE_SETTING",
    SET_STORE_SETTINGS = "SET_STORE_SETTINGS",
    SET_STORE_SETTING = "SET_STORE_SETTING"
}


// #endregion [Action Types]

// #region [Action Creators] ===========================================================================================

export const SettingActions = {
    createSetting: (payload: ICreateSettingActionPayload) => ({
        type: ESettingActionTypes.CREATE_SETTING,
        payload
    }),
    updateSetting: (payload: IUpdateSettingActionPayload) => ({
        type: ESettingActionTypes.UPDATE_SETTING,
        payload
    }),
    deleteSetting: (payload: IDeleteSettingActionPayload) => ({
        type: ESettingActionTypes.DELETE_SETTING,
        payload
    }),
    readSettings: (payload: IReadSettingsActionPayload) => ({
        type: ESettingActionTypes.READ_SETTINGS,
        payload
    }),
    rehydrateStoreSettings: (payload: IRehydrateStoreSettingsActionPayload) => ({
        type: ESettingActionTypes.REHYDRATE_STORE_SETTINGS,
        payload
    }),
    rehydrateStoreSetting: (payload: IRehydrateStoreSettingActionPayload) => ({
        type: ESettingActionTypes.REHYDRATE_STORE_SETTING,
        payload
    }),
    setStoreSettingItems: (payload: ISetStoreSettingsActionPayload) => ({
        type: ESettingActionTypes.SET_STORE_SETTINGS,
        payload
    }),
    setStoreSettingItem: (payload: ISetStoreSettingActionPayload) => ({
        type: ESettingActionTypes.SET_STORE_SETTING,
        payload
    })
};

// #endregion [Action Creators]
