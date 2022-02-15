(function (blocks, i18n, editor, element, components) {

	var el = element.createElement,
		InspectorControls = editor.InspectorControls, // sidebar controls
		ServerSideRender = components.ServerSideRender, // sidebar controls
		PanelBody = components.PanelBody; // sidebar panel

	/**
	 * Creates dropdown options
	 *
	 * @param {array} items array of options
	 * @param {string} selectedFormId
	 */
	function createFormList(items, selectedFormId) {
		items.push(el('option', {
			value: 0,
			selected: (!showFormContent(selectedFormId)),
			disabled: true
		}, cleverReachFormsBlock.translations.select_and_display_forms));
		_.each(cleverReachFormsBlock.forms, function (form) {
			items.push(el('option',
				{
					value: form.form_id,
					selected: (parseInt(form.form_id) === parseInt(selectedFormId)),
					title: form.name
				},
				form.name.substr(0, 100)
			))
		});
	}

	/**
	 * Return selected form url
	 *
	 * @param formId
	 *
	 * @returns url of selected form
	 */
	function getSelectedFormUrl(formId) {
		for (let i = 0; i < cleverReachFormsBlock.forms.length; i++) {
			if (cleverReachFormsBlock.forms[i].form_id === formId) {
				return cleverReachFormsBlock.forms[i].url;
			}
		}
	}

	/**
	 * Renders CleverReach select form page
	 *
	 * @param {array} itemsToAdd
	 * @param {array} children
	 */
	function showSelectFormsPage(itemsToAdd, children) {
		let contentItems = [];
		for (let i = 0; i < itemsToAdd.length; i++) {
			contentItems.push(itemsToAdd[i]);
		}

		let content = el('div', {className: 'cr-gutenberg-form-config-container'}, contentItems);
		children.push(content);
	}

	/**
	 * Creates element that renders form html from backend
	 *
	 * @param props
	 * @param formID
	 * @returns {*}
	 */
	function createServerSideRenderForm(props, formID) {
		return el(ServerSideRender, {
			key: 'cr-gutenberg-forms-render',
			className: 'cr-form-' + formID,
			block: "cleverreach/subscription-form",
			attributes: props.attributes
		});
	}

	/**
	 * Checks whether form content should be shown
	 *
	 * @param {string} formId
	 * @returns {boolean|*}
	 */
	function showFormContent(formId) {
		if (!formId) {
			return false;
		}

		let existingFormIds = cleverReachFormsBlock.forms.map(form => form.form_id);

		return existingFormIds.includes(formId);
	}

	/**
	 * Adds sidebar settings items
	 *
	 * @param {array} items elements that should be added to sidebar
	 * @param {array} children global return value
	 */
	function addSidebarSettings(items, children) {
		// Set up the form dropdown and link in the side bar 'block' settings
		let inspectorControls = el(InspectorControls, {},
			el(PanelBody, {title: cleverReachFormsBlock.translations.form_settings},
				el('span', null, cleverReachFormsBlock.translations.form),
				items
			)
		);
		children.push(inspectorControls);
	}

	/**
	 * Periodically checks whether the form code from CleverReach has been loaded into page.
	 *
	 * @param formID
	 */
	function checkIsFormLoaded(formID) {
		let timer = setInterval(function () {
			if (reinitializeScripts(formID)) {
				clearInterval(timer);
			}
		}, 500);
	}

	/**
	 * Re-initializes form scripts, if the form has been loaded in page.
	 *
	 * @param formID
	 * @returns {boolean}
	 */
	function reinitializeScripts(formID) {
		let form = document.querySelector('.cr-form-' + formID);

		if (typeof form === 'undefined' || form.querySelector('.cr_form') === null) {
			return false;
		}

		Array.from(form.querySelectorAll("script")).forEach(oldScript => {
			const newScript = document.createElement("script");
			Array.from(oldScript.attributes)
				.forEach(attr => newScript.setAttribute(attr.name, attr.value));
			newScript.appendChild(document.createTextNode(oldScript.innerHTML));
			oldScript.parentNode.replaceChild(newScript, oldScript);
		});

		return true;
	}

	let crIcon = wp.element.createElement('svg',
		{
			width: 30,
			height: 30,
			xmlns: 'http://www.w3.org/2000/svg',
			viewBox: '0 0 20 10',
			color: '#ec6702',
			fill: '#ec6702'
		},
		wp.element.createElement( 'path',
			{
				d: 'M12.65,0A.57.57,0,0,0,12,.68L12.28,3a1,1,0,0,1-.65,1.06L.57,8c-.41.15-.4.36,0,.46L11,11.09a1.37,1.37,0,0,0,1.28-.41l7.58-8.95c.28-.33.16-.66-.27-.72Z'
			}
		),
		wp.element.createElement( 'path',
			{
				d: 'M9.72.11a.46.46,0,0,1,.76.36l.2,1.64A1,1,0,0,1,10,3.16L.34,6.45c-.42.14-.45.06-.09-.18Z'
			}
		)
	);

	// register our block
	blocks.registerBlockType('cleverreach/subscription-form', {
		title: cleverReachFormsBlock.translations.subscription_form,
		icon: crIcon,
		category: 'cleverreach',
		attributes: {
			formID: {
				type: 'string'
			},
			renderForm: {
				type: 'boolean'
			}

		},

		supports: {
			html: false
		},

		edit: function (props) {
			let formID = props.attributes.formID;
			let formItems = [];
			let children = [];

			createFormList(formItems, formID);

			/**
			 * Set form id when CleverReach form is selected
			 *
			 * @param event
			 */
			function formSelected(event) {
				//set the attributes from the selected for item
				let selectElement = event.target;
				event.preventDefault();
				props.setAttributes({
					formID: selectElement.options[selectElement.selectedIndex].value,
				});
			}

			// text element
			let textItem = el('div', {className: 'cr-gutenberg-form-config-item cr-gutenberg-form-config-item-text'},
				el('div', {className: 'cr-gutenberg-form-config-text-message'},
					cleverReachFormsBlock.translations.insert_form
				)
			);

			// dropdown element
			let selectItem = el('div', {className: 'cr-gutenberg-form-config-item'},
				el('select', {onChange: formSelected}, formItems)
			);

			let formLink = showFormContent(formID) ?
				el('a', {href: getSelectedFormUrl(formID), target: '_blank'},
					el('span', {}, cleverReachFormsBlock.translations.edit_in_cleverreach),
					el('img', {
						className: 'cr-learn-button',
						src: 'data:image/svg+xml;utf8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2020%2020%22%3E%3Cpath%20fill%3D%22%23212B36%22%20d%3D%22M13%2012a1%201%200%200%201%201%201v1a1%201%200%200%201-1%201H6c-.575%200-1-.484-1-1V7a1%201%200%200%201%201-1h1a1%201%200%200%201%200%202v5h5a1%201%200%200%201%201-1zm-2-7h4v4a1%201%200%201%201-2%200v-.586l-2.293%202.293a.999.999%200%201%201-1.414-1.414L11.586%207H11a1%201%200%200%201%200-2z%22%2F%3E%3C%2Fsvg%3E%0A'
					})
				)
				: null;

			addSidebarSettings([selectItem, formLink], children);

			if (showFormContent(formID)) {
				if (!props.attributes.renderForm) {
					setTimeout(function () {
						props.setAttributes({
							renderForm:true,
							formID: formID,
						});
					}, 0);

					showSelectFormsPage([textItem, selectItem], children);

					return [children];
				}

				children.push(createServerSideRenderForm(props, formID));

				checkIsFormLoaded(formID);
			} else {
				showSelectFormsPage([textItem, selectItem], children);
			}

			return [children];
		},

		save: function () {
			return null;
		}
	});
})(
	window.wp.blocks,
	window.wp.i18n,
	window.wp.editor,
	window.wp.element,
	window.wp.components
);
