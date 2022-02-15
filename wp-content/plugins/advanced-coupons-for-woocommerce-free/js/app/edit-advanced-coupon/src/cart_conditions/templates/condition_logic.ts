import { selected } from "../../helper";

declare var acfw_edit_coupon: any;

/**
 * Return logic template markup.
 * 
 * @since 1.15
 */
export default function condition_logic_template( value: string = "" ): string {

    const { logic_field_options } = acfw_edit_coupon;
    const { and , or } = logic_field_options;
    
    return `
        <div class="condition-group-logic">
            <div class="field-control">
                <select class="cgroup-logic">
                    <option value="and" ${ selected( value , "and" ) }>${ and }</option>
                    <option value="or" ${ selected( value , "or" ) }>${ or }</option>
                </select>
            </div>
        </div>
    `;
}