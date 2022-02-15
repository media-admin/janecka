import conditionFieldOptionsTemplate from "./condition_field_options";

declare var jQuery: any;
declare var acfw_edit_coupon: any;

/**
 * Return condition group template markup.
 *
 * @since 1.15
 */
export default function condition_group_template(
  fields_markup: string = ""
): string {
  const { empty_cart_conditions_field } = acfw_edit_coupon;
  const cgroup_actions: string = conditionFieldOptionsTemplate();
  const empty_conditions_markup: string = `<div class="empty-conditions">${empty_cart_conditions_field}</div>`;

  return `
        <div class="condition-group ${!fields_markup ? "new" : ""}">
            <a class="remove-condition-group" href="javascript:void(0);">
                <i class="dashicons dashicons-trash"></i>
            </a>
            <div class="condition-group-fields">
                ${fields_markup ? fields_markup : empty_conditions_markup}
            </div>
            ${cgroup_actions}
        </div>
    `;
}
