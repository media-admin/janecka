import { remove_leading_zeros } from "../helper";
import {
  toggle_editing_mode,
  toggle_block_data_type,
  forceApplyToSameProductForEachProductBase,
} from "./toggles";
import {
  save_bogo_deals,
  clear_bogo_deals,
  update_combined_products_list,
  update_any_products_trigger_data,
  update_any_products_apply_data,
  save_on_coupon_publish,
} from "./save";
import {
  trigger_add_table_row_form,
  trigger_edit_table_row_form,
  cancel_add_edit_table_row,
  add_edit_table_row,
  product_table_remove_product,
} from "./table";

declare var jQuery: any;
declare var acfw_edit_coupon: any;

const $: any = jQuery;
const module_block: HTMLElement = document.querySelector("#acfw_bogo_deals");

/**
 * Export functions to be accessed by ACFWP.
 */
acfw_edit_coupon = {
  ...acfw_edit_coupon,
  bogo_toggle_editing_mode: toggle_editing_mode,
};

/**
 * Add free products module events script.
 *
 * @since 1.0.0
 */
export default function bogo_deals_module_events(): void {
  $(module_block).on(
    "click",
    ".acfw-styled-table tfoot .add-table-row",
    trigger_add_table_row_form
  );
  $(module_block).on(
    "click",
    ".acfw-styled-table td.actions a.edit",
    trigger_edit_table_row_form
  );
  $(module_block).on(
    "click",
    "tr.add-edit-form .actions .cancel",
    cancel_add_edit_table_row
  );
  $(module_block).on(
    "click",
    "tr.add-edit-form .actions .condition-product-add",
    add_edit_table_row
  );
  $(module_block).on(
    "click",
    ".acfw-styled-table td.actions a.remove",
    product_table_remove_product
  );
  $(module_block).on("click", "button#save-bogo-deals", save_bogo_deals);
  $(module_block).on("click", "button#clear-bogo-deals", clear_bogo_deals);
  $(module_block).on(
    "change acfw_load",
    "select#bogo-condition-type,select#bogo-deals-type",
    toggle_block_data_type
  );
  $(module_block).on(
    "change acfw_fetch_data",
    ".combined-products-form select,.combined-products-form input",
    update_combined_products_list
  );
  $(module_block).on(
    "change acfw_fetch_data",
    ".any-products-trigger-form input",
    update_any_products_trigger_data
  );
  $(module_block).on(
    "change acfw_fetch_data",
    ".any-products-apply-form input,.any-products-apply-form select",
    update_any_products_apply_data
  );
  $(module_block).on("change", ".condition-quantity", restrict_quantity_value);
  $(module_block).on("blur", ".wc_input_price", remove_leading_zeros);
  $(module_block).css("min-height", $("#coupon_options").height());
  $(module_block).on("change", "input[name='bogo_type']", trigger_toggle);
  $(module_block).on(
    "click",
    ".acfw-styled-table .actions .remove",
    trigger_toggle
  );
  $(module_block)
    .find(".notice-option")
    .on("change", "input,textarea,select", trigger_toggle);
  $("form#post").on("submit", save_on_coupon_publish);

  $(module_block)
    .find("select#bogo-condition-type,select#bogo-deals-type")
    .trigger("acfw_load");

  $("#woocommerce-coupon-data").on(
    "change acfw_load",
    "select#discount_type",
    toggleBogoInterface
  );

  $("#woocommerce-coupon-data select#discount_type").trigger("acfw_load");
}

function toggleBogoInterface() {
  const $discountType = $(this);
  const $general = $discountType.closest("#general_coupon_data");
  const $inputs = $general.find("p.form-field input");

  if ("acfw_bogo" === $discountType.val()) {
    $general.addClass("acfw-bogo-type");
    $("#acfw_bogo_deals").show();

    // disable fields under general tab that cannot be used in conjunction with BOGO
    for (let x = 0; x <= $inputs.length; x++) {
      if (
        ![
          "expiry_date",
          "_acfw_coupon_sort_priority",
          "coupon_amount", // we don't disable the coupon_amount field as it will trigger a PHP notice log in WC
        ].includes($($inputs[x]).prop("name"))
      ) {
        $($inputs[x]).prop("disabled", true);
      }
    }
  } else {
    $general.removeClass("acfw-bogo-type");
    $("#acfw_bogo_deals").hide();

    // reenable fields under general tab when discount type is not BOGO anymore
    for (let x = 0; x <= $inputs.length; x++) {
      if (
        ![
          "expiry_date",
          "_acfw_coupon_sort_priority",
          "coupon_amount",
        ].includes($($inputs[x]).prop("name"))
      ) {
        $($inputs[x]).prop("disabled", false);
      }
    }
  }
}

/**
 * Trigger toggle editing mode.
 *
 * @since 1.0.0
 */
function trigger_toggle(): void {
  toggle_editing_mode(true);
}

/**
 * Restrict condition quantity value.
 *
 * @since 1.0.0
 */
function restrict_quantity_value() {
  const $field: JQuery = jQuery(this);

  if ($field.val() < 1) $field.val(1);
}
