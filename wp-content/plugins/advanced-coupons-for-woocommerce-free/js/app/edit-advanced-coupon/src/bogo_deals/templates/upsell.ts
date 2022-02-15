declare var acfw_edit_coupon: any;

export default function upsell_template() {

    const { upsell } = acfw_edit_coupon;

    if ( ! upsell ) return '';

    return `
    <div class="upsell">
        ${ upsell.bogo_deals_type }
    </div>`;
}