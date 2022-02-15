import linksListTemplate, { ILink } from "./links_list_template";
import { ISearchArgs } from "./search_widget_template";

declare var acfw_edit_coupon: any;

interface IHelpMainContent {
  title: string;
  content: string;
  videos: ILink[];
}

interface IHelpAsideContent {
  links: ILink[];
  kbs: ILink[];
  tutorials: ILink[];
}

export interface IHelpModalArguments {
  target: string;
  left: IHelpMainContent;
  right: IHelpAsideContent;
}

/**
 * Help modal template markup.
 *
 * @since 1.5
 *
 * @param {IHelpModalArguments} args
 */
export default function modalTemplate(args: IHelpModalArguments) {
  const { labels, images_url, is_premium } = acfw_edit_coupon.help_modal;
  const {
    target,
    left: { title, content, videos },
    right: { links, kbs, tutorials },
  } = args;
  const kbSearchArgs: ISearchArgs = {
    slug: "kb-articles",
    action: "acfw_search_help_kb_articles",
    placeholder: labels.search_placeholder,
  };
  const source = is_premium ? "acfwp" : "acfwf";
  const upgradeLink = `https://advancedcouponsplugin.com/pricing/?utm_source=acfwf&utm_medium=help_modal&utm_campaign=help_modal_${target}_upgrade_link`;
  const logoUrl = `https://advancedcouponsplugin.com/?utm_source=${source}&utm_medium=help_modal&utm_campaign=help_brand_link_${target}`;

  return `
    <div class="header">
      <div class="logo">
        <a href="${logoUrl}" target="_blank"><img src="${images_url}acfw-logo.png" alt="Advanced Coupons logo" /></a>
      </div>
      ${
        !is_premium
          ? `
        <div class="upgrade-link">
          <a href="${upgradeLink}" target="_blank">
            <span>${labels.upgrade_to_premium}</span>
            <i class="dashicons dashicons-external"></i>
          </a>
        </div>
      `
          : ""
      }
    </div>
    <main>
      <h1 class="title">${title}</h1>
      <div class="content">
        ${content}
      </div>
      ${
        videos.length
          ? `<div class="video-gallery-placeholder"><img src="${images_url}spinner-2x.gif" alt="Loading videos" /><p>${labels.loading_videos}</p></div>`
          : ""
      }
    </main>
    <aside>
      ${linksListTemplate({
        title: labels.rel_links,
        className: "relevant-links",
        links: links,
      })}
      ${linksListTemplate({
        title: labels.kb_articles,
        className: "knowledge-base-articles",
        links: kbs,
        search: kbSearchArgs,
      })}
      ${linksListTemplate({
        title: labels.tut_guides,
        className: "tutorials-guides",
        links: tutorials,
      })}
    </aside>
  `;
}
