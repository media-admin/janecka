// #region [Imports] ===================================================================================================

// Libraries
import React from "react";
import ReactDOM from "react-dom";
import { Provider } from "react-redux";
import { all, put, call, takeEvery } from "redux-saga/effects";
import createSagaMiddleware from "redux-saga";
import {
  BrowserRouter,
  Route,
  useLocation,
  useHistory,
  Link,
  Redirect,
} from "react-router-dom";
import * as antd from "antd";
import * as antdIcons from "@ant-design/icons";
import {
  bindActionCreators,
  createStore,
  combineReducers,
  applyMiddleware,
} from "redux";
import { connect } from "react-redux";
import * as lodash from "lodash";
import moment from "moment";

// Store
import initializeStore from "./store";

// CSS
import "antd/dist/antd.css";
import "./index.scss";

// Pages
import App from "./pages/App";

// Components
import CouponsNav from "./components/CouponsNav";

// Helpers
// import axiosInstance from "./helpers/axios";
import { getPathPrefix, validateURL } from "./helpers/utils";

// Actions
import { PageActions } from "./store/actions/page";

// #endregion [Imports]

// #region [Variables] =================================================================================================

declare var acfwpElements: any;

const pathPrefix = getPathPrefix();

// Initialize redux store.
const store = initializeStore();

// if ACFWP is active, then we export our React instance and other required packages.
const exports = {
  element: React,
  dom: ReactDOM,
  router: { BrowserRouter, Route, useLocation, useHistory, Link, Redirect },
  redux: {
    bindActionCreators,
    createStore,
    combineReducers,
    applyMiddleware,
    createSagaMiddleware,
    connect,
    sagaEffects: { all, put, call, takeEvery },
  },
  lodash,
  antd,
  antdIcons,
  moment,
  // axiosInstance,
  pathPrefix,
  validateURL,
  appStore: {
    Provider: Provider,
    store: store,
    storeActions: { ...PageActions },
  },
};

acfwpElements = {
  ...acfwpElements,
  ...exports,
};

// #endregion [Variables]

// #region [Component] =================================================================================================

// Render main app.
document.querySelectorAll("#acfw_admin_app").forEach((domContainer: any) => {
  ReactDOM.render(
    <Provider store={store}>
      <BrowserRouter>
        <Route path={`${pathPrefix}admin.php`} component={App} />
      </BrowserRouter>
    </Provider>,
    domContainer
  );
});

// Replace Advanced Coupons navigation in WP admin sidebar with a React app equivalent.
document
  .querySelectorAll("#toplevel_page_acfw-admin")
  .forEach((domContainer: any) => {
    ReactDOM.render(
      <Provider store={store}>
        <BrowserRouter>
          <Route path={`${pathPrefix}admin.php`} component={CouponsNav} />
        </BrowserRouter>
      </Provider>,
      domContainer
    );
  });

// #endregion [Component]
