import {
  get_allowed_minimum_value,
  toggle_overlay,
  validate_url,
} from "../helper";
import { toggle_editing_mode } from "./toggles";
import { scrape_condition_group_data } from "./scrapers";

declare var jQuery: any;
declare var acfw_edit_coupon: any;
declare var ajaxurl: string;
declare var woocommerce_admin_meta_boxes: any;
declare var vex: any;

const $: any = jQuery;
const { post_id } = woocommerce_admin_meta_boxes;

/**
 * Save cart conditions.
 *
 * @since 1.0.0
 */
export function save_cart_conditions() {
  const module_block: HTMLElement = document.querySelector(
      "#acfw_cart_conditions"
    ),
    condition_groups: NodeList = module_block.querySelectorAll(
      ".condition-groups .condition-group, .condition-groups .condition-group-logic"
    ),
    overlay: HTMLElement = module_block.querySelector(".acfw-overlay");

  const { post_status } = acfw_edit_coupon;
  const { post_id } = woocommerce_admin_meta_boxes;

  let form_data: any[] = [];

  // remove product table edit form rows.
  $(module_block)
    .find(".acfw-styled-table tr.add-edit-form button.cancel")
    .trigger("click");

  condition_groups.forEach((condition_group: HTMLElement) => {
    const data = scrape_condition_group_data(condition_group);
    if (data) form_data.push(data);
  });

  if (!validate_form_data(form_data)) {
    vex.dialog.alert(acfw_edit_coupon.cart_conditions_invalid);

    // Add save error flag class in #acfw_cart_conditions
    $(module_block).addClass("save-error");
    return;
  }

  const notice_settings = {
    message: $(module_block)
      .find(".text-input.non-qualifying-message-field")
      .val(),
    btn_text: $(module_block)
      .find(".text-input.non-qualifying-btn-text-field")
      .val(),
    btn_url: $(module_block)
      .find(".text-input.non-qualifying-btn-url-field")
      .val(),
  };
  const auto_apply_display = $(module_block)
    .find(".display-notice-auto-apply-field")
    .is(":checked")
    ? "yes"
    : "";

  if (notice_settings.btn_url && !validate_url(notice_settings.btn_url)) {
    vex.dialog.alert(acfw_edit_coupon.cart_notice_url_invalid);
    $(module_block).addClass("save-error");
    return;
  }

  toggle_overlay(overlay, "show");

  ajax_save(
    {
      cart_conditions: form_data,
      notice_settings: notice_settings,
      auto_apply_display: auto_apply_display,
    },
    (response: any) => {
      if (response.status == "success") {
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

      toggle_editing_mode(false);
      $(module_block).find("#save-cart-conditions").prop("disabled", true);
      toggle_overlay(overlay, "hide");
    }
  );
}

/**
 * Save on coupon publish.
 *
 * @since 1.0.0
 */
export function save_on_coupon_publish(e: any) {
  const module_block: HTMLElement = document.querySelector(
    "#acfw_cart_conditions"
  );

  if (!$(module_block).data("editing")) return;

  e.preventDefault();
  $(module_block).find("#save-cart-conditions").trigger("click");

  // delay for 1 second to give time for
  setTimeout(() => {
    // If Cart Condition displays an error message then don't save the coupon
    if ($(module_block).hasClass("save-error"))
      $(module_block).removeClass("save-error");
    else $("form#post").submit();
  }, 1000);
}

/**
 * Trigger AJAX save.
 *
 * @since 1.0.0
 *
 * @param form_data
 * @param callback
 * @return jQuery AJAX
 */
function ajax_save(data: any, callback: CallableFunction) {
  data.action = "acfw_save_cart_conditions";
  data.coupon_id = post_id;

  return $.post(ajaxurl, data, callback, "json");
}

/**
 * Validate cart conditions form data.
 *
 * @since 1.0.0
 */
function validate_form_data(form_data: any[]): boolean {
  let group, field, data, type, objects, condition, value, min;

  // return true if form data has a single empty group (initial layout).
  if (form_data.length === 1 && form_data[0].fields?.length < 1) return true;

  let prevGroupType = "";

  for (group of form_data) {
    if (group["type"] === "group_logic") {
      // invalidate if the previous group of the logic is still a logic or non-existent.
      if (!prevGroupType || prevGroupType === "group_logic") {
        return false;
      }

      prevGroupType = "group_logic";

      // skip validation for group logic fields.
      continue;
    }

    prevGroupType = group["type"];

    // invalidate if no fields detected.
    if (group["fields"] === undefined || group["fields"].length < 1)
      return false;

    for (field of group["fields"]) {
      type = field["type"];
      data = field["data"];

      // invalidate if atleast a single field has invalid data.
      if (data === undefined || data === "" || data === null) return false;

      if (typeof data == "object" && typeof data.length == "number")
        // product quantities field validation
        objects = data;
      else if (type === "product-category") {
        // product category validation

        objects = data["value"];
        condition = data["condition"];
        value = data["quantity"];
      } else if (typeof data == "object" && data.length === undefined) {
        // has ordered before validation

        objects = data["products"];
        condition = data["condition"];
        value = data["value"];
      } else value = data;

      min = get_allowed_minimum_value(condition);

      if (
        (objects !== undefined && objects.length < 1) ||
        (condition !== undefined && !condition) ||
        (!Number.isInteger(value) && value !== undefined && !value) ||
        (Number.isInteger(value) && value < min) ||
        (typeof value == "number" && value < min)
      )
        return false;
    }
  }

  return true;
}
