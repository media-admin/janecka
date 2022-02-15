(function () {
	'use strict';

	document.addEventListener(
		"DOMContentLoaded",
		function (event) {

			function init() {
				attachRedirectButtonClickHandler();
			}

			function attachRedirectButtonClickHandler() {
				var e = document.querySelector( '[data-success-panel-start-initial-sync]' );
				if (e.addEventListener) {
					e.addEventListener( "click", doRedirect, false );
				} else if (e.attachEvent) {
					e.attachEvent( "click", doRedirect );
				}
			}

			function doRedirect() {
				var defaultNewsletterStatusElements = document.getElementsByName( 'cr-newsletterStatus' ),
				defaultNewsletterStatusValue        = 'none',
				configurationUrl                    = document.getElementById( 'cr-configuration' ).value;

				for (var i = 0, length = defaultNewsletterStatusElements.length; i < length; i++) {
					if (defaultNewsletterStatusElements[i].checked) {
						defaultNewsletterStatusValue = defaultNewsletterStatusElements[i].value;
						break;
					}
				}

				CleverReach.Ajax.post(
					configurationUrl,
					{
						newsletterStatus: defaultNewsletterStatusValue
					},
					function (response) {
						if (response.status === 'success') {
							location.reload();
						} else {
							alert( response.message );
						}
					},
					'json',
					true
				);
			}

			init();
		}
	);
})();
