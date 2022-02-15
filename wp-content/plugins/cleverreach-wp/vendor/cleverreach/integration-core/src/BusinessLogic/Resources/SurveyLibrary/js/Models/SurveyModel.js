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
