declare var acfw_edit_coupon: any;

export default function placeholderTemplate() {
  const { images_url, labels } = acfw_edit_coupon.help_modal;

  return `<div id="acfw-help-modal-placeholder">
    <img src="${images_url}spinner-2x.gif" />
    <p>${labels.loading_content}</p>
  </div>`;
}
