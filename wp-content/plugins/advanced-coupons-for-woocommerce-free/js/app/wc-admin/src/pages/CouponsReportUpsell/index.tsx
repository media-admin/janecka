// #region [Imports] ===================================================================================================

// @ts-ignore
import {Card, CardHeader, CardBody} from "@wordpress/components"; 

import {sharedProps, analyticsUpsell} from "../../externals/acfw-wc-admin";
import "./index.scss";

// #endregion [Imports]

// #region [Components] ================================================================================================

const CouponsReportUpsell = () => {

  const {title, description} = analyticsUpsell;
  const {upgradePremium, premiumLink, bonusText} = sharedProps;

  return (
    <div className="acfw-analytics-upsell">
      <Card className="woocommerce-card">
        <CardHeader>
          <h2 className="title">{title}</h2>
        </CardHeader>
        <CardBody>
          <p className="description" dangerouslySetInnerHTML={{__html: description}} />
          <a className="upgrade-btn" href={premiumLink}>{upgradePremium}</a>
          <span className="sub-label" dangerouslySetInnerHTML={{__html: bonusText}} />
        </CardBody>
      </Card>
    </div>
  );
}

export default CouponsReportUpsell;

// #endregion [Components]