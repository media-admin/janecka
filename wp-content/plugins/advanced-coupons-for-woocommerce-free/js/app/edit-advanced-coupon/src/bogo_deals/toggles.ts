import combination_products_template from "./templates/combination_products";
import product_categories_template from "./templates/product_categories";
import specific_products_template from "./templates/specific_products";
import any_products_template from "./templates/any_products";

declare var jQuery: any;
declare var acfw_edit_coupon: any;
declare var vex: any;

const $: any = jQuery;
const module_block: HTMLElement = document.querySelector("#acfw_bogo_deals");

/**
 * Toggle editing mode.
 *
 * @since 1.0.0
 *
 * @param {toggle} bool True to toggle editing mode, false otherwise.
 */
export function toggle_editing_mode(toggle: boolean) {
  $(module_block).data("editing", toggle);
  $(module_block).find("#save-bogo-deals").prop("disabled", !toggle);
}

/**
 * Toggle condition type.
 *
 * @since 1.1.0
 */
export function toggle_block_data_type(e: JQuery.Event) {
  const $select: JQuery = $(this),
    block_type: string = $select.data("block"),
    condition_block: HTMLElement = module_block.querySelector(
      ".bogo-conditions-block"
    ),
    deals_block: HTMLElement = module_block.querySelector(
      ".bogo-product-deals-block"
    ),
    bogo_deals: any = $(module_block).data("bogo_deals"),
    conditions: any = bogo_deals.conditions ? bogo_deals.conditions : null,
    deals: any = bogo_deals.deals ? bogo_deals.deals : null,
    block_data_type: string =
      block_type == "conditions"
        ? bogo_deals.conditions_type
        : bogo_deals.deals_type;

  // get correct block data.
  let block_data: any = null;
  if ("conditions" === block_type)
    block_data =
      bogo_deals.conditions_type == $select.val() ? conditions : block_data;
  else block_data = bogo_deals.deals_type == $select.val() ? deals : block_data;

  const isDeal = block_type == "deals";

  let isPremium = false,
    markup,
    tempBlock;

  switch ($select.val()) {
    case "combination-products":
      markup = combination_products_template(block_data ?? {}, isDeal);
      isPremium = true;
      break;

    case "product-categories":
      markup = product_categories_template(
        block_data ?? [],
        block_data_type,
        isDeal
      );
      isPremium = true;
      break;

    case "any-products":
      markup = any_products_template(block_data ?? {}, isDeal);
      isPremium = true;
      break;

    case "specific-products":
    default:
      markup = specific_products_template(
        block_data ?? [],
        block_data_type,
        isDeal
      );
      isPremium = false;
      break;
  }

  if (block_type == "conditions") {
    $(condition_block)
      .html(markup)
      .find("input.condition-quantity")
      .trigger("change" === e.type ? "change" : "acfw_fetch_data");
    tempBlock = condition_block;
  } else {
    const $multipleDesc = $(module_block).find(".multiple-items-desc");

    if ("specific-products" !== $select.val()) $multipleDesc.show();
    else $multipleDesc.hide();

    $(deals_block)
      .html(markup)
      .find("input.condition-quantity")
      .trigger("change" === e.type ? "change" : "acfw_fetch_data");
    tempBlock = deals_block;
  }

  if (isPremium && undefined !== acfw_edit_coupon.upsell)
    $(tempBlock).addClass("premium-only");
  else $(tempBlock).removeClass("premium-only");

  $("body").trigger("wc-enhanced-select-init");
  $("body").trigger("init_tooltips");
}

/**
 * Force apply type to "Same Product" block when "each-product" is selected as base for Any Products trigger.
 *
 * @since 1.4
 */
export function forceApplyToSameProductForEachProductBase() {
  const $this = $(this);
  const $module = $this.closest("#acfw_bogo_deals");
  const $applySelect = $module.find("#bogo-deals-type");
  const base = $this.val();

  if ("each-product" === base) {
    $applySelect.val("same-products").trigger("change");
  }
}

function validateTriggerApplyCompatibility($select: JQuery, isDeals: boolean) {
  const $module: JQuery = $select.closest("#acfw_bogo_deals");
  const triggerType: string = $module
    .find("select#bogo-condition-type")
    .val()
    .toString();
  const applyType: string = $module
    .find("select#bogo-deals-type")
    .val()
    .toString();
  const baseType = $module
    .find("input[name='acfw_bogo_trigger_base']:checked")
    .val();

  if (
    isDeals &&
    "any-products" === triggerType &&
    "each-product" === baseType &&
    "same-products" !== applyType
  ) {
    $select.val("same-products");
    vex.dialog.alert({
      unsafeMessage: `Any products trigger type based on each product's quantity can only work with <code>Same Products</code> apply type.`,
    });
    return false;
  }

  return true;
}
