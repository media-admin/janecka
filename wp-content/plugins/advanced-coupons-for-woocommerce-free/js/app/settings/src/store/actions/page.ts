// #region [Action Payloads] ===========================================================================================

export interface ISetStorePageActionPayload {
    data: string;
}

// #endregion [Action Payloads]

// #region [Action Types] ==============================================================================================

export enum EPageActionTypes {
    SET_STORE_PAGE = "SET_STORE_PAGE"
}

// #endregion [Action Types]

// #region [Action Creators] ===========================================================================================

export const PageActions = {
    setStorePage: (payload: ISetStorePageActionPayload) => ({
        type: EPageActionTypes.SET_STORE_PAGE,
        payload
    })
};

// #endregion [Action Creators]