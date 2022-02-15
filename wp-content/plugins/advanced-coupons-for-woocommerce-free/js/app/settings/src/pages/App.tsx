// #region [Imports] ===================================================================================================

// Libraries
import React, { useEffect } from "react";
import { useLocation } from "react-router-dom";
import { bindActionCreators, Dispatch } from "redux";
import { connect } from "react-redux";

// Pages
import Settings from "./Settings";
import License from "./License";
import About from "./About";
import PremiumUpsell from "./Premium";
import Help from "./Help";

// Actions
import { PageActions } from "../store/actions/page";

// Types
import { IStore } from "../types/store";

// Helpers
import axiosInstance from "../helpers/axios";

// #endregion [Imports]

// #region [Variables] =================================================================================================

declare var acfwpElements: any;

const { LicensePremium } = acfwpElements;
const is_acfwp_active = parseInt(acfwpElements.is_acfwp_active);
const { setStorePage } = PageActions;

acfwpElements.axiosInstance = axiosInstance;

// #endregion [Variables]

// #region [Interfaces]=================================================================================================

interface IActions {
  setStorePage: typeof setStorePage;
}

interface IProps {
  page: string;
  actions: IActions;
}

// #endregion [Interfaces]

// #region [Component] =================================================================================================

const App = (props: IProps) => {
  const { page, actions } = props;

  const urlParams = new URLSearchParams(useLocation().search);
  const appPage = urlParams.get("page");

  acfwpElements.appPage = appPage;

  useEffect(() => {
    actions.setStorePage({ data: appPage ? appPage : "" });
  }, [appPage, actions]);

  return (
    <div className="app">
      {page === "acfw-settings" ? <Settings /> : null}

      {page === "acfw-license" && is_acfwp_active ? <LicensePremium /> : null}

      {page === "acfw-license" && !is_acfwp_active ? <License /> : null}

      {page === "acfw-premium" ? <PremiumUpsell /> : null}

      {page === "acfw-help" ? <Help /> : null}

      {page === "acfw-about" ? <About /> : null}
    </div>
  );
};

const mapStateToProps = (store: IStore) => ({ page: store.page });

const mapDispatchToProps = (dispatch: Dispatch) => ({
  actions: bindActionCreators({ setStorePage }, dispatch),
});

export default connect(mapStateToProps, mapDispatchToProps)(App);

// #endregion [Component]
