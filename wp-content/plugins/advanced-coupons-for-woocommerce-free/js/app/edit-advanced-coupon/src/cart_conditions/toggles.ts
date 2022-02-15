import { enhanceConditionFieldSelector } from "./templates/condition_field_options";

declare var jQuery: any;
const $ = jQuery;
const module_block: HTMLElement = document.querySelector(
  "#acfw_cart_conditions"
);

/**
 * Toggle add condition form.
 *
 * @since 1.0.0
 */
export function toggle_add_condition_form(): void {
  const $button: JQuery = $(this),
    $condition_actions: JQuery = $button.closest("div.condition-group-actions");

  if ($button.hasClass("add-condition-trigger")) {
    $condition_actions.find(".add-condition-form").show();
    enhanceConditionFieldSelector();
    $button.hide();
  } else {
    $condition_actions.find(".add-condition-form").hide();
    $condition_actions.find(".add-condition-trigger").show();
  }
}

/**
 * Toggle editing mode.
 *
 * @since 1.0.0
 */
export function toggle_editing_mode(toggle: boolean): void {
  $(module_block).data("editing", toggle);
  $(module_block).find("#save-cart-conditions").prop("disabled", !toggle);
}

/**
 * Populate custom taxonomy options.
 *
 * @since 1.4
 */
export function popuplate_taxonomy_term_options() {
  const $taxonomy: JQuery = $(this),
    $condition_block: JQuery = $taxonomy.closest(".condition-field"),
    $tax_terms: JQuery = $condition_block.find("select.condition-value"),
    taxonomy: string = $taxonomy.val() + "",
    terms: any[] = $condition_block.data("selected_terms"),
    tax_options: any[] = $(module_block).data("custom_tax_options"),
    taxonomy_data: any[] = tax_options.filter((t) => t.slug == taxonomy),
    taxonomy_terms: any[] = taxonomy_data.length ? taxonomy_data[0].terms : [];

  $tax_terms.val(null).find("option").remove();

  if (!taxonomy_terms.length) return;

  for (let term of taxonomy_terms) {
    let { name, term_id } = term;
    let optionObj: HTMLOptionElement = new Option(name, term_id);
    $tax_terms.append(optionObj);
  }

  $tax_terms.val(terms).trigger("change");
}

/**
 * Populate shipping zone region options.
 *
 * @since 1.4
 */
export function popuplate_shipping_region_options() {
  const $shipping_zone: JQuery = $(this),
    $condition_block: JQuery = $shipping_zone.closest(".condition-field"),
    $shipping_region: JQuery = $condition_block.find("select.condition-value"),
    zone_id: string = $shipping_zone.val() + "";

  if (!zone_id) return;

  const regions: any = $condition_block.data("regions"),
    region_labels: any = $(module_block).data("shipping_regions"),
    region_options: any = $condition_block.data("zones")[zone_id]["regions"];

  $shipping_region.val(null).find("option").remove();

  if (typeof region_options != "object" || !region_options.length) return;

  for (let code of region_options) {
    const optionObj: HTMLOptionElement = new Option(region_labels[code], code);
    $shipping_region.append(optionObj);
  }

  $shipping_region.val(regions).trigger("change");
}
