import { esc_attr } from "../helper";
import { toggle_editing_mode } from "./toggles";

declare var jQuery: any;
declare var acfw_edit_coupon: any;
declare var vex: any;

const $: any = jQuery;
const {
  product_table_buttons,
  condition_field_options,
  bogo_form_fields,
} = acfw_edit_coupon;

/**
 * Trigger the add table row form event.
 *
 * @since 1.1.0
 */
export function trigger_add_table_row_form() {
  const $button: JQuery = $(this),
    $table: JQuery = $button.closest(".acfw-styled-table"),
    $tbody: JQuery = $table.find("tbody"),
    exclude: number[] = $table.data("exclude");

  $tbody.find("tr.no-result").remove();

  $tbody.append(get_add_edit_form_row_markup(exclude));
  $("body").trigger("wc-enhanced-select-init");
}

/**
 * Trigger the edit table row form event.
 *
 * @since 1.1.0
 */
export function trigger_edit_table_row_form() {
  const $button: JQuery = $(this),
    $row: JQuery = $button.closest("tr"),
    $table: JQuery = $button.closest("table"),
    $tbody: JQuery = $row.closest("tbody"),
    data: any = $row.find("td.product").data("product");

  let exclude: number[] = $table.data("exclude");

  exclude.splice(exclude.indexOf(data.product_id), 1);
  $tbody.find("tr.no-result").remove();
  $row.replaceWith(get_add_edit_form_row_markup(exclude, data));
  $table.data("exclude", exclude);
  $table.find(".wc-product-search").data("exclude", exclude);
  $("body").trigger("wc-enhanced-select-init");
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
    $search: JQuery = $row.find(".wc-product-search"),
    $condition: JQuery = $row.find(".condition-select"),
    $quantity: JQuery = $row.find(".condition-quantity"),
    minimum: number = $condition.val() === "<" ? 1 : 0;

  let exclude: any = $table.data("exclude");

  if (
    !$search.val() ||
    !$condition.val() ||
    !$quantity.val() ||
    $quantity.val() < minimum
  ) {
    vex.dialog.alert(acfw_edit_coupon.fill_form_propery_error_msg);
    return;
  }

  const productId: number = parseInt($search.val().toString());

  // don't proceed if product has already been added to the table.
  // this happens when the same product has been added to a new row and trying to cancel the row that already has the same value.
  if (exclude.includes(productId)) {
    vex.dialog.alert(acfw_edit_coupon.product_exists_in_table);
    return;
  }

  const data: any = {
    product_id: productId,
    condition: $condition.val(),
    quantity: parseInt($quantity.val() + ""),
    product_label: $search.find("option:selected").text(),
    condition_label: $condition.find("option:selected").text(),
  };

  let markup: string = `
        <tr>
            <td class="product" data-product="${esc_attr(
              JSON.stringify(data)
            )}">${data.product_label}</td>
            <td class="condition">${data.condition_label}</td>
            <td class="quantity">${data.quantity}</td>
            <td class="actions">
                <a class="edit" href="javascript:void(0)"><span class="dashicons dashicons-edit"></span></a>
                <a class="remove" href="javascript:void(0)"><span class="dashicons dashicons-no"></span></a>
            </td>
        </tr>
    `;

  exclude.push(data.product_id);
  $row.replaceWith(markup);
  $table.data("exclude", exclude);
  $table.find(".wc-product-search").data("exclude", exclude);
  toggle_editing_mode(true);
}

/**
 * Trigger the cancel add/edit table row form event.
 *
 * @since 1.1.0
 */
