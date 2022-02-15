declare var jQuery: any;
var $: any = jQuery;

/**
 * Toggle field events script.
 * Used by Roles Restriction and other similar modules.
 *
 * @since 1.0.0
 */
export default function toggle_fields_events() {
  const $module_block: JQuery = $(".toggle-enable-fields");

  $module_block.on(
    "change",
    ".toggle-trigger-field",
    toggle_disable_enable_fields
  );
  $module_block.find(".toggle-trigger-field").trigger("change");
}

/**
 * Toggle disable/enable fields.
 *
 * @since 1.0.0
 */
function toggle_disable_enable_fields() {
  const $button: JQuery = $(this),
    $module_block: JQuery = $button.closest(".toggle-enable-fields"),
    $fields: JQuery = $module_block.find(
      "select,textarea,input:not(.toggle-trigger-field)"
    ),
    toggle: boolean = !$button.prop("checked");

  $fields.prop("disabled", toggle);

  if (toggle) $fields.closest(".form-field").addClass("disabled");
  else $fields.closest(".form-field").removeClass("disabled");
}
