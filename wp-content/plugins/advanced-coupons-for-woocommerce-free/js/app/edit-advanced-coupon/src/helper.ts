declare var jQuery: any;
declare var acfw_edit_coupon: any;

/**
 * Toggle overlay block.
 *
 * @since 1.0.0
 *
 * @param object overlay Overlay html element.
 * @param string type    Toggle type (show/hide).
 */
export function toggle_overlay(overlay: HTMLElement, type: string = ""): void {
  if (!type) type = "show";

  let display = type === "show" ? "block" : "none";
  jQuery(overlay).css("display", display);
}

/**
 * Escape string to be used on an attribute.
 *
 * @since 1.0.0
 * @param string text String to escape.
 */
export function esc_attr(text: string): string {
  const map: any = {
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#039",
  };

  return text.replace(/[&<>"']/g, (m) => map[m]);
}

/**
 * Remove leading zeros from an input field.
 *
 * @since 1.2
 */
export function remove_leading_zeros() {
  const { decimal_separator } = acfw_edit_coupon;

  const $input: JQuery = jQuery(this);
  let value: string = $input.val() + "";

  if (value == "0" + decimal_separator) $input.val("0");
  if (value == "0" || value.substring(0, 2) == "0" + decimal_separator) return;

  value = value.replace(/^0+/, "");
  value = value.substring(0, 1) == decimal_separator ? "0" + value : value;

  $input.val(value);
}

/**
 * Array to select options markup.
 *
 * @param options
 * @param value
 * @param key
 * @param label
 */
export function array_to_select_options(
  options: any,
  value: string,
  key: string = "value",
  label: string = "label"
): string {
  return options
    .map(
      (option: any) =>
        `<option value="${option[key]}" ${
          option[key] == value ? "selected" : ""
        }>${option[label]}</option>`
    )
    .toString()
    .replace(/>,</g, "><");
}

/**
 * Get allowed minimum value.
 *
 * @since 1.0.0
 * @since 1.2   Remove unused property.
 *
 * @param {string} condition
 */
export function get_allowed_minimum_value(condition: string): number {
  return condition == "<" ? 1 : 0;
}

/**
 * Compare option values to mark option as selected.
 *
 * @param value_1
 * @param value_2
 */
export function selected(value_1: string, value_2: string) {
  return value_1 == value_2 ? "selected" : "";
}

export function selected_multiple(array: any[], value: string) {
  return array.indexOf(value) >= 0 ? "selected" : "";
}

/**
 * Get discount type select options markup.
 *
 * @since 1.15
 *
 * @param value
 */
export function discount_type_options(
  value: string = "",
  show_nodiscount: boolean = false
): string {
  const { nodiscount, override, percent, fixed } =
    acfw_edit_coupon.discount_field_options;
  const nodiscount_markup = `<option value="nodiscount" ${selected(
    value,
    "nodiscount"
  )}>= : ${nodiscount}</option>`;

  return `
        ${show_nodiscount ? nodiscount_markup : ""}
        <option value="override" ${selected(
          value,
          "override"
        )}>$ : ${override}</option>
        <option value="percent" ${selected(
          value,
          "percent"
        )}>% : ${percent}</option>
        <option value="fixed" ${selected(value, "fixed")}>-$ : ${fixed}</option>
    `;
}

/**
 * Get condition options markup.
 *
 * @sinc 1.15
 * @param condition
 */
export function condition_options(condition: string = ""): string {
  const { exactly, anyexcept, morethan, lessthan } =
    acfw_edit_coupon.condition_field_options;

  return `
        <option value="=" ${selected(condition, "=")}>${exactly}</option>
        <option value="!=" ${selected(condition, "!=")}>${anyexcept}</option>
        <option value=">" ${selected(condition, ">")}>${morethan}</option>
        <option value="<" ${selected(condition, "<")}>${lessthan}</option>
    `;
}

/**
 * Validate url.
 *
 * @since 1.15
 */
export function validate_url(url: string): boolean {
  const pattern =
    /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/;

  return pattern.test(url);
}

/**
 * Format price string to float.
 *
 * @since 1.3.3
 */
export function price_to_float(price: string): number {
  const { decimal_separator } = acfw_edit_coupon;
  return parseFloat(price.replace(decimal_separator, "."));
}

/**
 * Validate discount value by type.
 * Make sure that percent and fixed type discounts is not valid when value is not greater than 0.
 *
 * @param type
 * @param value
 * @returns
 */
export function validateDiscount(type: string, value: string): boolean {
  if ("percent" === type || "fixed" === type) {
    return 0 < price_to_float(value);
  }

  return true;
}
