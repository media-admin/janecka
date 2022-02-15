import { esc_attr } from "../../helper";
import upsell_template from "./upsell";

declare var acfw_edit_coupon: any;

/**
 * Same products apply template.
 *
 * @since 2.6
 *
 * @param data
 */
export default function same_products_template(data: any) {
  const { quantity, discount_type, discount_value } = data;
  const {
    currency_symbol,
    discount_field_options,
    bogo_form_fields,
    bogo_instructions,
  } = acfw_edit_coupon;
  const { override, percent, fixed } = discount_field_options;
  const {
    quantity: quantityLabel,
    price_discount: priceDiscountLabel,
    discount_type: discountTypeLabel,
  } = bogo_form_fields;

  const priceType: string = discount_type ? data.discount_type : "override";

  return `
    <p class="instructions">${bogo_instructions.same_apply}</p>
    <table class="acfw-styled-table same-products-apply-form"  data-sameproducts="${esc_attr(
      JSON.stringify(data)
    )}">
      <thead>
        <tr>
          <th class="quantity">${quantityLabel}</th>
          <th class="type">${discountTypeLabel}</th>
          <th class="price">${priceDiscountLabel}</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="quantity object">
            <input type="number" class="condition-quantity" value="${
              quantity ?? "1"
            }" min="1">
          </td>
          <td class="type">
            <select class="discount_type">
              <option value="override" ${
                priceType == "override" ? "selected" : ""
              }>${currency_symbol} : ${override}</option>
              <option value="percent" ${
                priceType == "percent" ? "selected" : ""
              }>% : ${percent}</option>
              <option value="fixed" ${
                priceType == "fixed" ? "selected" : ""
              }>-${currency_symbol} : ${fixed}</option>
            </select>
          </td>
          <td class="price">
            <input type="text" class="discount_value short wc_input_price" value="${
              discount_value ?? "0"
            }">
          </td>
        </tr>
      </tbody>
    </table>
    ${upsell_template()}
  `;
}
