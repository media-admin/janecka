declare var acfw_edit_coupon: any;

export interface IVideoEmbed {
  url: string;
  videoid: string;
  embed: string;
  thumbnail: string;
  title?: string;
}

/**
 * Video gallery template.
 *
 * @param {IVideoEmbed[]} videoEmbeds
 * @returns
 */
export default function videoGalleryTemplate(videoEmbeds: IVideoEmbed[]) {
  return `
  <div class="acfw-help-video-gallery">
    <div class="videos">
      ${
        videoEmbeds[0].embed
          ? videoEmbeds[0].embed
          : noVideoTemplate(videoEmbeds[0])
      }
    </div>
    ${
      videoEmbeds.length > 1
        ? `
      <ul class="thumbnails">
        ${videoEmbeds
          .map(
            (ve, i) => `
            <li ${i === 0 ? `class="active"` : ""}>
              <a href="javascript:void(0);" data-videoid="${ve.videoid}">
                <img src="${ve.thumbnail}" title="${ve.title}" />
              </a>
              <span class="title">${ve.title}</span>
            </li>
            `
          )
          .join("")}
      </ul>
      `
        : ""
    }
    
  </div>
  `;
}

export function noVideoTemplate(videoEmbed: IVideoEmbed) {
  return `
  <div class="no-video" style="background-image:url(${videoEmbed.thumbnail});">
    <a href="${videoEmbed.url}" rel="noreferer noopener" >
      <span>${acfw_edit_coupon.help_modal.labels.no_video}</span>
    </a>
  </div>
  `;
}
