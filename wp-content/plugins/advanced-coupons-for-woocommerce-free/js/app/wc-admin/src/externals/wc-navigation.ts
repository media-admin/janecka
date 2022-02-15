declare var wc: any;
const navigation = wc.navigation;

/**
 * Export the WC admin navigation package.
 * https://github.com/woocommerce/woocommerce-admin/tree/main/packages/navigation
 */
export default navigation;

export const { getHistory, getPath } = navigation;
