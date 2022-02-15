import searchWidgetTemplate, { ISearchArgs } from "./search_widget_template";

declare var acfw_edit_coupon: any;

export interface ILinksListArguments {
  title: string;
  className: string;
  links: ILink[];
  search?: ISearchArgs;
}

export interface ILink {
  title: string;
  url: string;
  freeOnly?: boolean;
  premiumUrl?: string;
}

/**
 * Links list template markup.
 *
 * @since 1.5
 *
 * @param {ILinksListArguments} args
 */
export default function linksListTemplate(args: ILinksListArguments) {
  const { title, className } = args;
  let { links } = args;
  const { is_premium } = acfw_edit_coupon.help_modal;

  if (is_premium) {
    links = links.filter(
      (l: ILink) => l?.freeOnly === undefined || !l?.freeOnly
    );
  }

  return `
    <div class="links-list ${className}">
      <h3>${title}</h3>

      ${args?.search ? searchWidgetTemplate(args?.search) : ""}

      <ul>
        ${links.map((link) => `<li>${generateLink(link)}</li>`).join("")}
      </ul>
    </div>
  `;
}

/**
 * Generate link <a> markup.
 *
 * @since 1.5
 *
 * @param {ILink} link
 */
function generateLink(link: ILink) {
  const { title, url } = link;
  const { is_premium } = acfw_edit_coupon.help_modal;

  return `<a href="${
    is_premium && link?.premiumUrl ? link?.premiumUrl : url
  }">${title}</a>`;
}
