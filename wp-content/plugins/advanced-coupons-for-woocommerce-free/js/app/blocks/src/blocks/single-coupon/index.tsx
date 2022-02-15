// #region [Imports] ===================================================================================================

// Components
import SingleCouponBlock from "./block";

// Utils
import sharedAtts from "../../utils/sharedAtts";

// SCSS
import "./index.scss";

// #endregion [Imports]

// #region [Variables] =================================================================================================

declare var acfwfBlocksi18n: any;
const {singleCouponTexts} = acfwfBlocksi18n;

// #endregion [Variables]

// #region [BlockData] =================================================================================================

export default {
  name: 'acfw/single-coupon',

  settings: {
    title: singleCouponTexts.title,
    icon: "tickets-alt",
    category: "advancedcoupons",
    keywords: ["coupon", "advanced"],
    decription: singleCouponTexts.description,
    supports: {
      align: [ 'wide', 'full'],
      html: false,
    },

    example: {
      attributes: {
        isPreview: true,
      },
    },

    attributes: {

      coupon_id: {
        type: 'number',
        default: 0,
      },

      coupon_code: {
        type: 'string',
        default: '',
      },

      ...sharedAtts
    },

    edit(props) {
      return <SingleCouponBlock {...props} />;
    },
    
    /**
     * Save nothing; rendered by server.
     */
    save() {
      return null;
    }
  },
}

// #endregion [BlockData]