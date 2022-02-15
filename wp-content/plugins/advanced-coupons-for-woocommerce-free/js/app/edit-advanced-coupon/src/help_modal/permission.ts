import { fetchContentDataUpdateModal } from "./modal";
import placeholderTemplate from "./templates/placeholder_template";

declare var ajaxurl: any;
declare var jQuery: any;
declare var vex: any;
declare var acfw_edit_coupon: any;

const $ = jQuery;

export function allowFetchPermission() {
  const { _secure_nonce } = acfw_edit_coupon.help_modal;
  const $button = jQuery(this);
  const $div = $button.closest("#acfw-help-modal-permission-request");
  const module = $div.data("module");

  $div.replaceWith(placeholderTemplate());

  $.post(
    ajaxurl,
    {
      action: "acfw_save_fetch_help_permission",
      _nonce: _secure_nonce,
    },
    () => {
      fetchContentDataUpdateModal(module);
      acfw_edit_coupon.help_modal.allow_fetch = true;
    },
    "json"
  );
}

export function closePermisionModal() {
  vex.closeAll();
}
