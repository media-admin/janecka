import { condition_options } from "../../helper";

declare var acfw_edit_coupon: any;
const { cart_condition_fields } = acfw_edit_coupon;

/**
 * Return cart quantity condition field template markup.
 * 
 * @since 1.15 
 * 
 * @param data 
 */
export default function cart_quantity_template( data: any ): string {

    const { condition , value } = data;
    const { title, desc, field } = cart_condition_fields.cart_quantity;
    const { condition_label } = acfw_edit_coupon;

    return `
    <div class="cart-quantity-field condition-field" data-type="cart-quantity">
        <a class="remove-condition-field" href="javascript:void(0);"><i class="dashicons dashicons-trash"></i></a>
        <h3 class="condition-field-title">${ title }</h3>
        <label>${ desc }</label>
        <div class="field-control">
            <label>${ condition_label }</label>
            <select class="condition-select">
                ${ condition_options( condition ) }
            </select>
        </div>
        <div class="field-control">
            <label>${ field }</label>
            <input class="condition-value" type="number" min="0" value="${ value >= 0 ? value : "" }">
        </div>
    </div>
    `;
}