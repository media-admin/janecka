// #region [Imports] ===================================================================================================

// Actions
import {
    ISetStorePageActionPayload,
    EPageActionTypes
} from "../actions/page";

// #endregion [Imports]

// #region [Reducer] ===================================================================================================

export default (
    page: string = '',
    action: { type: string; payload: any }
) => {

    switch (action.type) {

        case EPageActionTypes.SET_STORE_PAGE: {
            const { data } = action.payload as ISetStorePageActionPayload;
            return data;
        }

        default:
            return page;
    }
};

// #endregion [Reducer]
