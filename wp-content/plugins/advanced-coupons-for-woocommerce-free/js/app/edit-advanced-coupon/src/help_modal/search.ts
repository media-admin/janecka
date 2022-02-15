import {
  searchResultsTemplate,
  ISearchResult,
  ISearchArgs,
} from "./templates/search_widget_template";

declare var ajaxurl: string;
declare var jQuery: any;
declare var acfw_edit_coupon: any;

interface ISearchQueryArgs {
  action: string;
  term: string;
  _nonce: string;
}

interface ISearchResultsCache {
  term: string;
  results: ISearchResult[];
}

const $ = jQuery;
let searchTimeoutState: any = null;
let searchResultsCache: ISearchResultsCache | null = null;

/**
 * Trigger search event when on keyboard input.
 *
 * @since 1.5
 */
export function triggerSearchEvent() {
  const $button = $(this);
  const $wrap = $button.closest(".search-widget");
  const $input = $wrap.find("input.search-input");
  const $results = $wrap.find(".search-results");
  const { labels, images_url, _secure_nonce } = acfw_edit_coupon.help_modal;
  const term = $input.val();

  // if search input is emptied, clear state and remove results.
  if (!term) {
    clearTimeout(searchTimeoutState);
    searchTimeoutState = null;
    $results.height(0).html("");
    $wrap.removeClass("show-clear");
    return;
  }

  $wrap.addClass("show-clear");

  // run search query.
  doSearchQuery(
    {
      action: $input.data("action"),
      term: $input.val(),
      _nonce: _secure_nonce,
    },

    // callback to run before running ajax.
    () => {
      $results
        .height(35)
        .html(
          `<div class="loading"><img src="${images_url}spinner.gif" /></div>`
        );
    },

    // callback to run to process results.
    (data: ISearchResult[]) => {
      if (!$wrap.hasClass("show-clear")) {
        return;
      }

      if (data.length) {
        searchResultsCache = { term, results: data };
        $results.html(searchResultsTemplate(data, term));
      } else {
        $results.html(
          `<div class="no-result">${labels.search_no_results}</div>`
        );
      }

      // adjust the height to match the content (css transform).
      $results.height($results.find("ul,.no-result").height() + 3);
    }
  );
}

/**
 * Function that handles the search query scheduling (delay 1 second after no more keyboard inputs).
 *
 * @since 1.5
 *
 * @param {ISearchArgs} args
 * @param {CallableFunction} beforeCB
 * @param {CallableFunction} successCB
 */
function doSearchQuery(
  args: ISearchQueryArgs,
  beforeCB: CallableFunction,
  successCB: CallableFunction
) {
  // clear saved timeout everytime search term value is changed.
  if (searchTimeoutState) {
    clearTimeout(searchTimeoutState);
    searchTimeoutState = null;
  }

  // if search term is the same from previous, then serve cached results.
  if (args.term === searchResultsCache?.term) {
    successCB(searchResultsCache.results);
    return;
  }

  // schedule AJAX query after 1 second.
  searchTimeoutState = setTimeout(() => {
    beforeCB();
    $.post(ajaxurl, args, successCB, "json");
  }, 1000);
}

/**
 * Clear the search text and hide results list.
 *
 * @since 1.5
 */
export function clearSearchEvent() {
  const $button = $(this);
  const $wrap = $button.closest(".search-widget");
  const $input = $wrap.find("input.search-input");
  const $results = $wrap.find(".search-results");

  clearTimeout(searchTimeoutState);
  searchTimeoutState = null;
  $input.val("");
  $results.height(0).html("");
  $wrap.removeClass("show-clear");
}
