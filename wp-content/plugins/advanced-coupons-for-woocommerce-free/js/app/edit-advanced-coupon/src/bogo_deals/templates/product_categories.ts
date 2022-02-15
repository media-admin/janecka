import { esc_attr } from "../../helper";
import category_first_column_template from "./category_first_column";
import placeholder_row_template from "./placeholder_row";
import upsell_template from "./upsell";

declare var acfw_edit_coupon: any;

/**
 * Product cagtegories condition type.
 *
 * @since 1.1.0
 */
export default function product_categories_template(
  data: any,
  data_type: string,
  is_deals = false
) {
  const {
    add_prod_cat_label,
    bogo_form_fields,
    bogo_instructions,
  } = acfw_edit_coupon;
  const {
    quantity: quantityLabel,
    product_cat: categoryLabel,
  } = bogo_form_fields;

  const colspan: number = is_deals ? 4 : 3;
  const priceTh: string = is_deals
    ? `<th class="price">Price/Discount</th>`
    : "";
  const instructions: string = is_deals
    ? `${bogo_instructions.apply_default} ${bogo_instructions.multiple_items}`
    : bogo_instructions.trigger_default;

  let tbody: string = "";
  let exclude: number[] = [];

  if (data.length >= 1 && data_type == "product-categories") {
    for (let product_cat of data) {
      let {
        category_id,
        quantity,
        discount_type,
        discount_value,
      } = product_cat;
      let priceCol: string = is_deals
        ? get_price_column(discount_type, discount_value)
        : "";

      exclude.push(category_id);
      tbody += `
                <tr>
                    ${category_first_column_template(product_cat)}
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
    tbody = placeholder_row_template(colspan, "category");
  }

  return `
        <p class="instructions">${instructions}</p>
        <table class="acfw-styled-table" 
            data-type="category" 
            data-exclude="${esc_attr(JSON.stringify(exclude))}" 
            data-isdeals="${is_deals}">
            <thead>
                <tr>
                    <th class="product-cat">${categoryLabel}</th>
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
                            ${add_prod_cat_label}
                        </a>
                    </td>
                </tr>
            </tfoot>
        </table>
        ${upsell_template()}
    `;
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
