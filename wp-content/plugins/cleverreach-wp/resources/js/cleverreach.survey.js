'use strict';

var CleverReach = window.CleverReach || {};
CleverReach.Survey = window.CleverReach.Survey || {};

// Override this in integration.
CleverReach.Survey.IMAGES_BASE_URL = '../wp-content/plugins/cleverreach-wp/resources/images/';
CleverReach.Survey.HANDLE_EXIT_WINDOW_EVENT = false;

CleverReach.Survey.THANK_YOU_PAGE_DURATION = 5;
/**
 * Survey model.
 *
 * @constructor
 *
 * @param {string} pollId ID of the poll on CleverReach.
 * @param {string} language Language code.
 * @param {string} user ID of the user.
 * @param {string} customerId ID of the customer.
 * @param {string} token One time token to be used in answer.
 * @param {string} thankYouPageContent HTML to be rendered on Thank You page.
 * @param {int} thankYouDuration Amount of time thank you page should be displayed for.
 */
CleverReach.Survey.SurveyModel = function(
    pollId,
    language,
    user,
    customerId,
    token,
    thankYouPageContent,
    thankYouDuration
) {
  this.pollId = pollId;
  this.language = language;
  this.user = user;
  this.customerId = customerId;
  this.token = token;
  this.thankYouPageContent = thankYouPageContent;
  this.thankYouDuration = thankYouDuration;
};

/**
 * Sets result on model.
 *
 * @param {string} result Result value.
 */
CleverReach.Survey.SurveyModel.prototype.setResult = function(result) {
  this.result = result;
};

/**
 * Sets user comment on model.
 *
 * @param {string} comment User comment.
 */
CleverReach.Survey.SurveyModel.prototype.setComment = function(comment) {
  this.comment = comment;
};
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
/**
 * Thank you page.
 *
 * @constructor
 */
CleverReach.Survey.ThankYouPage = function() {
  this.closePage = this.closePage.bind(this);
};

/**
 * Opens thank you page.
 */
CleverReach.Survey.ThankYouPage.prototype.openPage = function(thankYouPageContent, thankYouDuration) {
  this.blanket = document.createElement('div');
  this.thankYouPage = document.createElement('div');

  let template = document.createElement('template');

  this.blanket.classList.add('cr-blanket');
  document.body.appendChild(this.blanket);

  if (thankYouDuration === 0) {
    thankYouDuration = CleverReach.Survey.THANK_YOU_PAGE_DURATION * 1000;
  }

  this.timeout = setTimeout(this.closePage, thankYouDuration);
  this.blanket.addEventListener('click', function() {
    clearTimeout(this.timeout);
    this.closePage();
  }.bind(this));

  template.innerHTML = thankYouPageContent.replace(/\\n/g, '').replace(/\\/g, '');
  this.thankYouPage = template.content.firstChild;
  this.thankYouPage.classList.add('cr-thank-you');

  document.body.appendChild(this.thankYouPage);
};

/**
 * Closes thank you page.
 */
CleverReach.Survey.ThankYouPage.prototype.closePage = function() {
  document.body.removeChild(this.thankYouPage);
  document.body.removeChild(this.blanket);
};
/**
 * NPS form.
 *
 * @constructor
 */
CleverReach.Survey.NPSForm = function(strings) {
  CleverReach.Survey.SurveyForm.apply(this, arguments);

  this.points = [];
  this.pointClicked = this.pointClicked.bind(this);
};

CleverReach.Survey.NPSForm.prototype = Object.create(
    CleverReach.Survey.SurveyForm.prototype,
    {
      'constructor': CleverReach.Survey.NPSForm,
    },
);

/**
 * Returns form content.
 *
 * @return {HTMLDivElement}
 */
