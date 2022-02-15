(function () {
	document.addEventListener(
		"DOMContentLoaded",
		function () {
			var formsSidebar = document.getElementById('cr-forms-sidebar'),
				formBlock = document.getElementById('cr-forms-block'),
				sidebarButton = formsSidebar.querySelector('#cr-sidebar-button'),
				formInfo = document.getElementById('cr-form-info');

			if (formsSidebar) {
				formsSidebar.addEventListener('click', function () {
					if (formBlock.classList.contains('closed')) {
						formBlock.classList.remove('closed');
						formBlock.parentElement.classList.remove('closed');
						sidebarButton.setAttribute('aria-expanded', "true");
					} else {
						formBlock.classList.add('closed');
						formBlock.parentElement.classList.add('closed');
						sidebarButton.setAttribute('aria-expanded', "false");
					}
				});

				showForms();

				formInfo.addEventListener('click', function (event) {
					event.stopPropagation();
				});
			}

			/**
			 * Shows all cleverreach forms which are created for wordpress list
			 */
			function showForms() {
				CleverReach.Ajax.get(cleverreachForms.forms_endpoint_url, null, function (response) {
						if (response) {
							renderForms(response);
						}
					},
					'json',
					true
				);
			}

			/**
			 * Render forms on dashboard page
			 *
			 * @param forms array of all forms
			 */
			function renderForms(forms) {
				for (let i = 0; i < forms.length; i++) {
					createNewRow(forms[i]);
				}
			}

			/**
			 * Creates new raw element with form information column and shortcode column
			 *
			 * @param form
			 */
			function createNewRow(form) {
				let formList = document.querySelector('#cr-forms-table');
				let row = document.createElement('div');
				row.classList.add('cr-form-row');
				addForm(row, form);
				addShortCode(row, form.formId);

				formList.appendChild(row);
			}

			/**
			 * Add new column element to raw with form name, integration name and link on CleverReach
			 *
			 * @param row table row element
			 * @param form
			 */
			function addForm(row, form) {
				let formNameContainer = document.createElement('div');
				formNameContainer.classList.add('cr-form-name-container');
				let formName = document.createElement('div');
				formName.classList.add('cr-form-name');
				formName.title = form.name;
				let formNameLink = document.createElement('a');
				formNameLink.href = form.url;
				formNameLink.target = '_blank';
				formNameLink.appendChild((document.createTextNode(form.name)));
				formName.appendChild(formNameLink);
				formNameContainer.appendChild(formName);

				let integrationNameElement = document.createElement('div');
				integrationNameElement.classList.add('cr-integration-name');
				integrationNameElement.appendChild(document.createTextNode(form.groupName));
				formNameContainer.appendChild(integrationNameElement);

				addLink(formNameContainer, form.url);
				addNewCell(row, formNameContainer);
			}

			/**
			 * Add new column element to raw with shortcode and copy button
			 *
			 * @param row table row element
			 * @param formId form id
			 */
			function addShortCode(row, formId) {
				let inputTextField = document.createElement('input');
				inputTextField.classList.add('cr-shortcode-input');
				inputTextField.setAttribute('type', 'text');
				inputTextField.value = '[cleverreach form=' + formId + ']';
				inputTextField.style.width = '100%';
				let copyButton = document.createElement('button');
				copyButton.id = 'cr-copy-button';
				copyButton.classList.add("button");
				let icon = document.createElement('i');
				icon.classList.add("far", "fa-clone");
				copyButton.appendChild(icon);
				copyButton.addEventListener('click', function () {
					inputTextField.select();
					document.execCommand('copy');
				});

				let shortcodeContainer = document.createElement('div');
				shortcodeContainer.classList.add('cr-shortcode-container');
				shortcodeContainer.appendChild(inputTextField);
				shortcodeContainer.appendChild(copyButton);

				addNewCell(row, shortcodeContainer);
			}

			/**
			 * Add CleverReach form link column with form information
			 *
			 * @param container table row element
			 * @param formUrl form id
			 */
			function addLink(container, formUrl) {
				let wrapper = document.createElement('div');
				wrapper.classList.add('cr-form-link');
				let link = document.createElement('a');
				let linkText = document.createTextNode(cleverreachForms.edit_in_cleverreach_text);
				linkText.innerHTML += '&reg;';
				link.appendChild(linkText);
				link.href = formUrl;
				link.target = '_blank';
				wrapper.appendChild(link);
				container.appendChild(wrapper);
			}

			/**
			 * Adds new cell to row
			 *
			 * @param row where element should be added
			 * @param element which should be added
			 */
			function addNewCell(row, element) {
				let cell = document.createElement('div');
				cell.classList.add('cr-form-column');
				cell.appendChild(element);
				row.appendChild(cell);
			}
		}
	);
})();
