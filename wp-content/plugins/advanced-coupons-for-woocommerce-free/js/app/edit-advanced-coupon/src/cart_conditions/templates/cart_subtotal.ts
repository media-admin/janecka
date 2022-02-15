import { condition_options } from "../../helper";

declare var acfw_edit_coupon: any;

/**
 * Return cart subtotal condition field template markup.
 *
 * @since 1.15
 *
 * @param data
 */
export default function cart_subtotal_template(data: any): string {
  const { condition, value } = data;
  const { cart_condition_fields, condition_label } = acfw_edit_coupon;
  const { title, desc, field } = cart_condition_fields.cart_subtotal;

  return `
    <div class="cart-subtotal-field condition-field" data-type="cart-subtotal">
        <a class="remove-condition-field" href="javascript:void(0);"><i class="dashicons dashicons-trash"></i></a>
        <h3 class="condition-field-title">${title}</h3>
        <label>${desc}</label>
        <div class="field-control">
            <label>${condition_label}</label>
            <select class="condition-select">
                ${condition_options(condition)}
            </select>
        </div>
        <div class="field-control">
            <label>${field}</label>
            <input class="condition-value wc_input_price" type="text" value="${
              value ? value : ""
            }">
        </div>
    </div>
    `;
}
