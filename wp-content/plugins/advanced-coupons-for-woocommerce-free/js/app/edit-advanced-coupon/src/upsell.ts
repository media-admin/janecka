declare var jQuery: any;
declare var acfw_edit_coupon: any;
declare var vex: any;

const $: any = jQuery;
let exludeCouponShown = false;

/**
 * Add upsell events script.
 *
 * @since 1.0.0
 */
export default function upsell_events() {
  $("#usage_limit_coupon_data").on(
    "change",
    "#reset_usage_limit_period",
    upsell_advance_usage_limits
  );
  $("#usage_restriction_coupon_data .exclude_coupon_ids_field").on(
    "click change focus",
    "input,select",
    upsell_exclude_coupons_restriction
  );
  $("#acfw-auto-apply-coupon").on(
    "change",
    "#acfw_auto_apply_coupon_field",
    upsell_auto_apply
  );
  $("#acfw_cart_conditions").on(
    "change",
    ".condition-types",
    cart_condition_select_notice
  );
  $("#woocommerce-coupon-data").on(
    "change acfw_load",
    "#discount_type",
    hideGeneralUpsellOnBogo
  );

  $("#woocommerce-coupon-data #discount_type").trigger("acfw_load");
}

/**
 * Usage limits upsell vex dialog.
 *
 * @since 1.1
 */
function upsell_advance_usage_limits() {
  const { usage_limits } = acfw_edit_coupon.upsell;

  vex.dialog.alert({
    unsafeMessage: `<div class="upsell-alert usage-limits">${usage_limits}</div>`,
  });
  $(this).val("none");
}

/**
 * Usage restriction for exclude coupons upsell vex dialog.
 *
 * @since 1.1
 */
function upsell_exclude_coupons_restriction() {
  // prevent duplicate dialogs showing up.
  if (exludeCouponShown) return;
  exludeCouponShown = true;

  const { usage_restriction } = acfw_edit_coupon.upsell;

  vex.dialog.alert({
    unsafeMessage: `<div class="upsell-alert exclude-coupon">${usage_restriction}</div>`,
    afterClose: () => (exludeCouponShown = false),
  });
  $(this).val("");
}

/**
 * Auto apply upsell vex dialog.
 *
 * @since 1.1
 */
function upsell_auto_apply() {
  const { auto_apply } = acfw_edit_coupon.upsell;

  vex.dialog.alert({
    unsafeMessage: `<div class="upsell-alert auto-apply">${auto_apply}</div>`,
  });
  $(this).prop("checked", false);
}

/**
 * Display did you know notice below cart condition selector when premium option is selected.
 *
 * @since 1.6
 */
function cart_condition_select_notice() {
  const $select = $(this);
  const $moduleBlock = $select.closest("#acfw_cart_conditions");
  const $formBlock = $select.closest(".add-condition-form");
  const premiumConditions: string[] = $moduleBlock.data("premium-conditions");
  const $noticeHolder = $moduleBlock.find(".acfw-dyk-notice-holder");

  let $noticeBlock = $formBlock.find(".acfw-dyk-notice");

  if (!$noticeBlock.length) {
    $formBlock.append($noticeHolder.html());
    $noticeBlock = $formBlock.find(".acfw-dyk-notice");
  }

  if ($.inArray($select.val(), premiumConditions) >= 0) {
    $noticeBlock.show();
  } else {
    $noticeBlock.hide();
  }
}

/**
 * Hide did you know notice upsell under general tab when BOGO discount type is selected.
 *
 * @since 3.0
 */
function hideGeneralUpsellOnBogo() {
  const type = $(this).val();

  if ("acfw_bogo" === type) {
    $("p.acfw-dyk-notice-general").hide();
  } else {
    $("p.acfw-dyk-notice-general").show();
  }
}
