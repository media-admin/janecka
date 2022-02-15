/**
 * Abstract form class.
 *
 * @constructor
 *
 * @param {array} strings Array of strings to be displayed within the form.
 * @param {SurveyModel} model Survey model.
 * @param {function} submitCallback Submit button callback.
 * @param {function} closeCallback Close button callback.
 */
CleverReach.Survey.SurveyForm = function(
    strings, model, submitCallback, closeCallback) {
  if (this.constructor === CleverReach.Survey.SurveyForm) {
    throw new Error('Cannot instantiate abstract class');
  }

  this.strings = strings;
  this.model = model;
  this.submitCallback = submitCallback;
  this.closeCallback = closeCallback;
  this.submitButton = document.createElement('button');
};

/**
 * Builds CleverReach form that should be displayed to the user.
 */
CleverReach.Survey.SurveyForm.prototype.buildForm = function() {
  let form = document.createElement('div'),
      formHeader = this.getFormHeader(),
      formBody = this.getFormBody(),
      formFooter = this.getFormFooter();

  form.classList.add('cr-form');

  form.appendChild(formHeader);
  formBody.classList.add('cr-form-body');
  form.appendChild(formBody);
  form.appendChild(formFooter);

  return form;
};

/**
 * Returns form header that is the same on all CleverReach survey forms.
 *
 * @return {HTMLDivElement}
 */
CleverReach.Survey.SurveyForm.prototype.getFormHeader = function() {
  let formHeader = document.createElement('div'),
      closeBtn = document.createElement('button'),
      title = document.createElement('p'),
      icon = document.createElement('img');

  formHeader.classList.add('cr-form-header');

  title.innerText = this.strings['title'];
  title.classList.add('cr-form-title');
  formHeader.appendChild(title);

  icon.src = CleverReach.Survey.IMAGES_BASE_URL + 'icon_header.png';
  formHeader.appendChild(icon);

  closeBtn.classList.add('close-btn');
  closeBtn.innerHTML = 'x';
  closeBtn.addEventListener('click', this.closeCallback);

  formHeader.appendChild(closeBtn);

  return formHeader;
};

/**
 * Returns form footer that is the same on all CleverReach survey forms.
 *
 * @return {HTMLDivElement}
 */
CleverReach.Survey.SurveyForm.prototype.getFormFooter = function() {
  let formFooter = document.createElement('div'),
      poweredBy = document.createElement('div'),
      label = document.createElement('p'),
      icon = document.createElement('img');

  formFooter.classList.add('cr-form-footer');

  poweredBy.classList.add('powered-by');

  label.innerText = this.strings['poweredBy'];
  label.classList.add('powered-by');
  poweredBy.appendChild(label);

  icon.src = CleverReach.Survey.IMAGES_BASE_URL + 'logo_cleverreach.svg';
  icon.classList.add('powered-by');
  poweredBy.appendChild(icon);

  formFooter.appendChild(poweredBy);

  return formFooter;
};

/**
 * Returns form body.
 *
 * @return {HTMLDivElement}
 */
CleverReach.Survey.SurveyForm.prototype.getFormBody = function() {
  let formBody = document.createElement('div'),
      surveyText = document.createElement('p'),
      formContent = this.getFormContent(),
      formExpanded = document.createElement('div');

  surveyText.innerText = this.strings['question'];
  surveyText.classList.add('cr-survey-text');
  formBody.appendChild(surveyText);

  formContent.classList.add('cr-content');
  formBody.appendChild(formContent);

  formExpanded.hidden = true;
  formExpanded.classList.add('cr-expanded');
  formBody.appendChild(formExpanded);

  this.submitButton.innerText = this.strings['btnSend'];
  this.submitButton.classList.add('cr-button');
  this.submitButton.classList.add('cr-submit');
  this.submitButton.addEventListener('click', this.submitCallback);
  this.deactivateSubmitButton();

  formBody.appendChild(this.submitButton);

  return formBody;
};

/**
 * Returns form content.
 *
 * @return {HTMLDivElement}
 */
CleverReach.Survey.SurveyForm.prototype.getFormContent = function() {
  throw new Error('Cannot call abstract method');
};

/**
 * Expands form to add feedback comment section.
 */
CleverReach.Survey.SurveyForm.prototype.expandForm = function() {
  let form = document.getElementsByClassName('cr-form')[0],
      formHeader = document.getElementsByClassName('cr-form-header')[0],
      formTitle = document.getElementsByClassName('cr-form-title')[0],
      formBody = document.getElementsByClassName('cr-form-body')[0],
      formExpanded = document.getElementsByClassName('cr-expanded')[0],
      expandedText = document.createElement('p');

  form.classList.add('cr-form-extended');
  formHeader.classList.add('cr-form-header-extended');
  formBody.classList.add('cr-form-body-extended');

  formExpanded.hidden = false;

  formTitle.classList.add('cr-form-title-extended');

  expandedText.classList.add('cr-expanded-text');
  expandedText.innerText = this.strings['comment'];
  formExpanded.appendChild(expandedText);

  formExpanded.appendChild(this.getCommentField());
  this.activateSubmitButton();
};

/**
 * Returns comment field element.
 *
 * @return {HTMLTextAreaElement}
 */
CleverReach.Survey.SurveyForm.prototype.getCommentField = function() {
  let expandedInput = document.createElement('textarea');

  expandedInput.name = 'cr-comment';
  expandedInput.classList.add('cr-comment');

  return expandedInput;
};

/**
 * Activates submit button.
 */
CleverReach.Survey.SurveyForm.prototype.activateSubmitButton = function() {
  this.submitButton.disabled = false;
  this.submitButton.classList.add('cr-submit-active');
};

/**
 * Deactivates submit button.
 */
CleverReach.Survey.SurveyForm.prototype.deactivateSubmitButton = function() {
  this.submitButton.disabled = true;
  this.submitButton.classList.remove('cr-submit-active');
};

/**
 * Returns survey model data.
 */
CleverReach.Survey.SurveyForm.prototype.getModelData = function() {
  let comment = document.getElementsByClassName('cr-comment')[0];

  this.model.setComment(comment.value);

  return this.model;
};

/**
 * Checks whether the form has already been expanded.
 *
 * @return {boolean}
 */
CleverReach.Survey.SurveyForm.prototype.isFormExpanded = function() {
  let expandedInputs = document.getElementsByClassName('cr-comment');

  return expandedInputs.length > 0;
};
