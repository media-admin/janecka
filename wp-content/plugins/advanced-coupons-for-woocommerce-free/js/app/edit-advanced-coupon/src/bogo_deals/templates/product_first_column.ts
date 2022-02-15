import { esc_attr } from "../../helper";

/**
 * Product table row first column template.
 * 
 * @param data 
 */
export default function product_first_column_template( data: any ): string {

    const { product_id , product_label } = data;

    return `
    <td class="product product-${ product_id } object" data-product="${ esc_attr( JSON.stringify(data) ) }">
        ${ product_label }
    </td>
    `
}