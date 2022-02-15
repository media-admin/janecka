import { toggle_editing_mode } from "./toggles";
import { price_to_float, validateDiscount } from "../helper";

import add_edit_row_template from "./templates/add_edit_row";
import table_row_template from "./templates/table_row";
import placeholder_table_row_template from "./templates/placeholder_row";

declare var jQuery: any;
declare var acfw_edit_coupon: any;
declare var vex: any;

const $: any = jQuery;
const { bogo_form_fields } = acfw_edit_coupon;

/**
 * Trigger the add table row form event.
 *
 * @since 1.1.0
 */
export function trigger_add_table_row_form() {
  const $button: JQuery = $(this),
    $table: JQuery = $button.closest(".acfw-styled-table"),
    $tbody: JQuery = $table.find("tbody"),
    is_deals: string = $table.data("isdeals"),
    type: string = $table.data("type"),
    exclude: number[] = $table.data("exclude");

  $tbody.find("tr.no-result").remove();

  $tbody.append(add_edit_row_template(type, exclude, is_deals));
  $("body").trigger("wc-enhanced-select-init");

  $tbody.find("tr.add-edit-form select.enhanced").trigger("focus");
}

/**
 * Trigger the edit table row form event.
 *
 * @since 1.1.0
 */
export function trigger_edit_table_row_form() {
  const $button: JQuery = jQuery(this),
    $row: JQuery = $button.closest("tr"),
    $table: JQuery = $button.closest(".acfw-styled-table"),
    $tbody: JQuery = $table.find("tbody"),
    is_deals: string = $table.data("isdeals"),
    type: string = $table.data("type"),
    data: any = $row.find(".object").data(type);

  const { category_id, product_id } = data;
  const exclude: number[] = $table.data("exclude");
  const object_id: number = type == "category" ? category_id : product_id;

  exclude.splice(exclude.indexOf(object_id), 1);
  $tbody.find("tr.no-result").remove();
  $row.replaceWith(add_edit_row_template(type, exclude, is_deals, data));
  $table.data("exclude", exclude);
  $table.find(".wc-product-search").data("exclude", exclude);
  $("body").trigger("wc-enhanced-select-init");
}

/**
 * Trigger the cancel add/edit table row form event.
 *
 * @since 1.1.0
 */
export function cancel_add_edit_table_row() {
  const $button: JQuery = $(this),
    $row: JQuery = $button.closest("tr"),
    $table: JQuery = $button.closest("table"),
    $tbody: JQuery = $row.closest("tbody"),
    is_deals: string = $table.data("isdeals"),
    colspan: number = $row.find("td").length,
    type: string = $table.data("type"),
    data: any = $row.find("td.product").data("object");

  const { category_id, product_id } = data;
  const exclude: number[] = $table.data("exclude");
  const object_id = type == "category" ? category_id : product_id;

  // don't proceed if product has already been added to the table.
  // this happens when the same product/category has been added to a new row and trying to cancel the row that already has the same value.
  if (exclude.includes(object_id)) {
    vex.dialog.alert(acfw_edit_coupon.product_exists_in_table);
    return;
  }

  if (typeof data == "object") {
    exclude.push(object_id);
    $row.replaceWith(table_row_template(data, type, is_deals));
    $table.data("exclude", exclude);
    $table.find(".wc-product-search").data("exclude", exclude);
  } else {
    $row.remove();

    if ($tbody.find("tr").length <= 0)
      $tbody.append(placeholder_table_row_template(colspan, type));
  }
}

/**
 * Actual function that saves/edits the table row.
 *
 * @since 1.1.0
 */
export function add_edit_table_row() {
  const $button: JQuery = $(this),
    $row: JQuery = $button.closest("tr"),
    $table: JQuery = $button.closest("table.acfw-styled-table"),
    is_deals: string = $table.data("isdeals"),
    type: string = $table.data("type"),
    $search: JQuery = $row.find(".wc-product-search"),
    $quantity: JQuery = $row.find(".condition-quantity");

  const exclude = $table.data("exclude");

  if (!$search.val() || !$quantity.val() || $quantity.val() < 1) {
    vex.dialog.alert(acfw_edit_coupon.fill_form_propery_error_msg);
    return;
  }

  // don't proceed if product has already been added to the table.
  if (exclude.includes(parseInt($search.val().toString()))) {
    vex.dialog.alert(acfw_edit_coupon.product_exists_in_table);
    return;
  }

  // dont allow negative value for discount/price field.
  if (is_deals) {
    const discount_val = $row.find("input.discount_value").val().toString();

    // validate discount by type.
    if (
      !validateDiscount(
        $row.find("select.discount_type").val().toString(),
        discount_val
      )
    ) {
      vex.dialog.alert(acfw_edit_coupon.discount_type_value_error_msg);
      return;
    }

    if (
      price_to_float(discount_val) < 0 ||
      isNaN(price_to_float(discount_val))
    ) {
      vex.dialog.alert(acfw_edit_coupon.fill_form_propery_error_msg);
      return;
    }
  }

  const data: any = get_table_row_data($row, type, is_deals);
  const { category_id, product_id } = data;

  exclude.push(type == "category" ? category_id : product_id);
  $row.replaceWith(table_row_template(data, type, is_deals));
  $table.data("exclude", exclude);
  $table.find(".wc-product-search").data("exclude", exclude);
  toggle_editing_mode(true);
}

/**
 * Remove product in table.
 *
 * @since 1.0.0
 */
export function product_table_remove_product() {
  const $button: JQuery = $(this),
    $object_row: JQuery = $button.closest("tr"),
    $table: JQuery = $object_row.closest("table"),
    $tbody: JQuery = $object_row.closest("tbody"),
    colspan: number = $object_row.find("td").length,
    type: string = $table.data("type");

  const data = $object_row.find("td.object").data(type);
  const exclude = $table.data("exclude");
  let index: number;

  switch (type) {
    case "category":
      index = exclude.indexOf(parseInt(data.category_id));
      break;

    case "product":
    default:
      index = exclude.indexOf(parseInt(data.product_id));
      break;
  }

  if (index !== -1) exclude.splice(index, 1);
  $table.data("exclude", exclude);
  $table.find(".wc-product-search").data("exclude", exclude);

  $object_row.remove();

  if ($tbody.find("tr").length <= 0)
    $tbody.append(placeholder_table_row_template(colspan, type));

  toggle_editing_mode(true);
}

/**
 * Get table row data.
 *
 * @since 1.15
 *
 * @param $row
 * @param type
 * @param is_deals
 */
function get_table_row_data($row: JQuery, type: string, is_deals: string): any {
  let object: any = {};

  if (type == "category") {
    object = {
      category_id: parseInt($row.find(".wc-product-search").val() + ""),
      quantity: parseInt($row.find(".condition-quantity").val() + ""),
      category_label: $row.find(".wc-product-search option:selected").text(),
    };
  } else {
    object = {
      product_id: parseInt($row.find(".wc-product-search").val() + ""),
      quantity: parseInt($row.find(".condition-quantity").val() + ""),
      product_label: $row.find(".wc-product-search option:selected").text(),
    };
  }

  if (is_deals) {
    object.discount_type = $row.find("select.discount_type").val();
    object.discount_value = $row.find("input.discount_value").val().toString();
  }

  return object;
}
