/**
 * Yes/no form.
 *
 * @constructor
 */
CleverReach.Survey.YesNoForm = function(strings) {
  CleverReach.Survey.SurveyForm.apply(this, arguments);

  this.buttonClicked = this.buttonClicked.bind(this);
};

CleverReach.Survey.YesNoForm.prototype = Object.create(
    CleverReach.Survey.SurveyForm.prototype, {
      'constructor': CleverReach.Survey.YesNoForm,
    }
);

/**
 * Returns form content.
 *
 * @return {HTMLDivElement}
 */
CleverReach.Survey.YesNoForm.prototype.getFormContent = function() {
  let content = document.createElement('div'),
      yesButton = document.createElement('button'),
      noButton = document.createElement('button');

  content.classList.add('cr-content-yes-no');

  yesButton.innerText = this.strings['yes'];
  yesButton.classList.add('cr-button');
  yesButton.classList.add('cr-yes-button');
  yesButton.addEventListener('click', this.buttonClicked);

  content.appendChild(yesButton);

  noButton.innerText = this.strings['no'];
  noButton.classList.add('cr-button');
  noButton.classList.add('cr-no-button');
  noButton.addEventListener('click', this.buttonClicked);

  content.appendChild(noButton);

  return content;
};

/**
 * Handles button clicked event.
 *
 * @param {event} event
 */
CleverReach.Survey.YesNoForm.prototype.buttonClicked = function(event) {
  let content = document.getElementsByClassName('cr-content')[0],
      yesButton = document.getElementsByClassName('cr-yes-button')[0],
      noButton = document.getElementsByClassName('cr-no-button')[0];

  this.model.setResult(
      event.target.classList.contains('cr-yes-button') ? '10' : '0'
  );

  if (this.model.result === '10') {
    noButton.classList.remove('cr-no-button-clicked');
    yesButton.classList.add('cr-yes-button-clicked');
  } else {
    yesButton.classList.remove('cr-yes-button-clicked');
    noButton.classList.add('cr-no-button-clicked');
  }

  if (!this.isFormExpanded()) {
    content.classList.add('cr-content-yes-no-extended');
    this.expandForm();
  }
};
