(function () {
	document.addEventListener(
		"DOMContentLoaded",
		function (event) {
			var buildFirstEmailUrl = document.getElementById( 'cr-build-first-email-url' ).value,
			retrySyncUrl           = document.getElementById( 'cr-retry-sync-url' ).value,
			buildEmailUrl          = document.getElementById( 'cr-build-email-url' ).value,
			buildEmailButton       = document.getElementById( 'cr-buildEmail' ),
			retrySynchronization   = document.getElementById( 'cr-retrySync' );

			if (buildEmailButton) {
				buildEmailButton.addEventListener(
					'click',
					function () {
						startBuildingEmail( buildEmailUrl + '#login' );
					}
				);
			} else {
				retrySynchronization.addEventListener(
					'click',
					function () {
						sendAjax( retrySyncUrl );
					}
				);
			}

			function startBuildingEmail(buildEmailUrl) {
				sendAjax( buildFirstEmailUrl );
				var win = window.open( buildEmailUrl, '_blank' );
				win.focus();
			}

			function sendAjax(url, callback) {
				CleverReach.Ajax.post(
					url,
					null,
					function (response) {
						if (response.status === 'success') {
							if (callback) {
								callback();
							} else {
								location.reload();
							}
						}
					},
					'json',
					true
				);
			}
		}
	);
})();
