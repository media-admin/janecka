declare var jQuery: any;

const $: any = jQuery;

export function navigate_tabs(): void {
  const $tab: JQuery = $(this),
    $module_block = $tab.closest(".woocommerce").find("#acfw_cart_conditions"),
    tab: string = $tab.data("tab");

  $module_block.find(".panel").hide();
  $module_block.find(`.panel[data-tab="${tab}"]`).show();
  set_active_tab($tab);
}

function set_active_tab($tab: JQuery): void {
  $(".acfw-cart-condition-tabs li").removeClass("active");
  $tab.closest("li").addClass("active");
}