CleverReach.Survey.NPSForm.prototype.getFormContent = function() {
  let content = document.createElement('div'),
      notSatisfied = document.createElement('div'),
      satisfied = document.createElement('div'),
      point;

  for (let i = 0; i <= 10; i++) {
    point = document.createElement('div');

    point.classList.add('cr-point');
    point.innerText = i.toString();
    point.dataset.point = i.toString();

    point.addEventListener('click', this.pointClicked);

    this.points.push(point);

    content.appendChild(point);
  }

  notSatisfied.innerText = this.strings['descLeft'];
  notSatisfied.classList.add('cr-not-satisfied');
  content.appendChild(notSatisfied);

  satisfied.innerText = this.strings['descRight'];
  satisfied.classList.add('cr-satisfied');
  content.appendChild(satisfied);

  return content;
};

/**
 * Handles point clicked event.
 *
 * @param {event} event
 */
CleverReach.Survey.NPSForm.prototype.pointClicked = function(event) {
  let content = document.getElementsByClassName('cr-content')[0];

  if (typeof this.selectedPoint !== 'undefined') {
    this.selectedPoint.classList.remove('cr-point-selected');
  }

  event.target.classList.add('cr-point-selected');

  this.selectedPoint = event.target;
  this.model.setResult(event.target.dataset.point);

  if (!this.isFormExpanded()) {
    let formExpanded = document.getElementsByClassName('cr-expanded')[0];

    for (let pointElement of this.points) {
      pointElement.classList.add('cr-point-extended');
    }

    content.classList.add('cr-content-extended');
    formExpanded.classList.add('cr-expanded-extended');

    this.expandForm();
  }
};
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
/**
 * NPS traffic light form.
 *
 * @constructor
 */
CleverReach.Survey.NPSTrafficLightForm = function(strings) {
  CleverReach.Survey.SurveyForm.apply(this, arguments);

  this.selectReaction = this.selectReaction.bind(this);
};

CleverReach.Survey.NPSTrafficLightForm.prototype = Object.create(
    CleverReach.Survey.SurveyForm.prototype, {
      'constructor': CleverReach.Survey.NPSTrafficLightForm,
    }
);

/**
 * Returns form content.
 *
 * @return {HTMLDivElement}
 */
CleverReach.Survey.NPSTrafficLightForm.prototype.getFormContent = function() {
  let content = document.createElement('div'),
      sadFace = document.createElement('img'),
      neutralFace = document.createElement('img'),
      happyFace = document.createElement('img'),
      breakLine = document.createElement('hr'),
      notLikely = document.createElement('div'),
      likely = document.createElement('div');

  content.classList.add('cr-content-nps-traffic-light');

  sadFace.src = CleverReach.Survey.IMAGES_BASE_URL + 'nps0.png';
  sadFace.classList.add('cr-reaction');
  sadFace.dataset.point = '0';
  sadFace.addEventListener('click', this.selectReaction);
  content.appendChild(sadFace);

  neutralFace.src = CleverReach.Survey.IMAGES_BASE_URL + 'nps7-8.png';
  neutralFace.classList.add('cr-reaction');
  neutralFace.dataset.point = '7';
  neutralFace.addEventListener('click', this.selectReaction);
  content.appendChild(neutralFace);

  happyFace.src = CleverReach.Survey.IMAGES_BASE_URL + 'nps10.png';
  happyFace.classList.add('cr-reaction');
  happyFace.dataset.point = '10';
  happyFace.addEventListener('click', this.selectReaction);
  content.appendChild(happyFace);

  content.appendChild(breakLine);

  notLikely.innerText = this.strings['descLeft'];
  notLikely.classList.add('cr-not-likely');
  content.appendChild(notLikely);

  likely.innerText = this.strings['descRight'];
  likely.classList.add('cr-likely');
  content.appendChild(likely);

  return content;
};

/**
 * Handles reaction selected event.
 *
 * @param {event} event
 */
