declare var acfw_edit_coupon: any;
import condition_group_template from "condition_group";

/**
 * Empty cart conditions template.
 * 
 * @since 1.15
 */
export default function empty_cart_conditions_template() {

    const { no_condition_group_msg } = acfw_edit_coupon;

    return `
    <div class="no-condition-group">
        ${ no_condition_group_msg }
    </div>
    `;
}