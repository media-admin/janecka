import { toggle_editing_mode } from "./toggles";
import condition_logic_template from "./templates/condition_logic";
import condition_group_template from "./templates/condition_group";

declare var jQuery: any;
declare var acfw_edit_coupon: any;

var $: any = jQuery;

/**
 * Add condition group callback.
 *
 * @since 1.0.0
 */
export function add_condition_group() {
  const condition_groups: HTMLElement = document.querySelector(
    "#acfw_cart_conditions .condition-data-wrap .condition-groups"
  );

  if ($(condition_groups).find(".condition-group").length)
    $(condition_groups).append(condition_logic_template("or"));

  $(condition_groups).find(".no-condition-group").remove();
  $(condition_groups).append(condition_group_template());

  $(this).blur();
  $(condition_groups)
    .find(".condition-group.new .add-condition-trigger")
    .trigger("click");
  $(condition_groups).find(".condition-group").removeClass("new");

  toggle_editing_mode(true);
}

/**
 * Remove condition group callback.
 *
 * @since 1.0.0
 */
export function remove_condition_group() {
  const $button: JQuery = $(this),
    module_block: HTMLElement = document.querySelector("#acfw_cart_conditions"),
    $condition_group: JQuery = $button.closest(".condition-group"),
    $cgroup_logic: JQuery =
      $condition_group.index() == 0
        ? $condition_group.next(".condition-group-logic")
        : $condition_group.prev(".condition-group-logic");

  const { no_condition_group_msg } = acfw_edit_coupon;

  $condition_group.remove();
  $cgroup_logic.remove();

  if ($(module_block).find(".condition-group").length < 1) {
    $(module_block).find(".condition-groups").append(`
            <div class="no-condition-group">
                ${no_condition_group_msg}
            </div>
        `);
  }

  toggle_editing_mode(true);
}
