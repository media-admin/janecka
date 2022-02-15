declare var acfw_edit_coupon: any;

export default function permissionRequestTemplate(module: string) {
  const { images_url } = acfw_edit_coupon.help_modal;
  const {
    permission_request,
    allow,
    cancel,
  } = acfw_edit_coupon.help_modal.labels;
  return `
  <div id="acfw-help-modal-permission-request" data-module="${module}">
    <img src="${images_url}acfw-logo.png" />
    <p>${permission_request}</p>
    <p class="actions">
      <button class="button-primary allow" type="button">${allow}</button>
      <button class="button cancel" type="button">${cancel}</button>
    </p>
  </div>
  `;
}
