import { toggle_overlay } from "../helper";
import { toggle_editing_mode } from "./toggles";

// templates
import logic_template from "./templates/logic";
import product_category_template from "./templates/product_category";
import customer_logged_in_status_template from "./templates/customer_logged_in_status";
import customer_user_role_template from "./templates/customer_user_role";
import disallowed_customer_user_role_template from "./templates/disallowed_customer_user_role";
import cart_quantity_template from "./templates/cart_quantity";
import cart_subtotal_template from "./templates/cart_subtotal";

declare var jQuery: any;
declare var acfw_edit_coupon: any;
declare var vex: any;

const $: any = jQuery;
const {
  cart_condition_fields,
  premium_cart_condition_fields,
  upsell,
} = acfw_edit_coupon;

/**
 * Add condition field.
 *
 * @since 1.0.0
 */
export function add_condition_field(): void {
  const $button: JQuery = $(this),
    $condition_type: JQuery = $button
      .closest(".condition-group-actions")
      .find("select.condition-types"),
    $condition_group: JQuery = $button.closest(".condition-group"),
    $condition_fields: JQuery = $condition_group.find(
      ".condition-group-fields"
    ),
    overlay: HTMLElement = document.querySelector(
      "#acfw_cart_conditions .acfw-overlay"
    );

  const condition_type: string = $condition_type.val() + "";
  const fields_count: number = $condition_fields.find(
    ".condition-field,.condition-set"
  ).length;

  toggle_overlay(overlay, "show");

  if (
    premium_cart_condition_fields &&
    premium_cart_condition_fields.indexOf(condition_type) > -1
  ) {
    // upsell
    vex.dialog.alert({ unsafeMessage: upsell.cart_condition_field });
  } else {
    const condition_field_markup: string = get_condition_field(
      condition_type,
      null,
      fields_count
    );

    if (condition_field_markup) {
      $condition_fields.find(".empty-conditions").remove();
      $condition_fields.show().append(condition_field_markup);
      $condition_fields
        .find(`.${condition_type}-field`)
        .find("input,select")
        .trigger("change");
      $("body").trigger("wc-enhanced-select-init");

      toggle_editing_mode(true);
    } else vex.dialog.alert(acfw_edit_coupon.fail_add_condition_field);
  }

  toggle_overlay(overlay, "hide");

  $condition_group.find(".condition-group-actions .add-condition-form").hide();
  $condition_group
    .find(".condition-group-actions .add-condition-trigger")
    .show();
}

/**
 * Remove condition field.
 *
 * @since 1.0.0
 */
export function remove_condition_field(): void {
  const $button: JQuery = $(this),
    $condition_field: JQuery = $button.closest(
      ".condition-field,.condition-set"
    ),
    $condition_group: JQuery = $condition_field.closest(".condition-group"),
    $cfield_logic: JQuery =
      $condition_field.index() == 0
        ? $condition_field.next(".logic-condition-field")
        : $condition_field.prev(".logic-condition-field");

  const { empty_cart_conditions_field } = acfw_edit_coupon;

  $condition_field.remove();
  $cfield_logic.remove();

  if ($condition_group.find(".condition-field,.condition-set").length < 1) {
    $condition_group.find(".condition-group-fields").append(`
            <div class="empty-conditions">
                ${empty_cart_conditions_field}
            </div>
        `);
  }

  toggle_editing_mode(true);
}

/**
 * Get condition field markup.
 *
 * @param condition_type string
 * @param fields_count number
 * @return string
 */
export function get_condition_field(
  condition_type: string,
  data: any = null,
  fields_count: number = 0
): string {
  let markup: string = "";
  switch (condition_type) {
    case "product-category":
      data = data ? data : {};

      // if data is an array (old format) then we build
      if (data.length) data = { condition: ">", value: data, quantity: 0 };

      markup += product_category_template(data);
      break;

    case "customer-logged-in-status":
      markup += customer_logged_in_status_template(data);
      break;

    case "customer-user-role":
      data = data ? data : [];
      markup += customer_user_role_template(data);
      break;

    case "disallowed-customer-user-role":
      data = data ? data : [];
      markup += disallowed_customer_user_role_template(data);
      break;

    case "cart-quantity":
      data = data ? data : {};
      markup += cart_quantity_template(data);
      break;

    case "cart-subtotal":
      data = data ? data : {};
      markup += cart_subtotal_template(data);
      break;

    default:
      const field_key: string = condition_type.replace(/-/g, "_");
      if (cart_condition_fields[field_key]) {
        const { template_callback, default_data_value } = cart_condition_fields[
          field_key
        ];
        if (default_data_value) data = data ? data : default_data_value;

        if (template_callback) markup += template_callback(data, field_key);
      }
      break;
  }

  return markup && fields_count ? logic_template() + markup : markup;
}

/**
 * Check if condition field type is supported.
 *
 * @param type string
 */
export function is_condition_field(type: string) {
  return acfw_edit_coupon.cart_conditon_field_options.indexOf(type) > -1;
}
