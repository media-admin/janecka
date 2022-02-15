// #region [Imports] ===================================================================================================

// Components
import Block from "./block";

// Utils
import sharedAtts, {layoutAtts} from "../../utils/sharedAtts";

// SCSS
import "./index.scss";

// #endregion [Imports]

// #region [Variables] =================================================================================================

declare var acfwfBlocksi18n: any;
const {couponsCustomerTexts} = acfwfBlocksi18n;

// #endregion [Variables]

// #region [BlockData] =================================================================================================

export default {
  name: 'acfw/coupons-customer',

  settings: {
    title: couponsCustomerTexts.title,
    icon: "tickets-alt",
    category: "advancedcoupons",
    keywords: ["coupon", "advanced", "customer", "user"],
    decription: couponsCustomerTexts.title,
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

      display_type: {
        type: 'string',
        default: '',
      },

      ...layoutAtts,

      ...sharedAtts
    },

    edit(props) {
      return <Block {...props} />;
    },

    /**
     * Save nothing; rendered by server.
     */
    save() {
      return null;
    }
  }
}

// #endregion [BlockData]