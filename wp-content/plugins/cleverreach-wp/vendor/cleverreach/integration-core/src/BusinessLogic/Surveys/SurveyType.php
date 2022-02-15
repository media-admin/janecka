<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Surveys;

/**
 * Class SurveyType
 */
abstract class SurveyType
{
    const PLUGIN_INSTALLED = 'plugin_installed';
    const INITIAL_SYNC_FINISHED = 'initial_sync_finished';
    const FIRST_FORM_USED = 'first_form_used';
    const PERIODIC = 'periodic';
}
