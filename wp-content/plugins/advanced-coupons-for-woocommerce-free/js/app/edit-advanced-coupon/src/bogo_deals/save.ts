import { toggle_overlay, validateDiscount } from "../helper";
import { toggle_editing_mode } from "./toggles";
import placeholder_table_row_template from "./templates/placeholder_row";

declare var jQuery: any;
declare var acfw_edit_coupon: any;
declare var woocommerce_admin_meta_boxes: any;
declare var ajaxurl: string;
declare var vex: any;

const $: any = jQuery;
const module_block: HTMLElement = document.querySelector("#acfw_bogo_deals");
const { post_id } = woocommerce_admin_meta_boxes;

/**
 * Save BOGO Deals.
 *
 * @since 1.0.0
 */
export function save_bogo_deals() {
  // cancel product table edit form rows.
  $("#acfw_bogo_deals")
    .find(".acfw-styled-table tr.add-edit-form button.cancel")
    .trigger("click");

  const { post_status, upsell } = acfw_edit_coupon;

  const condition_rows: NodeList = module_block.querySelectorAll(
      ".bogo-conditions-block table.acfw-styled-table tbody td.object,.bogo-conditions-block .any-products-trigger-form"
    ),
    deal_rows: NodeList = module_block.querySelectorAll(
      ".bogo-product-deals-block table.acfw-styled-table tbody td.object"
    ),
    overlay: HTMLElement = module_block.querySelector(".acfw-overlay"),
    type_field: HTMLInputElement = module_block.querySelector(
      "input[name='bogo_type']:checked"
    ),
    conditions_type: string = $(module_block)
      .find("#bogo-condition-type")
      .val(),
    deals_type: string = $(module_block).find("#bogo-deals-type").val(),
    $notice_options: JQuery = $(module_block).find(".notice-option");

  const conditions: any = get_data(condition_rows, conditions_type);
  const deals: any = get_data(deal_rows, deals_type);
  const type: string = type_field ? type_field.value : null;

  // error flag variables.
  let isTriggerError: boolean = false;
  let isApplyError: boolean = false;

  // validate any products trigger data.
  if ("any-products" === conditions_type) {
    isTriggerError = conditions?.quantity < 1;
  }

  // validate any products apply data.
  if ("any-products" === deals_type) {
    isApplyError =
      !deals?.discount_type || !deals?.discount_value || deals?.quantity < 1;
  }

  // validate combination products trigger data.
  if ("combination-products" === conditions_type) {
    isTriggerError = !conditions?.products?.length || conditions?.quantity < 1;
  }

  // validate combination products apply data.
  if ("combination-products" === deals_type) {
    isApplyError =
      !deals?.products?.length ||
      !deals?.discount_type ||
      !deals?.discount_value ||
      deals?.quantity < 1;
  }

  if (
    conditions?.length < 1 ||
    deals?.length < 1 ||
    !type ||
    isTriggerError ||
    isApplyError
  ) {
    vex.dialog.alert(acfw_edit_coupon.bogo_deals_save_error_msg);

    // Add save error flag class in #acfw_bogo_deals
    $(module_block).addClass("save-error");
    return;
  }

  if (
    upsell !== undefined &&
    (conditions_type !== "specific-products" ||
      deals_type !== "specific-products")
  ) {
    vex.dialog.alert(acfw_edit_coupon.bogo_deals_save_error_msg);

    // Add save error flag class in #acfw_bogo_deals
    $(module_block).addClass("save-error");
    return;
  }

  // validate discount by type for any products and combination products deal type.
  if (
    ["any-products", "combination-products"].includes(deals_type) &&
    !validateDiscount(deals?.discount_type, deals?.discount_value)
  ) {
    vex.dialog.alert(acfw_edit_coupon.discount_type_value_error_msg);

    // Add save error flag class in #acfw_bogo_deals
    $(module_block).addClass("save-error");
    return;
  }

  const notice_settings = {
    message: $notice_options
      .find("textarea[name='acfw_bogo_notice_message_text']")
      .val(),
    button_text: $notice_options
      .find("input[name='acfw_bogo_notice_button_text']")
      .val(),
    button_url: $notice_options
      .find("input[name='acfw_bogo_notice_button_url']")
      .val(),
    notice_type: $notice_options
      .find("select[name='acfw_bogo_notice_type']")
      .val(),
  };

  toggle_overlay(overlay, "show");

  $(module_block).trigger("save_bogo_deals");

  const dataToSave = {
    conditions: conditions,
    deals: deals,
    conditions_type: conditions_type,
    deals_type: deals_type,
    type: type,
    notice_settings: notice_settings,
  };

  $.post(
    ajaxurl,
    {
      action: "acfw_save_bogo_deals",
      coupon_id: post_id,
      ...dataToSave,
    },
    (response: any) => {
      if (response.status == "success") {
        // replace data in state with latest one.
        $(module_block).data("bogo_deals", dataToSave);

        // if coupon is not published yet, then save it as draft.
        if (post_status !== "publish") {
          if (!$("input[name='post_title']").val())
            $("input[name='post_title']").val("coupon-" + post_id);

          $("select[name='post_status']")
            .append(new Option("Published", "publish"))
            .val("publish");

          $("#publishing-action").append(`
                    <input type="hidden" name="publish" value="Publish">
                `);
          $("input#publish").click();
        }
      } else {
        if (response.error_msg) vex.dialog.alert(response.error_msg);
      }

      $(module_block).find("#clear-bogo-deals").prop("disabled", false);
      toggle_editing_mode(false);
      toggle_overlay(overlay, "hide");
    },
    "json"
  );
}

