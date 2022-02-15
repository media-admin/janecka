// #region [Imports] ===================================================================================================

// Libraries
import { // @ts-ignore
	registerBlockType, // @ts-ignore
	setDefaultBlockName, // @ts-ignore
	setFreeformContentHandlerName, // @ts-ignore
	setUnregisteredTypeHandlerName, // @ts-ignore
	setGroupingBlockName,
} from '@wordpress/blocks'; 

// Blocks
import singleCoupon from "./blocks/single-coupon";
import couponsCategory from "./blocks/coupons-category";
import couponsCustomer from "./blocks/coupons-customer";

// SCSS
import "./index.scss";

// #endregion [Imports]

declare var wp: any;

// #region [RegisterBlocks] ============================================================================================

const registerBlock = (block) => {
  if (!block) {
    return;
  }

  const {name, settings} = block;
  registerBlockType( name, settings);
}

/**
 * Register blocks when the DOM is ready.
 */
wp.domReady(() => {
  const blocks = [singleCoupon, couponsCategory, couponsCustomer];
  blocks.forEach(registerBlock);
})


// #endregion [RegisterBlocks]