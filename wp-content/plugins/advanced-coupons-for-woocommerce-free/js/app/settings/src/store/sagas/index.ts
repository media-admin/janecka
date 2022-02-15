// #region [Imports] ===================================================================================================

// Libraries
import { all } from "redux-saga/effects";

// Sagas
import * as section from "./section";
import * as setting from "./setting";

// #endregion [Imports]

// #region [Root Saga] =================================================================================================

export default function* rootSaga() {
    yield all([...section.actionListener, ...setting.actionListener]);
}

// #endregion [Root Saga]
