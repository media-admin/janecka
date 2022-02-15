declare var acfw_edit_coupon: any;

export interface ISearchArgs {
  slug: string;
  action: string;
  placeholder: string;
}

export interface ISearchResult {
  url: string;
  title: String;
}

/**
 * Search widget template.
 *
 * @param {ISearchArgs} search
 * @returns
 */
export default function searchWidgetTemplate(search: ISearchArgs) {
  const { slug, action, placeholder } = search;

  return `
    <div class="search-widget search-${slug}">
      <input class="search-input" type="text" placeholder="${placeholder}" data-action="${action}" />
      <button type="button" class="clear-search dashicons-before dashicons-no-alt"></button>
      <div class="search-results"></div>
    </div>
  `;
}

/**
 * Search results template.
 *
 * @param {ISearchResult[]} results
 * @param {string} term
 * @returns
 */
export function searchResultsTemplate(results: ISearchResult[], term: string) {
  const list: string = results
    .map(
      ({ url, title }) =>
        `<li><a href="${generateUrl(
          url,
          term
        )}" rel="noopener noreferrer nofollow" target="_blank">${title}</a></li>`
    )
    .join("");

  return `<ul>${list}</ul>`;
}

/**
 * Add UTM parameters to KB article URL.
 *
 * @param {string} url
 * @param {string} term
 * @returns
 */
function generateUrl(url: string, term: string) {
  return `${url}?utm_source=acfwf&utm_medium=help_modal&utm_campaign=kb_search&utm_term=${term}`;
}
