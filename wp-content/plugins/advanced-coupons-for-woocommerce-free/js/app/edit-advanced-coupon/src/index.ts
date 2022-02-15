import cart_conditions_module_events from "./cart_conditions/index";
import bogo_deals_module_events from "./bogo_deals/index";
import toggle_fields_events from "./toggle_fields";
import upsell_events from "./upsell";
import helpLinkRegisterEvents, { generateHelpLinks } from "./help_modal/index";

import "./assets/styles/index.scss";

declare var jQuery: any;
declare var acfw_edit_coupon: any;

const { modules, upsell } = acfw_edit_coupon;

jQuery(document).ready(($: any) => {
  if (modules.indexOf("acfw_cart_conditions_module") > -1)
    cart_conditions_module_events();

  if (modules.indexOf("acfw_bogo_deals_module") > -1)
    bogo_deals_module_events();

  if (upsell) upsell_events();

  toggle_fields_events();

  generateHelpLinks();
  helpLinkRegisterEvents();
});
