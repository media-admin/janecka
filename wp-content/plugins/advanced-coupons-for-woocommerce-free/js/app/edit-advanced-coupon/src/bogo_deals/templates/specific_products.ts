import { esc_attr } from "../../helper";
import product_first_column_template from "./product_first_column";
import placeholder_row_template from "./placeholder_row";

declare var acfw_edit_coupon: any;

/**
 * Specific products condition type.
 *
 * @since 1.1.0
 */
export default function specific_products_template(
  data: any,
  data_type: string,
  is_deals: boolean = false
) {
  const { add_product_label, bogo_form_fields, bogo_instructions } =
    acfw_edit_coupon;
  const { quantity: quantityLabel, product: productLabel } = bogo_form_fields;

  const instructions: string = is_deals
    ? `${bogo_instructions.apply_default}`
    : bogo_instructions.trigger_default;

  const colspan: number = is_deals ? 4 : 3;
  const priceTh: string = is_deals
    ? `<th class="price">Price/Discount</th>`
    : "";

  let tbody: string = "";
  let exclude: number[] = [];

  if (data.length >= 1 && data_type == "specific-products") {
    for (let product of data) {
      let { quantity, discount_type, discount_value } = product;
      let priceCol: string = is_deals
        ? get_price_column(discount_type, discount_value)
        : "";

      exclude.push(product.product_id);
      tbody += `
        <tr>
          ${product_first_column_template(product)}
          <td class="quantity">${quantity}</td>
          ${priceCol}
          <td class="actions">
            <a class="edit" href="javascript:void(0)"><span class="dashicons dashicons-edit"></span></a>
            <a class="remove" href="javascript:void(0)"><span class="dashicons dashicons-no"></span></a>
          </td>
        </tr>
      `;
    }
  } else {
    tbody = placeholder_row_template(colspan);
  }

  const markup = `
    <p class="instructions">${instructions}</p>
    <table class="product-quantity-condition-table acfw-styled-table" 
      data-type="product" data-exclude="${esc_attr(JSON.stringify(exclude))}" 
      data-isdeals="${is_deals}">
      <thead>
        <tr>
          <th class="product">${productLabel}</th>
          <th class="quantity">${quantityLabel}</th>
          ${priceTh}
          <th class="actions"></th>
        </tr>
      </thead>
      <tbody>${tbody}</tbody>
      <tfoot>
        <tr>
          <td colspan="${colspan}">
            <a class="add-table-row" href="javascript:void(0);">
              <i class="dashicons dashicons-plus"></i>
              ${add_product_label}
            </a>
          </td>
        </tr>
      </tfoot>
    </table>
  `;

  return markup;
}

/**
 * Get price column.
 *
 * @param discount_type
 * @param discount_value
 */
function get_price_column(
  discount_type: string = "override",
  discount_value: string = "0"
) {
  return `<td class="price">
    (${discount_type}) ${discount_value}
  </td>`;
}