CleverReach.Survey.NPSTrafficLightForm.prototype.selectReaction = function(event) {
  let reactions = document.getElementsByClassName('cr-reaction');

  for (let reaction of reactions) {
    reaction.classList.remove('cr-reaction-selected');
    reaction.classList.remove('cr-reaction-deselected');

    if (event.target === reaction) {
      reaction.classList.add('cr-reaction-selected');
      this.model.setResult(reaction.dataset.point);
    } else {
      reaction.classList.add('cr-reaction-deselected');
    }

    if (!this.isFormExpanded()) {
      let content = document.getElementsByClassName('cr-content')[0];
      content.classList.add('cr-content-nps-traffic-light-extended');

      this.expandForm();
    }
  }
};
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
/**
 * Abstract form factory.
 *
 * @constructor
 */
CleverReach.Survey.SurveyFormFactory = function(strings) {
  /**
   * Creates an instance of CleverReach SurveyForm entity depending on the type.
   *
   * @param {string} type Form type.
   * @param {SurveyModel} model Survey model.
   * @param {function} submitCallback Submit button callback.
   * @param {function} closeCallback Close button callback.
   *
   * @return {Window.CleverReach.SurveyForm}
   */
  this.create = function(type, model, submitCallback, closeCallback) {
    let form;
    switch (type) {
      case 'nps':
        form = new CleverReach.Survey.NPSForm(
            strings,
            model,
            submitCallback,
            closeCallback
        );
        break;
      case 'nps-free':
        form = new CleverReach.Survey.NPSFreeForm(
            strings,
            model,
            submitCallback,
            closeCallback
        );
        break;
      case 'nps-traffic-light':
        form = new CleverReach.Survey.NPSTrafficLightForm(
            strings,
            model,
            submitCallback,
            closeCallback
        );
        break;
      case 'yesno':
        form = new CleverReach.Survey.YesNoForm(
            strings,
            model,
            submitCallback,
            closeCallback
        );
        break;
      default:
        throw new Error('Invalid form type');
    }

    return form;
  };
};
/**
 * Service for making asynchronous API requests.
 *
 * @constructor
 */
CleverReach.Survey.AjaxService = function() {
  /**
   * Performs GET ajax request.
   *
   * @param {string} url
   * @param {function} onSuccess
   * @param {[function]} onError
   */
  this.get = function(url, onSuccess, onError) {
    this.call('GET', url, {}, onSuccess, onError);
  };

  /**
   * Performs POST ajax request.
   *
   * @note You can not post data that has fields with special values such as infinity, undefined etc.
   *
   * @param {string} url
   * @param {object} data
   * @param {function} onSuccess
   * @param {[function]} onError
   */
  this.post = function(url, data, onSuccess, onError) {
    this.call('POST', url, data, onSuccess, onError);
  };

  /**
   * Performs ajax call.
   *
   * @param {'GET' | 'POST'} method
   * @param {string} url
   * @param {object} data
   * @param {function} onSuccess
   * @param {[function]} onError
   */
  this.call = function(method, url, data, onSuccess, onError) {
    let request = getRequest();
    request.open(method, url, true);

    request.onreadystatechange = function() {
      if (this.readyState === 4) {
        if (this.status >= 200 && this.status < 300) {
          onSuccess(JSON.parse(this.responseText || '{}'));
        } else {
          if (typeof onError !== 'undefined') {
            onError(JSON.parse(this.responseText || '{}'));
          }
        }
      }
    };

    if (method === 'POST') {
      request.setRequestHeader('Content-Type', 'application/json');
      request.send(JSON.stringify(data));
    } else {
      request.send();
    }
  };

  /**
   * Creates instance of request.
   *
   * @return {XMLHttpRequest | ActiveXObject}
   */
  function getRequest() {
    if (typeof XMLHttpRequest !== 'undefined') {
      return new XMLHttpRequest();
    }

    let versions = [
      'MSXML2.XmlHttp.6.0',
      'MSXML2.XmlHttp.5.0',
      'MSXML2.XmlHttp.4.0',
      'MSXML2.XmlHttp.3.0',
      'MSXML2.XmlHttp.2.0',
      'Microsoft.XmlHttp',
    ];

    let xhr;
    for (let version of versions) {
      try {
        xhr = new ActiveXObject(version);
        break;
      } catch (e) {
      }
    }

    return xhr;
  }
};
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
