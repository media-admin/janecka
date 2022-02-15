// #region [Imports] ===================================================================================================

// Libraries
import { createStore, combineReducers, applyMiddleware } from "redux";
import createSagaMiddleware from "redux-saga";

// Types
import { IStore } from "../types/store";

// Reducers
import sectionsReducer from "./reducers/section";
import settingsReducer from "./reducers/setting";
import pageReducer from "./reducers/page";

// Saga
import rootSaga from "./sagas";

// #endregion [Imports]

// #region [Store] =====================================================================================================

/**
 * !Important
 * Comment this function out when releasing for production.
 */
const bindMiddleware = (middlewares: any[]) => {
  const { composeWithDevTools } = require("redux-devtools-extension");
  return composeWithDevTools(applyMiddleware(...middlewares));
};

export default function initializeStore(
  initialState: IStore | undefined = undefined
) {
  const sagaMiddleware = createSagaMiddleware();

  const store = createStore(
    combineReducers({
      sections: sectionsReducer,
      settingValues: settingsReducer,
      page: pageReducer,
    }),
    initialState,
    bindMiddleware([sagaMiddleware])
  );

  sagaMiddleware.run(rootSaga);

  return store;
}

// #endregion [Store]
