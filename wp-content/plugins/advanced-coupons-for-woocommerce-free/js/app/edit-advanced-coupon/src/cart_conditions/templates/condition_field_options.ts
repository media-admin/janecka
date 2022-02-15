declare var jQuery: any;
declare var acfw_edit_coupon: any;

interface IFieldGroup {
  group: string;
  label: string;
}

interface IConditionFieldOption {
  key: string;
  group: string;
  title: string;
}

export default function conditionFieldOptionsTemplate() {
  const {
    cart_condition_fields,
    cart_condition_field_groups: fieldGroups,
    product_table_buttons: btnLabels,
    add_new_and_rule,
  } = acfw_edit_coupon;

  const conditionFields = Object.keys(cart_condition_fields).map(
    (key) => cart_condition_fields[key]
  );

  return `
    <div class="condition-group-actions">
      <div class="add-condition-form" style="display: none;">
        <select class="condition-types" data-placeholder="Search/select condition fields">
          <option value="">Search</option>
          ${fieldGroups
            .map((f: IFieldGroup) =>
              conditionFieldGroupTemplate(f, conditionFields)
            )
            .join("")}
        </select>
        <button type="button" class="add-condition-field button-primary">${
          btnLabels.add
        }</button>
        <button type="button" class="cancel-add-condition-field button">${
          btnLabels.cancel
        }</button>
      </div>
      <button type="button" class="add-condition-trigger button">
        <i class="dashicons dashicons-plus"></i>
        ${add_new_and_rule}
      </button>
    </div>
  `;
}

function conditionFieldGroupTemplate(
  fieldGroup: IFieldGroup,
  conditionFields: IConditionFieldOption[]
) {
  const { group, label } = fieldGroup;
  const matchedFields = conditionFields.filter(
    (c: IConditionFieldOption) =>
      c.group === group || (group === "others" && c.group === undefined)
  );

  if (!matchedFields.length) return;

  return `
    <optgroup label="${label}">
      ${matchedFields
        .map((c) => `<option value="${c.key}">${c.title}</option>`)
        .join("")}
    </optgroup>
  `;
}

export function enhanceConditionFieldSelector() {
  jQuery("select.condition-types").select2();
}
