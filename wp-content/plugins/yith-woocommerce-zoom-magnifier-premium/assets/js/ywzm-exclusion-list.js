/* global ywzm_exclusion_list*/
jQuery(function ($) {
	"use strict";

	var newExclusion = $(document).find('.yith-exclusion-list__popup_wrapper'),
		confirm = $(document).find('#yith-exclusion-list__delete_row'),
		popupForm = newExclusion.find('form'),
		openPopup = function () {

			newExclusion = $(document).find('.yith-exclusion-list__popup_wrapper');

			// init dialog
			newExclusion.dialog({
				closeText: '',
				title: ywzm_exclusion_list.popup_add_title,
				width: 500,
				modal: true,
				dialogClass: 'yith-plugin-ui ywzm-exclusion-list-add ywzm-exclusion-list-popup',
				buttons: [{
					'text': ywzm_exclusion_list.save,
					'click': function (e) {
						e.preventDefault();
						window.onbeforeunload = null;
						popupForm.submit();
					},
					'class': 'yith-save-form'
				}]
			});

		},

		updatePopupField = function () {
			$(document).on('change', '#ywzm-exclusion-type', function () {
				var $t = $(this),
					fieldSelected = $t.val();
				$('.ywzm-exclusion-field').hide();
				$('[dep-value="' + fieldSelected + '"').show();
			});

			$('#ywzm-exclusion-type').change();
		},
		cancelExclusion = function (fieldType, fieldId) {
			$.ajax({
				url: ywzm_exclusion_list.ajaxurl,
				data: {type: fieldType, id: fieldId, action:'ywzm_delete_from_exclusion_list',nonce:ywzm_exclusion_list.delete_nonce},
				type: 'POST',
				success: function( response ){
					confirm.dialog('close');
					window.location.reload();
				}
			});
		};

	updatePopupField();


	$(document).on('click', '.ywzm-add-exclusions a', function () {
		openPopup();
	});

	$(document).on('click', '.action__trash', function (ev) {
		ev.preventDefault();

		var $t = $(this),
			fieldType = $t.data('field-type'),
			fieldId = $t.data('field-id');

		// init dialog
		confirm.dialog({
			closeText: '',
			width: 350,
			modal: true,
			dialogClass: 'yith-plugin-ui ywzm-exclusion-list-popup-confirmation ywzm-exclusion-list-popup',
			buttons: [{
				'text': ywzm_exclusion_list.confirmChoice,
				'click': function () {
					cancelExclusion(fieldType, fieldId);
				},
				'class': 'yith-confirm'
			},
				{
					'text': ywzm_exclusion_list.cancel,
					'click': function () {
						confirm.dialog("close");
					},
					'class': 'yith-close'
				}]

		});
	});

});

