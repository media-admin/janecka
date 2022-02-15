import { esc_attr } from "../../helper";

/**
 * Category table row first column template.
 * 
 * @param data 
 */
export default function category_first_column_template( data: any ): string {

    const { category_id , category_label } = data;

    return `
    <td class="category category-${ category_id } object" data-category="${ esc_attr( JSON.stringify(data) ) }">
        ${ category_label }
    </td>
    `;
}