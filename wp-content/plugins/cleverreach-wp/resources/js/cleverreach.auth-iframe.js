(function () {
	var iframe = window.parent.document.querySelector('.cr-iframe'),
		authUrlElement = window.parent.document.getElementById('cr-auth-url'),
		checkStatusUrl = window.parent.document.getElementById('cr-check-status-url').value;

	showSpinner();

	if (typeof authUrlElement !== 'undefined') {
		var authUrl = authUrlElement.value,
			auth = new parent.CleverReach.Authorization(authUrl, checkStatusUrl);
		auth.getStatus(function () {
			window.parent.location.reload();
		});
	}

	function showSpinner() {
		if (iframe) {
			iframe.style.display = 'none';
		}
		window.parent.document.querySelector('.cr-loader-big').style.display = 'flex';
		window.parent.document.querySelector('.cr-connecting').style.display = 'block';
	}
})();
