(function () {
	document.addEventListener(
		"DOMContentLoaded",
		function () {
			var integrationsSidebar = document.getElementById('cr-integrations-sidebar'),
				integrationsBlock = document.getElementById('cr-integrations-block'),
				sidebarButton = integrationsSidebar.querySelector('#cr-sidebar-button');

			if (integrationsSidebar) {
				integrationsSidebar.addEventListener('click', function () {
					if (integrationsBlock.classList.contains('closed')) {
						integrationsBlock.classList.remove('closed');
						integrationsBlock.parentElement.classList.remove('closed');
						sidebarButton.setAttribute('aria-expanded', "true");
					} else {
						integrationsBlock.classList.add('closed');
						integrationsBlock.parentElement.classList.add('closed');
						sidebarButton.setAttribute('aria-expanded', "false");
					}
				});

				showIntegrations();
			}

			/**
			 * Shows all integrations supported by CleverReach WordPress plugin.
			 */
			function showIntegrations() {
				CleverReach.Ajax.get(cleverreachIntegrations.integrations_endpoint_url, null, function (response) {
						if (response) {
							renderIntegrations(response);
						}
					},
					'json',
					true
				);
			}

			/**
			 * Render integrations on dashboard page.
			 *
			 * @param integrations Array of all integrations.
			 */
			function renderIntegrations(integrations) {
				for (let i = 0; i < integrations.length; i++) {
					let integration = integrations[i];

					let integrationList = document.querySelector('#cr-integration-table');
					let row = document.createElement('div');
					row.classList.add('cr-integration-row');
					addIntegration(row, integration);

					integrationList.appendChild(row);
				}
			}

			/**
			 * Adds concrete integration to a row in integrations section.
			 *
			 * @param row
			 * @param integration
			 */
			function addIntegration(row, integration) {
				let integrationNameColumn = document.createElement('div'),
					integrationStatusColumn = document.createElement('div'),
					integrationNameContainer = document.createElement('div'),
					integrationName = document.createElement('div'),
					integrationLink = document.createElement('a'),
					integrationDescription = document.createElement('div'),
					integrationManualLink = document.createElement('a'),
					integrationStatus = document.createElement('div'),
					integrationInstalled = document.createElement('span'),
					installed = document.getElementById('cr-installed').value,
					notInstalled = document.getElementById('cr-not-installed').value;

				integrationNameColumn.classList.add('cr-integration-column');
				integrationStatusColumn.classList.add('cr-integration-column');
				integrationNameContainer.classList.add('cr-integration-name-container');
				integrationName.classList.add('cr-external-integration-name');
				integrationName.title = integration.name;
				integrationLink.href = integration.link;
				integrationLink.target = '_blank';
				integrationLink.innerText = integration.name;
				integrationDescription.classList.add('cr-integration-description');
				integrationDescription.innerText = integration.description + ' ';
				integrationManualLink.href = integration.manual;
				integrationManualLink.target = '_blank';
				integrationManualLink.innerText = cleverreachIntegrations.integration_manual_text;
				integrationDescription.appendChild(integrationManualLink);

				integrationName.appendChild(integrationLink);
				integrationNameContainer.appendChild(integrationName);
				integrationNameContainer.appendChild(integrationDescription);
				integrationNameColumn.appendChild(integrationNameContainer);

				integrationStatus.classList.add('cr-integration-status');
				if (integration.installed) {
					integrationInstalled.classList.add('cr-integration-installed');
					integrationInstalled.innerText = installed;
				} else {
					integrationInstalled.classList.add('cr-integration-not-installed');
					integrationInstalled.innerText = notInstalled;
				}

				integrationStatus.appendChild(integrationInstalled);
				integrationStatusColumn.appendChild(integrationStatus);

				row.appendChild(integrationNameColumn);
				row.appendChild(integrationStatusColumn);
			}
		});
})();
