import { add_condition_group, remove_condition_group } from "./groups";
import {
  add_condition_field,
  remove_condition_field,
  get_condition_field,
  is_condition_field,
} from "./fields";
import { save_cart_conditions, save_on_coupon_publish } from "./save";
import {
  toggle_add_condition_form,
  toggle_editing_mode,
  popuplate_taxonomy_term_options,
  popuplate_shipping_region_options,
} from "./toggles";
import {
  trigger_add_table_row_form,
  trigger_edit_table_row_form,
  add_edit_table_row,
  cancel_add_edit_table_row,
  product_table_remove_product,
} from "./table";
import {
  restrict_number_min_value,
  update_input_minimum_value,
  update_has_ordered_before_condition_label,
  validate_price_field_value,
  remove_leading_zeros,
  condition_value_type_change,
} from "./restrictions";

import logic_template from "./templates/logic";
import condition_logic_template from "./templates/condition_logic";
import condition_group_template from "./templates/condition_group";
import { navigate_tabs } from "./tabs";

declare var jQuery: any;
declare var acfw_edit_coupon: any;

const $: any = jQuery;
const module_block: HTMLElement = document.querySelector(
  "#acfw_cart_conditions"
);

/**
 * Add free products module events script.
 *
 * @since 1.0.0
 */
export default function cart_conditions_module_events() {
  $(module_block).on("click", "#add-cart-condition-group", add_condition_group);
  $(module_block).on(
    "click",
    "a.remove-condition-group",
    remove_condition_group
  );
  $(module_block).on("click", ".add-condition-field", add_condition_field);
  $(module_block).on(
    "click",
    "a.remove-condition-field",
    remove_condition_field
  );
  $(module_block).on(
    "click",
    ".add-condition-trigger",
    toggle_add_condition_form
  );
  $(module_block).on(
    "click",
    ".cancel-add-condition-field",
    toggle_add_condition_form
  );
  $(module_block).on(
    "click",
    ".acfw-styled-table tfoot .add-table-row",
    trigger_add_table_row_form
  );
  $(module_block).on(
    "click",
    ".acfw-styled-table td.actions a.edit",
    trigger_edit_table_row_form
  );
  $(module_block).on(
    "click",
    "tr.add-edit-form .actions .condition-product-add",
    add_edit_table_row
  );
  $(module_block).on(
    "click",
    "tr.add-edit-form .actions .cancel",
    cancel_add_edit_table_row
  );
  $(module_block).on(
    "click",
    ".acfw-styled-table td.actions a.remove",
    product_table_remove_product
  );
  $(module_block).on("click", "#save-cart-conditions", save_cart_conditions);
  $(module_block).on(
    "change",
    "input[type='number']",
    restrict_number_min_value
  );
  $(module_block).on(
    "change",
    "select.condition-select",
    update_input_minimum_value
  );
  $(module_block).on(
    "change",
    "select.has-ordered-before-type",
    update_has_ordered_before_condition_label
  );
  $(module_block).on("change", ".wc_input_price", validate_price_field_value);
  $(module_block).on(
    "change",
    "select.custom-taxonomy",
    popuplate_taxonomy_term_options
  );
  $(module_block).on(
    "change",
    ".shipping-zone-region-field select.condition-select",
    popuplate_shipping_region_options
  );
  $(module_block).on("blur", ".wc_input_price", remove_leading_zeros);
  $(module_block).on(
    "change",
    "select.value-type",
    condition_value_type_change
  );
  $("form#post").on("submit", save_on_coupon_publish);

  $(module_block).find("select").trigger("change");
  $(module_block).css("min-height", $("#coupon_options").height());

  $(module_block).on("change", "input,select,textarea", () => {
    toggle_editing_mode(true);
  });

  $(module_block)
    .find(".condition-groups")
    .each(function () {
      if ($(this).find(".condition-group-fields").length) $(this).show();
    });

  $(".acfw-cart-condition-tabs").on("click", "li a", navigate_tabs);

  setTimeout(init_cart_conditions, 500);
}

/**
 * Init cart conditions. Save the current cart conditions data.
 *
 * @since 1.0.0
 */
function init_cart_conditions(): void {
  const condition_groups = module_block.querySelector(".condition-groups");
  const data: any = $(module_block).data("cart_conditions");

  const asyncFunc = async () => {
    let cart_conditions: any[] = [];
    let markup: string = "";

    if (data.length) {
      for (let row of data) {
        if (row.type == "group") {
          const fields_markup = load_condition_group_fields(row.fields);
          markup += condition_group_template(fields_markup);
        } else markup += condition_logic_template(row.value);
      }
    } else markup = condition_group_template();

    $(condition_groups).html(markup);
    $(condition_groups).find("input,select").trigger("change");

    $(condition_groups)
      .find(".condition-group.new .add-condition-trigger")
      .trigger("click");
    $(condition_groups).find(".condition-group").removeClass("new");
  };

  asyncFunc().then(() => {
    setTimeout(() => $("body").trigger("wc-enhanced-select-init"), 1500);
    toggle_editing_mode(false);
  });
}

/**
 * Load condition group fields.
 *
 * @since 1.0.0
 *
 * @param fields
 */
function load_condition_group_fields(fields: any[]): string {
  let markup: string = "";
  let temp: string = "";
  let addLogic: boolean = true;

  for (let field of fields) {
    const { type, data } = field;

    if (!is_condition_field(type)) {
      addLogic = false;
      continue;
    }

    if (type == "logic") {
      temp = addLogic ? logic_template(data) : "";
      continue;
    }

    const field_markup = get_condition_field(type, data);

    if (field_markup) {
      markup += temp + field_markup;
      addLogic = true;
    } else {
      addLogic = false;
    }

    temp = "";
  }

  return markup;
}