export function cancel_add_edit_table_row() {
  const $button: JQuery = jQuery(this),
    $row: JQuery = $button.closest("tr"),
    $table: JQuery = $button.closest("table"),
    $tbody: JQuery = $row.closest("tbody"),
    data: any = $row.find("td.product").data("product"),
    colspan: number = $row.find("td").length;

  let exclude: number[] = $table.data("exclude");

  // don't proceed if product has already been added to the table.
  // this happens when the same product has been added to a new row and trying to cancel the row that already has the same value.
  if (exclude.includes(data.product_id)) {
    vex.dialog.alert(acfw_edit_coupon.product_exists_in_table);
    return;
  }

  if (typeof data == "object") {
    const { product_id, product_label, condition_label, quantity } = data;

    let markup = `
            <tr>
                <td class="product" data-product="${esc_attr(
                  JSON.stringify(data)
                )}">${product_label}</td>
                <td class="condition">${condition_label}</td>
                <td class="quantity">${quantity}</td>
                <td class="actions">
                    <a class="edit" href="javascript:void(0)"><span class="dashicons dashicons-edit"></span></a>
                    <a class="remove" href="javascript:void(0)"><span class="dashicons dashicons-no"></span></a>
                </td>
            </tr>
        `;

    exclude.push(product_id);
    $row.replaceWith(markup);
    $table.data("exclude", exclude);
    $table.find(".wc-product-search").data("exclude", exclude);
  } else {
    $row.remove();
    append_blank_placeholder_table_row($tbody, colspan);
  }
}

/**
 * Remove product in table.
 *
 * @since 1.0.0
 */
export function product_table_remove_product() {
  const $button: JQuery = jQuery(this),
    $row: JQuery = $button.closest("tr"),
    $table: JQuery = $row.closest("table"),
    $tbody: JQuery = $table.find("tbody"),
    colspan: number = $row.find("td").length;

  $row.remove();

  let data: any = $row.find("td.product").data("product"),
    exclude: number[] = $table.data("exclude"),
    index: number = exclude.indexOf(parseInt(data.product_id));

  if (index !== -1) exclude.splice(index, 1);
  $table.data("exclude", exclude);
  $table.find(".wc-product-search").data("exclude", exclude);

  append_blank_placeholder_table_row($tbody, colspan);
  toggle_editing_mode(true);
}

/**
 * Append blank placeholder row on tables that are empty.
 *
 * @param {object} $tbody  Table tbody jQuery object.
 * @param {int}    colspan Number of row columns.
 */
function append_blank_placeholder_table_row(
  $tbody: JQuery,
  colspan: number = 3
) {
  if ($tbody.find("tr").length > 0) return;

  $tbody.append(`
        <tr class="no-result">
            <td colspan="${colspan}">${acfw_edit_coupon.no_products_added}</td>
        </tr>
    `);
}

/**
 * Get add/edit form row markup.
 *
 * @since 1.0.0
 *
 * @param {object} exclude Array of excluded product ids.
 * @param {object} data    Product data.
 * @return {string} Add/edit form row markup.
 */
function get_add_edit_form_row_markup(
  exclude: number[] = [],
  data: any = false
): string {
  let prodOption: string = "",
    condOption: string = "";

  if (typeof data == "object") {
    const { product_id, product_label } = data;

    prodOption = `<option value="${product_id}">${product_label}</option>`;
    condOption = data.condition;
  }

  const { add, edit, cancel } = product_table_buttons;
  const { exactly, anyexcept, morethan, lessthan } = condition_field_options;
  const { type_to_search } = bogo_form_fields;

  const btnText = typeof data == "object" ? edit : add,
    markup = `
        <tr class="add-edit-form adding">
            <td class="product" data-product="${
              typeof data == "object" ? esc_attr(JSON.stringify(data)) : 0
            }">
                <div class="object-search-wrap">
                    <select class="product-in-cart wc-product-search"
                        data-placeholder="${type_to_search}"
                        data-action="acfw_search_products"
                        data-exclude="${esc_attr(
                          JSON.stringify(exclude)
                        )}">${prodOption}</select>
                </div>
            </td>
            <td class="condition">
                <select class="condition-select">
                    <option value="=" ${
                      condOption == "=" ? "selected" : ""
                    }>${exactly}</option>
                    <option value="!=" ${
                      condOption == "!=" ? "selected" : ""
                    }>${anyexcept}</option>
                    <option value=">" ${
                      condOption == ">" ? "selected" : ""
                    }>${morethan}</option>
                    <option value="<" ${
                      condOption == "<" ? "selected" : ""
                    }>${lessthan}</option>
                </select>
            </td>
            <td class="quantity">
                <input type="number" class="condition-quantity" value="${
                  typeof data == "object" ? data.quantity : 1
                }" min="${condOption == "<" ? 1 : 0}">
            </td>
            <td class="actions">
                <button type="button" class="condition-product-add button-primary">${btnText}</button>
                <button type="button" class="cancel button">${cancel}</button>
            </td>
        </tr>
        `;

  return markup;
}
