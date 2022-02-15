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
