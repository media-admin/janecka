import { selected_multiple } from "../../helper";

declare var acfw_edit_coupon: any;
const { cart_condition_fields } = acfw_edit_coupon;

/**
 * Return disallowed customer user role condition field template markup.
 * 
 * @since 1.15 
 * 
 * @param data 
 */
export default function disallowed_customer_user_role_template( data: string[] ): string {

    const { title , placeholder } = cart_condition_fields.disallowed_customer_user_role;

    return `
        <div class="disallowed-customer-user-role-field condition-field" data-type="disallowed-customer-user-role">
            <a class="remove-condition-field" href="javascript:void(0);"><i class="dashicons dashicons-trash"></i></a>
            <h3 class="condition-field-title">${ title }</h3>
            <div class="field-control">
                <select class="wc-enhanced-select condition-value" multiple data-placeholder="${ placeholder }">
                    ${ get_options( data ) }
                </select>
            </div>
        </div>
    `;
}

/**
 * Get condition field options markup.
 * 
 * @since 1.15
 * 
 * @param data
 */
function get_options( data: string[] ) {

    const { user_role_options } = acfw_edit_coupon;
    let markup = '';
    
    for ( let x in user_role_options )
        markup += `<option value="${ x }" ${ selected_multiple( data , x ) }>${ user_role_options[x] }</option>`;

    return markup;
}