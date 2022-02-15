import { selected_multiple } from "../../helper";

declare var acfw_edit_coupon: any;
const { cart_condition_fields , condition_field_options } = acfw_edit_coupon;

/**
 * Return product category condition field template markup.
 * 
 * @since 1.15 
 * 
 * @param data 
 */
export default function product_category_template( data: any ): string {

    const { value: categories , condition , quantity } = data;
    const { title, placeholder, field } = cart_condition_fields.product_category;
    const { field: quantityLabel } = cart_condition_fields.cart_quantity;
    const { condition_label } = acfw_edit_coupon;
    const { exactly, anyexcept, morethan, lessthan } = condition_field_options;

    const condOption = condition ? condition : ">";

    return `
    <div class="product-category-field condition-field" data-type="product-category">
        <a class="remove-condition-field" href="javascript:void(0);"><i class="dashicons dashicons-trash"></i></a>
        <h3 class="condition-field-title">${ title }</h3>
        <div class="field-control categories">
            <label>${ field }</label>
            <select class="condition-value wc-enhanced-select" multiple data-placeholder="${ placeholder }">
                ${ category_options( categories ) }
            </select>
        </div>
        <div class="field-control condition">
            <label>${ condition_label }</label>
            <select class="condition-select">
                <option value="=" ${ condOption == "=" ? "selected" : "" }>${ exactly }</option>
                <option value="!=" ${ condOption == "!=" ? "selected" : "" }>${ anyexcept }</option>
                <option value=">" ${ condOption == ">" ? "selected" : "" }>${ morethan }</option>
                <option value="<" ${ condOption == "<" ? "selected" : "" }>${ lessthan }</option>
            </select>
        </div>
        <div class="field-control quantity">
            <label>${ quantityLabel }</label>
            <input type="number" class="condition-quantity" value="${ typeof quantity ? quantity : 0 }" min="${ condition == "<" ? 1 : 0 }">
        </div>
    </div>
    `;
}

/**
 * Get category options markup.
 * 
 * @since 1.15
 * 
 * @param data 
 */
function category_options( data: number[] = [] ): string {

    const { options } = cart_condition_fields.product_category;
    let markup: string = '';

    for ( let x in options ) {
        markup += `<option value="${ x }" ${ selected_multiple( data , x ) }>${ options[x] }</option>`
    }
        
    return markup;
}