/**
 * Get BOGO Deals data.
 *
 * @since 1.0.0
 *
 * @param rows
 * @param type
 */
function get_data(rows: NodeList, type: string): any {
  let data = [],
    temp;

  switch (type) {
    case "any-products":
      data = $(rows).closest(".any-products-form").data("anyproducts");
      break;

    case "combination-products":
      data = $(rows).closest("table.combined-products-form").data("combined");
      break;

    case "product-categories":
      rows.forEach((row: HTMLElement) => {
        temp = jQuery(row).data("category");
        if (temp) data.push(temp);
      });
      break;

    case "specific-products":
    default:
      rows.forEach((row: HTMLElement) => {
        temp = jQuery(row).data("product");
        if (temp) data.push(temp);
      });
      break;
  }

  return data;
}

/**
 * Clear BOGO Deals.
 *
 * @since 1.0.0
 */
export function clear_bogo_deals() {
  const $button: JQuery = $(this);

  if (!confirm($button.data("prompt"))) return;

  const condition_rows: HTMLElement = module_block.querySelector(
      ".bogo-conditions-block table.acfw-styled-table tbody"
    ),
    deal_rows: HTMLElement = module_block.querySelector(
      ".bogo-product-deals-block table.acfw-styled-table tbody"
    ),
    overlay: HTMLElement = module_block.querySelector(".acfw-overlay"),
    type_field: HTMLInputElement = module_block.querySelector(
      "input[name='bogo_type']:checked"
    );

  toggle_overlay(overlay, "show");

  $.post(
    ajaxurl,
    {
      action: "acfw_clear_bogo_deals",
      coupon_id: post_id,
      _wpnonce: $button.data("nonce"),
    },
    (response: any) => {
      if (response.status == "success") {
        $(condition_rows).html(placeholder_table_row_template(3));
        $(deal_rows).html(placeholder_table_row_template(4));
        $(type_field).prop("checked", false);

        $button.prop("disabled", true);
        toggle_editing_mode(false);

        // if coupon is not published yet, then save it as draft.
        if ($("#post-status-display").text() != "Published") {
          if (!$("input[name='post_title']").val())
            $("input[name='post_title']").val("coupon-" + post_id);

          $("input#publish").click();
        }
      } else {
        if (response.error_msg) vex.dialog.alert(response.error_msg);
      }

      toggle_overlay(overlay, "hide");
    },
    "json"
  );
}

/**
 * Update combined products list.
 *
 * @since 1.1.0
 */
export function update_combined_products_list() {
  const $table: JQuery = $(this).closest("table.combined-products-form"),
    current_data: any = $table.data("combined"),
    $options: JQuery = $table.find(".wc-product-search option:selected"),
    quantity: number = parseInt(
      $table.find("input.condition-quantity").val() + ""
    ),
    conditions: any = { products: [], quantity: quantity },
    is_deals: string = $table.data("isdeals");

  if (is_deals) {
    const $discount_type: JQuery = $table.find("select.discount_type"),
      $discount_value: JQuery = $table.find("input.discount_value"),
      discount_val: number = parseFloat($discount_value.val().toString());

    if (discount_val < 0 || isNaN(discount_val)) {
      $discount_value.val(current_data ? current_data.discount_value : "");
      vex.dialog.alert(acfw_edit_coupon.fill_form_propery_error_msg);
      return;
    } else {
      conditions.discount_type = $discount_type.val();
      conditions.discount_value = $discount_value.val();
    }
  }

  let $temp, x;

  for (x = 0; x < $options.length; x++) {
    $temp = $($options[x]);
    conditions.products.push({ product_id: $temp.val(), label: $temp.text() });
  }

  $table.data("combined", conditions);
  toggle_editing_mode(true);
}

/**
 * Save on coupon publish.
 *
 * @since 1.1.0
 *
 * @param {e} object Event Object.
 */
export function save_on_coupon_publish(e: Event) {
  if (!$(module_block).data("editing")) return;

  e.preventDefault();
  $(module_block).find("#save-bogo-deals").trigger("click");

  // delay for 1 second to give time for
  setTimeout(() => {
    // If BOGO Deals displays an error message then don't save the coupon
    if ($(module_block).hasClass("save-error"))
      $(module_block).removeClass("save-error");
    else $("form#post").submit();
  }, 1000);
}

/**
 * Update any products trigger data when an input in the block's value is changed.
 *
 * @since 1.4
 */
export function update_any_products_trigger_data(e: JQuery.Event) {
  const $block = $(this).closest(".any-products-trigger-form");
  const quantity: number = parseInt(
    $block.find("input#any_products_trigger_qty").val()
  );
  const data = {
    quantity: quantity && !isNaN(quantity) ? quantity : 0,
  };

  $block.data("anyproducts", data);
  if (e.type === "change") toggle_editing_mode(true);
}

/**
 * Update any products apply data when an input in the block's value is changed.
 *
 * @since 1.4
 */
export function update_any_products_apply_data(e: JQuery.Event) {
  const $block = $(this).closest(".any-products-apply-form");
  const quantity: number = parseInt(
    $block.find("input.condition-quantity").val()
  );
  const discountType: string = $block
    .find("select.discount_type")
    .val()
    .toString();
  const discountValue: string = $block
    .find("input.discount_value")
    .val()
    .toString();

  const data = {
    quantity: quantity && !isNaN(quantity) ? quantity : 0,
    discount_type: discountType ?? "",
    discount_value: discountValue ?? "",
  };

  $block.data("anyproducts", data);
  if (e.type === "change") toggle_editing_mode(true);
}
