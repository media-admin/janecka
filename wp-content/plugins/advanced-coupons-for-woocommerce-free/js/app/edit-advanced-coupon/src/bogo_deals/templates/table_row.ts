
import category_first_column_template from "./category_first_column";
import product_first_column_template from "./product_first_column";

/**
 * Table single row markup.
 * 
 * @since 1.15
 * 
 * @param data 
 * @param type 
 * @param is_deals 
 */
export default function table_row_template( data: any , type: string , is_deals: string ): string {

    const { quantity , discount_type ,discount_value } = data;
    const priceCol: string = is_deals ? `<td class="price">(${ discount_type }) ${ discount_value }</td>` : "";

    return `
    <tr>
        ${ get_first_column_markup( data , type ) }
        <td class="quantity">${ quantity }</td>
        ${ priceCol }
        <td class="actions">
            <a class="edit" href="javascript:void(0)"><span class="dashicons dashicons-edit"></span></a>
            <a class="remove" href="javascript:void(0)"><span class="dashicons dashicons-no"></span></a>
        </td>
    </tr>`;
}

/**
 * Get first column markup.
 * 
 * @since 1.15
 * 
 * @param data 
 * @param type 
 */
function get_first_column_markup( data: any, type: string ): string {

    switch ( type ) {
        case "category" :
            return category_first_column_template( data );
        case "product" :
            return product_first_column_template( data );
    }
}