import videoGalleryTemplate, {
  noVideoTemplate,
  IVideoEmbed,
} from "./templates/video_gallery_template";

declare var ajaxurl: string;
declare var acfw_edit_coupon: any;

const $ = jQuery;
let videoEmbedsState: IVideoEmbed[] = [];
let videoModuleState: string = "";

export interface IHelpVideo {
  title: string;
  url: string;
  embed?: string;
}

/**
 * Fetch Videos oembed markups via AJAX and update video gallery content.
 *
 * @since 1.5
 *
 * @param {IHelpVideo[]} videos
 * @returns
 */
export default function loadVideosOembed(videos: IHelpVideo[], module: string) {
  const { _secure_nonce } = acfw_edit_coupon.help_modal;

  // skip if there are no videos to load.
  if (!videos.length) return;

  $.post(
    ajaxurl,
    {
      action: "acfw_get_help_videos_oembed",
      urls: videos.map((v) => v.url),
      width: $(".acfw-help-vex .vex-content main").width(),
      _nonce: _secure_nonce,
    },
    (videoEmbeds: IVideoEmbed[]) => {
      // map video title to embeds data.
      videoEmbeds = videoEmbeds.map((ve) => {
        const index = videos.findIndex((v) => v.url === ve.url);
        ve.title = index >= 0 ? videos[index].title : "";
        return ve;
      });

      // update state variable for video embeds.
      videoEmbedsState = videoEmbeds;
      videoModuleState = module;

      updateVideoGalleryContent(videoEmbeds);
    },
    "json"
  );
}

/**
 * Load video gallery content from cached data if present.
 *
 * @since 1.5
 *
 * @param {IHelpVideo[]} videos
 * @returns
 */
export function loadVideoGalleryContentCache(
  videos: IHelpVideo[],
  module: string
) {
  if (!videoEmbedsState.length || module !== videoModuleState) {
    loadVideosOembed(videos, module);
    return;
  }

  updateVideoGalleryContent(videoEmbedsState);
}

/**
 * Update video gallery content markup.
 *
 * @since 1.5
 *
 * @param {IVideoEmbed[]} videoEmbeds
 */
function updateVideoGalleryContent(videoEmbeds: IVideoEmbed[]) {
  const $placeholder = $(".acfw-help-vex .video-gallery-placeholder");

  // replace placeholder with video gallery markup.
  if ($placeholder.length) {
    $placeholder.replaceWith(videoGalleryTemplate(videoEmbeds));
  }
}

/**
 * Switch active video displayed when thumbnail is clicked and autoplay video.
 *
 * @since 1.5
 */
export function switchActiveVideo() {
  const $this = jQuery(this);
  const $li = $this.closest("li");
  const $wrap = $this.closest(".acfw-help-video-gallery");
  const $video = $wrap.find(".videos");
  const $thumbs = $wrap.find("ul.thumbnails li");
  const videoId = $this.data("videoid");

  // skip if thumbnail is for current active video.
  if ($li.hasClass("active")) {
    return;
  }

  // switch active class to clicked thumbnail.
  $thumbs.removeClass("active");
  $li.addClass("active");

  // search index of video ombed markup of matching video ID.
  const index = videoEmbedsState.findIndex((ve) => ve.videoid === videoId);

  // replace main video oembed markup.
  if (index >= 0) {
    $video.html(
      videoEmbedsState[index].embed
        ? videoEmbedsState[index].embed.replace("oembed", "oembed&autoplay=1")
        : noVideoTemplate(videoEmbedsState[index])
    );
  }
}
