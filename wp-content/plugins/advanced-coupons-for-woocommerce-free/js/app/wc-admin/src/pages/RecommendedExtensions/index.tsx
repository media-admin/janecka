// #region [Imports] ===================================================================================================

// @ts-ignore
import {Card, CardHeader, CardBody} from "@wordpress/components"; 

import {sharedProps, recommendExtensions} from "../../externals/acfw-wc-admin";
import "./index.scss";

// #endregion [Imports]

// #region [Variables] =================================================================================================

const postForm = document.getElementById( 'posts-filter' );

// #endregion [Variables]

// #region [Components] ===================================================================================================

const RecommendedExtensions = () => {

  const {title, description} = recommendExtensions;
  const {upgradePremium, premiumLink, bonusText} = sharedProps;

  return (
    <div className={`acfw-recommend-extensions-upsell ${!postForm ? 'max-width' : ''}`}>
      <Card title={title}>
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

export default RecommendedExtensions;

// #endregion [Components]