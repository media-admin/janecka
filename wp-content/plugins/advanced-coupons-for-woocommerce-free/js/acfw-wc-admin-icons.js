jQuery(document).ready(function ($) {
  $(document).on("DOMNodeInserted", function (e) {
    if (
      !$(e.target).hasClass(
        "woocommerce-marketing-recommended-extensions-card__items"
      )
    )
      return;

    var $links = $(e.target).find(
      "a.woocommerce-marketing-recommended-extensions-item"
    );
    var getImage = function (url) {
      if (
        url.includes("wholesalesuiteplugin.com") ||
        url.includes("woocommerce-wholesale-prices")
      )
        return acfwAdminIcons.imgUrl + "Wholesale-Suite-Icon-WC-Marketing.png";

      if (url.includes("advancedcouponsplugin.com"))
        return acfwAdminIcons.imgUrl + "Advanced-Coupons-Icon-WC-Marketing.png";

      return false;
    };

    $links.each(function () {
      var $this = $(this),
        image = getImage($this.prop("href"));

      if (image)
        $this
          .find("svg")
          .replaceWith(
            '<img src="' + image + '" width="100%" height="auto" />'
          );
    });
  });
});
