import { get_allowed_minimum_value } from "../helper";

declare var jQuery: any;
declare var acfw_edit_coupon: any;

const $: any = jQuery;

/**
 * Restrict number input minimum value.
 *
 * @since 1.0.0
 */
export function restrict_number_min_value() {
  const $input: JQuery = $(this),
    min: number = parseInt($input.attr("min")),
    value: number = parseFloat($input.val() + "");

  if (value < min) $input.val(min);
}

/**
 * Restrict number input minimum value.
 *
 * @since 1.0.0
 */
export function update_input_minimum_value() {
  const $select: JQuery = $(this),
    $field: JQuery = $select.closest(".condition-field,.condition-set"),
    field_type: string = $field.data("type"),
    selector: string =
      field_type == "product-quantity" ||
      field_type == "has-ordered-before" ||
      field_type == "product-category"
        ? "input.condition-quantity"
        : "input.condition-value",
    $input: JQuery = $field.find(selector),
    condition: string = $select.val() + "";

  let min: number = get_allowed_minimum_value(condition);

  $input.attr("min", min).trigger("change");
}

/**
 * Updated has ordered before condition label.
 *
 * @since 1.0.0
 */
export function update_has_ordered_before_condition_label() {
  const { within_a_period_label, number_of_orders_label } = acfw_edit_coupon;

  const $select: JQuery = $(this),
    $field: JQuery = $select.closest(".cart-quantity-field"),
    $label: JQuery = $field.find(".condition-type-value label");

  if ($select.val() == "within-a-period") $label.text(within_a_period_label);
  else $label.text(number_of_orders_label);
}

/**
 * Validate price field value on change.
 *
 * @since 1.2
 */
export function validate_price_field_value() {
  const $input: JQuery = $(this),
    $condition_block: JQuery = $input.closest(".condition-field"),
    $condition_field: JQuery = $condition_block.find("select.condition-select"),
    condition_type: string = $condition_field.val() + "";

  let input_price: string = $input.val() + "",
    minimum_price: number = get_allowed_minimum_value(condition_type);

  if (acfw_edit_coupon.decimal_separator !== ".")
    input_price = input_price.replace(acfw_edit_coupon.decimal_separator, ".");

  if (!$condition_field || parseFloat(input_price) > minimum_price - 1) return;

  $input.val(minimum_price);
}

/**
 * Remove leading zeros from an input field.
 *
 * @since 1.2
 */
export function remove_leading_zeros(): void {
  const $input: any = $(this);
  let value: string = $input.val();

  if (value == "0" + acfw_edit_coupon.decimal_separator) $input.val("0");
  if (
    value == "0" ||
    value.substring(0, 2) == "0" + acfw_edit_coupon.decimal_separator
  )
    return;

  value = value.replace(/^0+/, "");
  value =
    value.substring(0, 1) == acfw_edit_coupon.decimal_separator
      ? "0" + value
      : value;

  $input.val(value);
}

/**
 * Condition value type change (used in user and cart item meta condition fields).
 *
 * @since 1.4
 */
export function condition_value_type_change() {
  const $type: JQuery = $(this),
    $field: JQuery = $type.closest(".condition-field"),
    $condition: JQuery = $field.find("select.condition-select"),
    $value: JQuery = $field.find("input.condition-value");

  switch ($type.val()) {
    case "string":
      $value.prop("type", "text");
      $value.removeClass("wc_input_price");
      if ($condition.val() == ">" || $condition.val() == "<")
        $condition.val("=");
      $condition
        .find("option[value='>'],option[value='<']")
        .prop("disabled", true);
      break;

    case "number":
      $value.prop("type", "number");
      $value.removeClass("wc_input_price");
      $condition
        .find("option[value='>'],option[value='<']")
        .prop("disabled", false);
      break;

    case "price":
      $value.prop("type", "text");
      $value.addClass("wc_input_price");
      $condition
        .find("option[value='>'],option[value='<']")
        .prop("disabled", false);
      break;
  }

  $condition.trigger("change");
}
