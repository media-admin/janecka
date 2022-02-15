(function() {
	document.addEventListener("DOMContentLoaded", function(event) {
		var loginButton = document.getElementById('cr-log-account'),
			iframeUrl = document.getElementById('cr-iframe-url').value;

		loginButton.addEventListener('click', function () {
			authenticate();
		});

		function authenticate() {
			var contentWindow = document.getElementsByClassName('cr-token-expired')[0],
				iframe = document.getElementById('cr-iframe');

			iframe.src = iframeUrl;
			iframe.classList.remove('hidden');
			contentWindow.classList.add('hidden');
		}
	});
})();
