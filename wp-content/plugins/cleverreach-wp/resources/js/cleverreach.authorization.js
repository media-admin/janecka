var CleverReach = window['CleverReach'] || {};

/**
 * Checks connection status
 */
(function () {

	/**
	 * Configurations and constants
	 *
	 * @type {{get}}
	 */
	var config = (function () {
		var constants = {
			CHECK_STATUS_URL: '',
			STATUS_FINISHED: 'finished'
		};

		return {
			get: function (name) {
				return constants[name];
			}
		};
	})();

	function AuthorizationConstructor(authUrl, checkStatusUrl) {
		this.getStatus = function(successCallback) {
			var self = this;
			CleverReach.Ajax.post(
				checkStatusUrl + config.get( 'CHECK_STATUS_URL' ),
				null,
				function (response) {
					if (response.status === config.get( 'STATUS_FINISHED' )) {
						successCallback();
					} else {
						self.getStatus( successCallback );
					}
				},
				'json',
				true
			);
		}
	}

	CleverReach.Authorization = AuthorizationConstructor;
})();
