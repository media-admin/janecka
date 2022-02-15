/**
 * Survey controller.
 *
 * @constructor
 */
CleverReach.Survey.SurveyController = function() {
  this.prepareForm = this.prepareForm.bind(this);
  this.mouseLeave = this.mouseLeave.bind(this);
  this.openForm = this.openForm.bind(this);
  this.submitAnswer = this.submitAnswer.bind(this);
  this.ignoreForm = this.ignoreForm.bind(this);
  this.closeForm = this.closeForm.bind(this);
  this.displayThankYouPage = this.displayThankYouPage.bind(this);

  this.ajaxService = new CleverReach.Survey.AjaxService();
};

/**
 * Initializes controller and checks whether CleverReach form should be displayed.
 *
 * @param {string} triggerType Survey trigger type.
 * @param {string} surveyUrl URL for fetching/answering survey.
 * @param {string} ignoreSurveyUrl URL for ignoring survey.
 * @param {string} setFormOpenedUrl URL for setting form opened.
 */
CleverReach.Survey.SurveyController.prototype.init = function(
    triggerType,
    surveyUrl,
    ignoreSurveyUrl,
    setFormOpenedUrl,
) {
  this.triggerType = triggerType;
  this.surveyUrl = surveyUrl;
  this.ignoreSurveyUrl = ignoreSurveyUrl;
  this.setFormOpenedUrl = setFormOpenedUrl;

  this.hasUrlTrigger = false;

  if (typeof this.surveyUrl === 'undefined'
      || typeof this.ignoreSurveyUrl === 'undefined'
      || typeof this.triggerType === 'undefined'
  ) {
    throw new Error('Required parameter missing');
  }

  let supportedTriggers = [
        'plugin_installed',
        'initial_sync_finished',
        'first_form_used',
        'periodic',
      ],
      queryConjuction = this.surveyUrl.indexOf('?') !== -1 ? '&' : '?';

  for (let trigger of supportedTriggers) {
    if (window.location.href.includes(trigger)) {
      this.triggerType = trigger;
      this.hasUrlTrigger = true;
    }
  }

  this.ajaxService.get(
      this.surveyUrl + queryConjuction + 'type=' + this.triggerType,
      this.prepareForm,
      function() {
        // If there are no polls available,
        // API will return 303 and feedback button won't be displayed.
      },
  );
};

/**
 * Prepares form for rendering.
 *
 * @param {object} response CleverReach API response.
 */
CleverReach.Survey.SurveyController.prototype.prepareForm = function(response) {
  if (typeof response['meta'] !== 'undefined'
      && typeof response['token'] !== 'undefined'
  ) {
    let thankYouDuration = 0;
    if (typeof response['meta']['info_delay'] !== 'undefined') {
      thankYouDuration = response['meta']['info_delay'];
    }

    this.model = new CleverReach.Survey.SurveyModel(
        response['meta']['id'],
        response['lang'],
        response['meta']['user'],
        response['customer_id'],
        response['token'],
        this.getThankYouPageContent(response['meta']['content'][response['lang']]),
        thankYouDuration
    );

    this.formType = response['meta']['type'];

    this.strings = response['meta']['content'][response['lang']]['text'];
    this.strings['title'] = response['meta']['content'][response['lang']]['title'];
    this.strings['question'] = response['meta']['content'][response['lang']]['question'];
    this.strings['poweredBy'] = 'Powered by';

    if (!this.hasUrlTrigger) {
      this.feedbackButton = document.getElementById('cr-open-form');

      let buttonImage = document.createElement('img');
      buttonImage.src = CleverReach.Survey.IMAGES_BASE_URL + 'feedback.png';

      this.feedbackButton.appendChild(buttonImage);
      this.feedbackButton.addEventListener('click', this.openForm);

      if (CleverReach.Survey.HANDLE_EXIT_WINDOW_EVENT) {
        document.addEventListener('mouseout', this.mouseLeave);
      }
    } else {
      this.openForm();
    }
  }
};

/**
 * Handles event when mouse leaves top of the screen.
 *
 * @param {object} event Mouse out event.
 */
CleverReach.Survey.SurveyController.prototype.mouseLeave = function(event) {
  if (event.clientY > 0) {
    return;
  }

  this.openForm();
};

/**
 * Opens CleverReach form.
 */
CleverReach.Survey.SurveyController.prototype.openForm = function() {
  if (CleverReach.Survey.HANDLE_EXIT_WINDOW_EVENT) {
    document.removeEventListener('mouseout', this.mouseLeave);
  }

  let formFactory = new CleverReach.Survey.SurveyFormFactory(this.strings);

  this.surveyForm = new formFactory.create(
      this.formType,
      this.model,
      this.submitAnswer,
      this.ignoreForm,
  );
  this.form = this.surveyForm.buildForm();

  this.blanket = document.createElement('div');
  this.blanket.classList.add('cr-blanket');
  this.blanket.addEventListener('click', this.ignoreForm);
  document.body.appendChild(this.blanket);

  document.body.appendChild(this.form);

  if (typeof this.setFormOpenedUrl !== 'undefined'
      && this.setFormOpenedUrl !== null
  ) {
    this.ajaxService.post(
        this.setFormOpenedUrl,
        {'type': this.triggerType},
        function() {
        },
        function() {
        },
    );
  }
};

/**
 * Submits an answer to the CleverReach API.
 */
CleverReach.Survey.SurveyController.prototype.submitAnswer = function() {
  this.model = this.surveyForm.getModelData();

  let params = {
        'pollId': this.model.pollId,
        'result': this.model.result,
        'comment': this.model.comment,
      },
      queryConjuction = this.surveyUrl.indexOf('?') !== -1 ? '&' : '?';

  this.ajaxService.post(
      this.surveyUrl + queryConjuction + 'token=' + this.model.token,
      params,
      this.displayThankYouPage,
      this.closeForm,
  );
};

/**
 * Displays thank you page to the user.
 */
CleverReach.Survey.SurveyController.prototype.displayThankYouPage = function() {
  this.closeForm();

  if (this.model.thankYouPageContent !== '') {
    let thankYouPage = new CleverReach.Survey.ThankYouPage();
    thankYouPage.openPage(this.model.thankYouPageContent, this.model.thankYouDuration);
  }
};

/**
 * Ignores survey form on CleverReach API.
 */
CleverReach.Survey.SurveyController.prototype.ignoreForm = function() {
  let queryConjuction = this.ignoreSurveyUrl.indexOf('?') !== -1 ? '&' : '?',
      url = this.ignoreSurveyUrl + queryConjuction
          + 'pollId=' + this.model.pollId
          + '&customerId=' + this.model.customerId
          + '&token=' + this.model.token;

  this.ajaxService.post(url, {}, this.closeForm, function() {});
};

/**
 * Closes opened survey form.
 */
CleverReach.Survey.SurveyController.prototype.closeForm = function() {
  if (typeof this.feedbackButton !== 'undefined') {
    this.feedbackButton.classList.add('cr-hidden');
  }

  document.body.removeChild(this.blanket);
  document.body.removeChild(this.form);
};

/**
 * Returns HTML code for thank you page depending on how the user is voted.
 *
 * @param {array} responseContent CleverReach API response content array.
 *
 * @return {string} HTML output for thank you page.
 */
CleverReach.Survey.SurveyController.prototype.getThankYouPageContent = function(responseContent) {
  if (responseContent['promotoren'] !== '') {
    return responseContent['promotoren'];
  }

  if (responseContent['indifferente'] !== '') {
    return responseContent['indifferente'];
  }

  return responseContent['detraktoren'];
};
