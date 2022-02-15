import { showHelpModal } from "./modal";
import { switchActiveVideo } from "./videos";
import { triggerSearchEvent, clearSearchEvent } from "./search";
import { allowFetchPermission, closePermisionModal } from "./permission";

declare var jQuery: any;
declare var vex: any;
declare var acfw_edit_coupon: any;

const $: any = jQuery;

/**
 * Register events related to help modal.
 *
 * @since 1.5
 */
export default function helpLinkRegisterEvents() {
  $("#post-body").on("click", ".acfw-help-modal-trigger", showHelpModal);
  $("body").on("click", ".acfw-help-vex .vex-close", () => vex.closeAll());
  $("body").on(
    "click",
    ".acfw-help-vex .acfw-help-video-gallery ul.thumbnails li a",
    switchActiveVideo
  );
  $("body").on(
    "keyup",
    ".acfw-help-vex .search-widget input.search-input",
    triggerSearchEvent
  );

  $("body").on(
    "click",
    ".acfw-help-vex .search-widget button.clear-search",
    clearSearchEvent
  );

  $("body").on(
    "click",
    ".acfw-help-vex #acfw-help-modal-permission-request button.allow",
    allowFetchPermission
  );

  $("body").on(
    "click",
    ".acfw-help-vex #acfw-help-modal-permission-request button.cancel",
    closePermisionModal
  );
}

/**
 * Generate help links in the tabs.
 *
 * @since 1.5
 */
export function generateHelpLinks() {
  const helpLinkDivs: NodeList = document.querySelectorAll(
    "#post-body .acfw-help-link"
  );
  const { help_text, images_url } = acfw_edit_coupon.help_modal;

  helpLinkDivs.forEach((div: HTMLElement) => {
    const markup = `
      <a class="acfw-help-modal-trigger" href="javascript:void(0);">${help_text} 
        <img class="acfw-icon" src="${images_url}help-icon.svg" alt="${help_text}" />
      </a>
    `;

    $(div).html(markup);
  });
}
