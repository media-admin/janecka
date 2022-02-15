/* global jQuery, acfw_notices */

jQuery(document).ready(function($) {

    var $adminNotices = $( ".acfw-admin-notice" );

    $adminNotices.on( "click", "button.notice-dismiss,.acfw-notice-dismiss", function() {
        var $notice = $(this).closest( ".acfw-admin-notice" );
        $notice.fadeOut( "fast" );
        $.post( ajaxurl, {  action : 'acfw_dismiss_admin_notice' , notice : $notice.data( "notice" ) } );
    } );

    // show review request dialog popup
    if ( acfw_notices && acfw_notices.review_link ) {

        vex.defaultOptions.className = 'vex-theme-plain acfw-review-request';

        var ajax_args = {
            url: ajaxurl,
            type: "POST",
            data: { action: "acfw_dismiss_admin_notice", notice: "review_request", response: "snooze" },
            dataType: "json"
        };

        vex.dialog.open({
            overlayClosesOnClick: false,
            escapeButtonCloses: false,
            unsafeMessage: acfw_notices.review_request_content,
            buttons: [
                $.extend({}, vex.dialog.buttons.YES, {
                    className: "vex-dialog-button-primary", 
                    text: acfw_notices.review_actions.review, 
                    click: function ($vexContent, event) {

                        ajax_args.data.response = "dismiss";

                        $.ajax(ajax_args);

                        window.open(acfw_notices.review_link);

                        vex.closeAll();

                    }
                }),
                $.extend({}, vex.dialog.buttons.NO, {
                    className: "vex-dialog-button-snooze", 
                    text: acfw_notices.review_actions.snooze, 
                    click: function ($vexContent, event) {

                        ajax_args.data.response = "snooze";

                        $.ajax(ajax_args);

                        vex.closeAll();

                    }
                }),
                $.extend({}, vex.dialog.buttons.NO, {
                    className: "vex-dialog-dismiss", 
                    text: acfw_notices.review_actions.dismiss, 
                    click: function ($vexContent, event) {

                        ajax_args.data.response = "dismiss";

                        $.ajax(ajax_args);

                        vex.closeAll();

                    }
                })
            ]
        });
    }

});