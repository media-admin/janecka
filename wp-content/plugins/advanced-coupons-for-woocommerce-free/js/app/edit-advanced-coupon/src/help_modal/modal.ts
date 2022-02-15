import modalTemplate, { IHelpModalArguments } from "./templates/modal_template";
import errorTemplate from "./templates/error_template";
import permissionRequestTemplate from "./templates/permission_template";
import placeholderTemplate from "./templates/placeholder_template";
import loadVideosOembed, { loadVideoGalleryContentCache } from "./videos";

declare var ajaxurl: string;
declare var jQuery: any;
declare var vex: any;
declare var acfw_edit_coupon: any;

const $ = jQuery;
const dataCache: IHelpModalArguments[] = []; // state variable that holds cached data.

/**
 * Show the help modal content.
 *
 * @since 1.5
 */
export function showHelpModal() {
  const { allow_fetch } = acfw_edit_coupon.help_modal;
  const $this = $(this);
  const module = $this.closest(".acfw-help-link").data("module");

  // display vex dialog with placeholder div and spinner.
  vex.dialog.open({
    unsafeMessage: `
      <a class="vex-close" href="javascript:void(0);"></a>
      ${allow_fetch ? placeholderTemplate() : permissionRequestTemplate(module)}
    `,
    className: "vex-theme-plain acfw-help-vex",
    showCloseButton: false,
    buttons: {},
  });

  // fetch data and update modal content.
  if (allow_fetch) fetchContentDataUpdateModal(module);
}

/**
 * Replace content in vex modal.
 * make sure placeholder still exists before replacing it with relative content.
 *
 * @param {string} markupTemplate
 */
function replaceModalContent(markupTemplate: string) {
  const $placeholder = $("#acfw-help-modal-placeholder");
  if ($placeholder.length) {
    $placeholder.replaceWith(markupTemplate);
    $(".acfw-help-vex")
      .find("main a,aside a")
      .attr("target", "_blank")
      .attr("rel", "noopener noreferrer nofollow");
  }
}

/**
 * Fetch content data from JSON url and update modal content.
 *
 * @since 1.5
 *
 * @param {string} module
 */
export function fetchContentDataUpdateModal(module: string) {
  const { _secure_nonce } = acfw_edit_coupon.help_modal;
  let cachedData: IHelpModalArguments | null = null;

  // serve cached data and videos embeds for module if already present.
  if ((cachedData = getCachedData(module))) {
    replaceModalContent(modalTemplate(cachedData));
    loadVideoGalleryContentCache(cachedData.left.videos, module);
    return;
  }

  $.post(
    ajaxurl,
    {
      action: "acfw_fetch_help_data",
      module: module,
      _nonce: _secure_nonce,
    },
    (data: IHelpModalArguments) => {
      dataCache.push(data);
      replaceModalContent(modalTemplate(data));
      loadVideosOembed(data.left.videos, module);
    },
    "json"
  ).fail((data: any) => {
    replaceModalContent(errorTemplate(data.responseJSON));
  });
}

/**
 * Get cached data for module.
 *
 * @since 1.5
 *
 * @param {string} module
 */
function getCachedData(module: string) {
  const index = dataCache.findIndex(
    (data: IHelpModalArguments) => data.target === module
  );

  return index >= 0 ? dataCache[index] : null;
}
