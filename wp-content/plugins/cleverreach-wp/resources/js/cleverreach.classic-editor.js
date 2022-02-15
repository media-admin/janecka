/* Translations are at the end of the file. */

tinymce.PluginManager.add('cleverreach', function (editor, url) {
	// Add a button that opens a window
	if (typeof CleverReach !== 'undefined') {
		editor.addButton('cleverreach', {
			image: tinymce.settings.base_url + '/wp-content/plugins/cleverreach-wp/resources/images/cr-envelope.svg',
			title: 'Insert CleverReach signup form',
			cmd: 'cr_open_dialog'
		});

		editor.addCommand('cr_open_dialog', function () {
			CleverReach.Ajax.get(
				tinymce.settings.base_url + '/index.php/?cleverreach_wp_controller=Forms&action=get_all_forms',
				null,
				open_dialog,
				'json',
				true
			);

			function open_dialog(response) {
				let forms = [],
					options = [],
					currentFormId = '',
					currentFormUrl = '';

				for (let i = 0; i < response.length; i++) {
					forms.push({
						id: response[i].formId,
						name: response[i].name,
						url: response[i].url
					});
					options.push({
						title: response[i].name,
						text: response[i].name.substr(0, 150),
						value: response[i].formId,
						onclick: function () {
							currentFormId = this.value();

							select_form();
						}
					});
				}

				if (forms.length > 0) {
					currentFormId = forms[0].id;
					currentFormUrl = forms[0].url;
				}

				editor.windowManager.open({
					title: 'Insert CleverReach signup form',
					classes: 'cr-window',
					body: [
						{
							type: 'listbox',
							name: 'cr_form',
							classes: 'cr-form-selector',
							fixedWidth: true,
							values: options
						}
					],
					buttons: [
						{
							type: 'container',
							classes: 'cr-link',
							html: '<a id="cr-edit-link" href="' + currentFormUrl + '" target="_blank">'
								+ tinymce.translate('Edit in CleverReach')
								+ '<img class="cr-learn-icon" src="data:image/svg+xml;utf8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2020%2020%22%3E%3Cpath%20fill%3D%22%23212B36%22%20d%3D%22M13%2012a1%201%200%200%201%201%201v1a1%201%200%200%201-1%201H6c-.575%200-1-.484-1-1V7a1%201%200%200%201%201-1h1a1%201%200%200%201%200%202v5h5a1%201%200%200%201%201-1zm-2-7h4v4a1%201%200%201%201-2%200v-.586l-2.293%202.293a.999.999%200%201%201-1.414-1.414L11.586%207H11a1%201%200%200%201%200-2z%22%2F%3E%3C%2Fsvg%3E%0A" alt=""></a>'
						},
						{
							text: 'Insert',
							id: 'cr-button',
							classes: 'cr-button',
							onclick: function (e) {
								editor.insertContent('[cleverreach form=' + currentFormId + ']');
								editor.windowManager.close();
							},
							primary: true
						}
					],
					height: 80,
					width: 400
				});

				function select_form() {
					let editLink = document.getElementById('cr-edit-link');

					if (typeof editLink !== 'undefined' && editLink !== null) {
						let form = forms.filter(form => {
							return form.id === currentFormId
						});

						if (form === null) {
							throw new Error('Form does not exist!');
						}

						currentFormUrl = form[0].url;
						editLink.setAttribute('href', currentFormUrl);
					}
				}
			}
		});
	}
});

tinymce.addI18n('de', {
	'Insert CleverReach signup form': 'CleverReach-Anmeldeformular einfügen',
	'Edit in CleverReach': 'In CleverReach bearbeiten',
	'Insert': 'Einfügen'
});

tinymce.addI18n('es', {
	'Insert CleverReach signup form': 'Inserta formulario de suscripción den CleverReach',
	'Edit in CleverReach': 'Editar en CleverReach',
	'Insert': 'Insertar'
});

tinymce.addI18n('fr', {
	'Insert CleverReach signup form': 'Insérer le formulaire d\'inscription CleverReach',
	'Edit in CleverReach': 'Éditer dans CleverReach',
	'Insert': 'Insérer'
});

tinymce.addI18n('it', {
	'Insert CleverReach signup form': 'Inserisci modulo di iscrizione CleverReach',
	'Edit in CleverReach': 'Modifica in CleverReach',
	'Insert': 'Inserisci'
});
