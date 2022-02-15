// #region [Imports] ===================================================================================================

// Libraries
import React from "react";
import { bindActionCreators, Dispatch } from "redux";
import { connect } from "react-redux";
import {useHistory } from "react-router-dom";

// SCSS
import "./index.scss";

// Actions
import { PageActions } from "../../store/actions/page";

// Types
import { IStore } from "../../types/store";

// Helpers
import { getPathPrefix } from "../../helpers/utils";

// #endregion [Imports]

// #region [Variables] =================================================================================================

declare var acfwAdminApp: any;
const { setStorePage } = PageActions;
const pathPrefix = getPathPrefix();

// #endregion [Variables]

// #region [Interfaces]=================================================================================================

interface IActions {
  setStorePage: typeof setStorePage;
}

interface IProps {
  actions: IActions;
}

// #endregion [Interfaces]

// #region [Component] =================================================================================================

const Logo = (props: IProps) => {

  const {actions} = props;
  const {app_pages} = acfwAdminApp;
  const history = useHistory();
  const [premiumPage] = app_pages.filter((p: any) => 'acfw-premium' === p.slug );

  const handleUpgradeClick = () => {
    history.push(`${ pathPrefix }admin.php?page=acfw-premium`);
    actions.setStorePage({ data: 'acfw-premium' });
  };


  return (
    <div className="acfw-logo-div">
      <img className="acfw-logo" src={ acfwAdminApp.logo } alt="acfw logo" />
      {premiumPage ? (
        <button 
          className="acfw-header-upgrade-btn"
          onClick={() => handleUpgradeClick()}
        >
          Upgrade
        </button>
      ) : null}
    </div>
  );
}

const mapStateToProps = (store: IStore) => ({ sections: store.sections });

const mapDispatchToProps = (dispatch: Dispatch) => ({
    actions: bindActionCreators({ setStorePage }, dispatch)
})

export default connect(mapStateToProps, mapDispatchToProps)(Logo);

// #endregion [Component]