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
