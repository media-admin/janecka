import { selected } from "../../helper";

declare var acfw_edit_coupon: any;
const { cart_condition_fields } = acfw_edit_coupon;

/**
 * Return customer logged in status condition field template markup.
 * 
 * @since 1.15 
 * 
 * @param data 
 */
export default function customer_logged_in_status_template( data:string ): string {

    const { title } = cart_condition_fields.customer_logged_in_status;

    return `
        <div class="customer-logged-in-status-field condition-field" data-type="customer-logged-in-status">

        <a class="remove-condition-field" href="javascript:void(0);"><i class="dashicons dashicons-trash"></i></a>

        <h3 class="condition-field-title">${ title }</h3>

        <div class="field-control">
            <select class="condition-value">
                ${ get_options( data ) }
            </select>
        </div>
    </div>`;
}

/**
 * Get condition field options.
 * 
 * @since 1.15 
 * 
 * @param data 
 */
function get_options( data: string ): string {

    const { options } = cart_condition_fields.customer_logged_in_status;
    let markup = '';

    for ( let x in options ) {
        markup += `<option value="${ x }" ${ selected( x , data ) }>${ options[ x ] }</option>`;
    }

    return markup;
}