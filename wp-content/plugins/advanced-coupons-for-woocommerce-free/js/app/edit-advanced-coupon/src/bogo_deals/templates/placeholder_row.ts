declare var acfw_edit_coupon: any;

/**
 * Placeholder table row.
 * 
 * @since 1.15
 * 
 * @param colspan 
 */
export default function placeholder_table_row_template( colspan: number = 3, type: string = "product" ): string {

    const { no_products_added , no_categories_added } = acfw_edit_coupon;
    return `
    <tr class="no-result">
        <td colspan="${ colspan }">${ type == "category" ? no_categories_added : no_products_added }</td>
    </tr>
    `;
}