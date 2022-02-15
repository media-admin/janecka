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
