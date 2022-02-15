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
