/**
 * NPS free form.
 *
 * @constructor
 */
CleverReach.Survey.NPSFreeForm = function(strings) {
  CleverReach.Survey.SurveyForm.apply(this, arguments);
};

CleverReach.Survey.NPSFreeForm.prototype = Object.create(
    CleverReach.Survey.SurveyForm.prototype, {
      'constructor': CleverReach.Survey.NPSFreeForm,
    },
);

/**
 * Returns form content.
 *
 * @return {HTMLDivElement}
 */
CleverReach.Survey.NPSFreeForm.prototype.getFormContent = function() {
  let content = document.createElement('div'),
      expandedInput = this.getCommentField();

  expandedInput.addEventListener('input', function() {
    let textLn = expandedInput.value.length;

    if (textLn > 0) {
      this.activateSubmitButton();
      this.submitButton.classList.add('cr-submit-active-nps-free');
    } else {
      this.deactivateSubmitButton();
      this.submitButton.classList.remove('cr-submit-active-nps-free');
    }
  }.bind(this));

  content.appendChild(expandedInput);

  return content;
};
