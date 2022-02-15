// #region [Imports] ===================================================================================================

import CouponsReportUpsell from "./CouponsReportUpsell";
import RecommendedExtensions from "./RecommendedExtensions";
import {Router, Route} from "../router";
import "./index.scss";

// #endregion [Imports]

// #region [Variables] =================================================================================================

const postForm = document.getElementById( 'posts-filter' );

// #endregion [Variables]

// #region [Component] =================================================================================================

const AdminUpsellApp = () => {

  return (
    <div id="acfw-admin-app">
      <Router>
        <Route key="upsell" path="/analytics/coupons" render={() => (<CouponsReportUpsell />)} />
        <Route key="recommended-extensions" path="/marketing" render={() => (<RecommendedExtensions />)} />
      </Router>
      {postForm ? <RecommendedExtensions /> : null}
    </div>
  );
}

export default AdminUpsellApp;

// #endregion [Component]