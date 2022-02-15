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
const {couponsCategoryTexts} = acfwfBlocksi18n;

// #endregion [Variables]

// #region [BlockData] =================================================================================================

export default {
  name: 'acfw/coupons-category',

  settings: {
    title: couponsCategoryTexts.title,
    icon: "tickets-alt",
    category: "advancedcoupons",
    keywords: ["coupon", "advanced", "category"],
    decription: couponsCategoryTexts.description,
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

      categories: {
        type: 'array',
        default: [],
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