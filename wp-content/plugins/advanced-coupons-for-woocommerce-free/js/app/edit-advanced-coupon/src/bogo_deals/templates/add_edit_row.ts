import { esc_attr } from "../../helper";

declare var acfw_edit_coupon: any;

/**
 * Add edit row template.
 *
 * @since 1.15
 *
 * @param type
 * @param exclude
 * @param is_deals
 */
export default function add_edit_row_template(
  type: string,
  exclude: number[],
  is_deals: string,
  data: any = false
): string {
  const { type_to_search } = acfw_edit_coupon.bogo_form_fields;
  const { add, edit, cancel } = acfw_edit_coupon.product_table_buttons;
  const { quantity } = data;

  const option: string = typeof data == "object" ? get_option(type, data) : "";
  const priceCol: string = is_deals ? get_price_column(data) : "";
  const btnText: string = typeof data == "object" ? edit : add;

  return `
    <tr class="add-edit-form adding">
        <td class="product" data-object="${
          typeof data == "object" ? esc_attr(JSON.stringify(data)) : 0
        }">
            <div class="object-search-wrap">
                <select class="${
                  type == "category"
                    ? "product-category-in-cart"
                    : "product-in-cart"
                } wc-product-search"
                    data-placeholder="${type_to_search}"
                    data-action="${
                      type == "category"
                        ? "acfw_search_product_category"
                        : "acfw_search_products"
                    }"
                    data-exclude="${esc_attr(
                      JSON.stringify(exclude)
                    )}">${option}</select>
            </div>
        </td>
        <td class="quantity">
            <input type="number" class="condition-quantity" value="${
              typeof data == "object" ? quantity : 1
            }" min="1">
        </td>
        ${priceCol}
        <td class="actions">
            <button type="button" class="condition-product-add button-primary">${btnText}</button>
            <button type="button" class="cancel button">${cancel}</button>
        </td>
    </tr>
    `;
}

/**
 * Get option markup.
 *
 * @since 1.15
 *
 * @param type
 * @param data
 */
function get_option(type: string, data: any): string {
  const { category_id, product_id, category_label, product_label } = data;
  const value: string = type == "category" ? category_id : product_id;
  const label: string = type == "category" ? category_label : product_label;

  return `<option value="${value}">${label}</option>`;
}

function get_price_column(data: any): string {
  const { currency_symbol, discount_field_options } = acfw_edit_coupon;
  const { override, percent, fixed } = discount_field_options;
  const { discount_type, discount_value } = data;
  const value: string =
    typeof data == "object" && discount_value ? discount_value : "0";

  return `
    <td class="price">
        <div>
        <select class="discount_type">
            <option value="override" ${
              discount_type == "override" ? "selected" : ""
            }>${currency_symbol} : ${override}</option>
            <option value="percent" ${
              discount_type == "percent" ? "selected" : ""
            }>% : ${percent}</option>
            <option value="fixed" ${
              discount_type == "fixed" ? "selected" : ""
            }>-${currency_symbol} : ${fixed}</option>
        </select>
        <input type="text" class="discount_value short wc_input_price" value="${value}">
        </div>
    </td>
    `;
}
