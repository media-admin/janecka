<?php

namespace CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required;

use CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\AuthInfo;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Logger\Logger;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException;

/**
 * Class Configuration
 *
 * @package CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required
 */
abstract class Configuration
{
    const CLASS_NAME = __CLASS__;
    const INITIAL_BATCH_SIZE = 250;
    const DEFAULT_MAX_STARTED_TASK_LIMIT = 16;
    const DEFAULT_ASYNC_STARTER_BATCH_SIZE = 16;
    const DEFAULT_QUEUE_ITEM_RETENTION_PERIOD = 30;
    const DEFAULT_CLEVERREACH_ASYNC_REQUEST_TIMEOUT = 1000;
    const MAX_ACCESS_TOKEN_DURATION = 86400;
    /**
     * Threshold between two runs of scheduler.
     */
    const SCHEDULER_TIME_THRESHOLD = 30;
    /**
     * Default scheduler queue name.
     */
    const DEFAULT_SCHEDULER_QUEUE_NAME = 'SchedulerCheckTaskQueue';

    /**
     * Instance of configuration repository.
     *
     * @var ConfigRepositoryInterface
     */
    protected $configRepository;
    /**
     * User access token.
     *
     * @var string
     */
    protected $accessToken;
    /**
     * Access token expiration time
     *
     * @var int
     */
    protected $accessTokenExpirationTime;
    /**
     * User information.
     *
     * @var array
     */
    protected $userInfo;
    /**
     * Context in which task is being executed.
     *
     * If no context is provided empty string is returned (global context).
     *
     * @var string
     */
    protected static $context;

    /**
     * Sets task execution context.
     *
     * When integration supports multiple accounts (middleware integration)
     * proper context must be set based on middleware account that is using
     * core library functionality. This context should then be used by business
     * services to fetch account specific data. Core will set context provided
     * upon task enqueueing before task execution.
     *
     * @param string $context Context to set
     */
    public function setContext($context)
    {
        static::$context = $context;
    }

    /**
     * Gets task execution context.
     *
     * @return string
     *   Context in which task is being executed. If no context is provided
     *   empty string is returned (global context).
     */
    public function getContext()
    {
        if (!empty(static::$context)) {
            return static::$context;
        }

        return '';
    }

    /**
     * Saves min log level in integration database.
     *
     * @param int $minLogLevel Log level.
     */
    public function saveMinLogLevel($minLogLevel)
    {
        $this->getConfigRepository()->set('CLEVERREACH_MIN_LOG_LEVEL', $minLogLevel);
    }

    /**
     * Retrieves min log level from integration database.
     *
     * @return int
     *   Log level.
     */
    public function getMinLogLevel()
    {
        return (int)$this->getConfigRepository()->get('CLEVERREACH_MIN_LOG_LEVEL') ?: Logger::WARNING;
    }

    /**
     * Retrieves access token from integration database.
     *
     * @return string|null
     *   User access token.
     */
    public function getAccessToken()
    {
        if (empty($this->accessToken)) {
            $this->accessToken = $this->getConfigRepository()->get('CLEVERREACH_ACCESS_TOKEN');
        }

        return $this->accessToken;
    }

    /**
     * Sets access token, refresh token and access token duration.
     *
     * @param \CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\AuthInfo $authInfo Authentication information object.
     */
    public function setAuthInfo($authInfo)
    {
        $this->setAccessToken($authInfo->getAccessToken());
        $this->setRefreshToken($authInfo->getRefreshToken());
        $this->setAccessTokenExpirationTime($authInfo->getAccessTokenDuration());
        $this->setUserOnline(true);
    }

    /**
     * Gets authentication information from current configuration.
     *
     * @return \CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\AuthInfo $authInfo Authentication information object.
     */
    public function getAuthInfo()
    {
        return new AuthInfo(
            $this->getAccessToken(),
            $this->getAccessTokenExpirationTime(),
            $this->getRefreshToken()
        );
    }

    /**
     * Retrieves access token expiration timestamp.
     *
     * @return int
     *   Timestamp in seconds when token will expire.
     */
    public function getAccessTokenExpirationTime()
    {
        if (!$this->accessTokenExpirationTime) {
            $this->accessTokenExpirationTime = (int) $this->getConfigRepository()
                ->get('CLEVERREACH_ACCESS_TOKEN_EXPIRATION_TIME');
        }

        return $this->accessTokenExpirationTime;
    }

    /**
     * Sets duration of access token in seconds.
     *
     * Maximum duration is one day.
     *
     * @param int $duration Duration in seconds.
     */
    public function setAccessTokenExpirationTime($duration)
    {
        if ($this->accessTokenExpirationTime) {
            // Invalidate cache.
            $this->accessTokenExpirationTime = null;
        }

        $value = time() + min($duration, self::MAX_ACCESS_TOKEN_DURATION);
        $this->getConfigRepository()->set('CLEVERREACH_ACCESS_TOKEN_EXPIRATION_TIME', $value);
    }

    /**
     * Checks whether access token is expired or not.
     *
     * @return bool
     *   True if token expired; otherwise, false.
     */
    public function isAccessTokenExpired()
    {
        $duration = $this->getAccessTokenExpirationTime();

        if ($duration) {
            return time() >= $duration;
        }

        return false;
    }

    /**
     * Sets CleverReach refresh token.
     *
     * @param string $token Refresh token.
     */
    public function setRefreshToken($token)
    {
        $this->getConfigRepository()->set('CLEVERREACH_REFRESH_TOKEN', $token);
    }

    /**
     * Gets CleverReach refresh token.
     *
     * @return string
     *   Refresh token.
     */
    public function getRefreshToken()
    {
        return $this->getConfigRepository()->get('CLEVERREACH_REFRESH_TOKEN');
    }

    /**
     * Return whether product search is enabled or not.
     *
     * @return bool
     *   If search is enabled returns true, otherwise false.
     */
    public function isProductSearchEnabled()
    {
        return false;
    }

    /**
     * Retrieves parameters needed for product search registrations.
     *
     * @return array
     *   Associative array with keys name, url, password.
     */
    public function getProductSearchParameters()
    {
        return array();
    }

    /**
     * Retrieves new field for product search registration endpoint.
     *
     * @return array
     *      Associative array with keys type, cors, icon.
     */
    public function getAdditionalProductSearchParameters()
    {
        return array(
            'type' => 'product',
            'cors' => true,
            'icon' => strtolower($this->getIntegrationName()),
        );
    }

    /**
     * Retrieves integration ID.
     *
     * @return int|null
     *   CleverReach integration ID.
     */
    public function getIntegrationId()
    {
        return (int)$this->getConfigRepository()->get('CLEVERREACH_INTEGRATION_ID');
    }

    /**
     * Retrieves user account ID.
     *
     * @return string
     *   User account ID.
     */
    public function getUserAccountId()
    {
        $userInfo = $this->getUserInfo();

        return !empty($userInfo['id']) ? $userInfo['id'] : '';
    }

    /**
     * Set default logger status (enabled or disabled).
     *
     * @param bool $status Logger status true => enabled, false => disabled.
     */
    public function setDefaultLoggerEnabled($status)
    {
        $this->getConfigRepository()->set('CLEVERREACH_DEFAULT_LOGGER_STATUS', $status);
    }

    /**
     * Return whether default logger is enabled or not.
     *
     * @return bool
     *   Logger status true => enabled, false => disabled.
     */
    public function isDefaultLoggerEnabled()
    {
        $defaultLoggerStatus = (int)$this->getConfigRepository()->get('CLEVERREACH_DEFAULT_LOGGER_STATUS');

        return ($defaultLoggerStatus === 1);
    }

    /**
     * Sets if user is online.
     *
     * @param bool $userOnline
     */
    public function setUserOnline($userOnline)
    {
        $this->getConfigRepository()->set('CLEVERREACH_IS_USER_ONLINE', $userOnline);
    }

    /**
     * Returns whether user is online.
     *
     * @return bool
     */
    public function isUserOnline()
    {
        $userOnline = $this->getConfigRepository()->get('CLEVERREACH_IS_USER_ONLINE');

        return $userOnline !== null ? (bool)$userOnline : true;
    }

    /**
     * Gets the number of maximum allowed started task at the point in time.
     *
     * This number will determine how many tasks can be in "in_progress" status at the same time.
     *
     * @return int
     *   Number of task can be run at the same time.
     */
    public function getMaxStartedTasksLimit()
    {
        return (int)$this->getConfigRepository()->get('CLEVERREACH_MAX_STARTED_TASK_LIMIT')
            ?: self::DEFAULT_MAX_STARTED_TASK_LIMIT;
    }

    /**
     * Sets the number of maximum allowed started task at the point in time.
     *
     * This number will determine how many tasks can be in "in_progress" status at the same time.
     *
     * @param int $maxStartedTaskLimit
     *   Number of task can be run at the same time.
     */
    public function setMaxStartedTaskLimit($maxStartedTaskLimit)
    {
        $this->getConfigRepository()->set('CLEVERREACH_MAX_STARTED_TASK_LIMIT', $maxStartedTaskLimit);
    }

    /**
     * Gets the batch size of async starter.
     *
     * @return int
     *   Batch size of async starter.
     */
    public function getAsyncStarterBatchSize()
    {
        return (int)$this->getConfigRepository()->get('CLEVERREACH_ASYNC_STARTER_BATCH_SIZE')
            ?: self::DEFAULT_ASYNC_STARTER_BATCH_SIZE;
    }

    /**
     * Sets the batch size of async starter.
     *
     * @param int $asyncStarterBatchSize Batch size of async starter.
     */
    public function setAsyncStarterBatchSize($asyncStarterBatchSize)
    {
        $this->getConfigRepository()->set('CLEVERREACH_ASYNC_STARTER_BATCH_SIZE', $asyncStarterBatchSize);
    }

    /**
     * Gets max queue item retention period in days
     *
     * @return int Retention period in days
     */
    public function getMaxQueueItemRetentionPeriod()
    {
        return (int)$this->getConfigRepository()->get('CLEVERREACH_QUEUE_ITEM_RETENTION_PERIOD')
            ?: self::DEFAULT_QUEUE_ITEM_RETENTION_PERIOD;
    }

    /**
     * Sets max queue item retention period in days
     *
     * @param int $maxQueueItemRetentionPeriod Retention period in days
     */
    public function setMaxQueueItemRetentionPeriod($maxQueueItemRetentionPeriod)
    {
        $this->getConfigRepository()->set('CLEVERREACH_QUEUE_ITEM_RETENTION_PERIOD', $maxQueueItemRetentionPeriod);
    }

    /**
     * Get CleverReach user information.
     *
     * @return array|null
     *   Associative array with information about logged user.
     *   [
     *      'id' => '221122',
     *      'firstname' => 'Joe',
     *      'name' => 'Doe',
     *      'street' => 'Street 12',
     *      'email' => 'joe.doe@example.com',
     *       ...
     *   ].
     */
    public function getUserInfo()
    {
        if (empty($this->userInfo)) {
            $this->userInfo = json_decode($this->getConfigRepository()->get('CLEVERREACH_USER_INFO'), true);
        }

        return $this->userInfo;
    }

    /**
     * Set CleverReach user information.
     *
     * @param array|null $userInfo Associative array with information about logged user.
     */
    public function setUserInfo($userInfo)
    {
        $this->getConfigRepository()->set('CLEVERREACH_USER_INFO', json_encode($userInfo));
        $this->userInfo = $userInfo;
    }

    /**
     * Gets indicator if first email is already built or not.
     *
     * @return bool
     *   If first email is created returns true, otherwise false.
     */
    public function isFirstEmailBuilt()
    {
        $firstEmailBuild = (int)$this->getConfigRepository()->get('CLEVERREACH_FIRST_EMAIL_BUILD');

        return $firstEmailBuild === 1;
    }

    /**
     * Sets if first email is built.
     *
     * @param bool $value If first email is created, pass true; otherwise false.
     */
    public function setIsFirstEmailBuilt($value)
    {
        $this->getConfigRepository()->set('CLEVERREACH_FIRST_EMAIL_BUILD', $value);
    }

    /**
     * Saves created groupId in CleverReach to integration.
     *
     * @param int $id CleverReach Group ID.
     */
    public function setIntegrationId($id)
    {
        $this->getConfigRepository()->set('CLEVERREACH_INTEGRATION_ID', $id);
    }

    /**
     * Gets automatic task runner wakeup delay in seconds.
     *
     * Task runner will sleep at the end of its lifecycle for this value seconds
     * before it sends wakeup signal for a new lifecycle.
     *
     * @return int|null
     *   Return null to use default system value (10).
     */
    public function getTaskRunnerWakeupDelay()
    {
        return (int)$this->getConfigRepository()->get('CLEVERREACH_TASK_RUNNER_WAKEUP_DELAY') ?: null;
    }

    /**
     * Set automatic task runner wakeup delay in seconds.
     *
     * Task runner will sleep at the end of its lifecycle for this value seconds
     * before it sends wakeup signal for a new lifecycle.
     *
     * @param int $taskRunnerWakeUpDelay Seconds how much task runner will sleep at the end of lifecycle.
     */
    public function setTaskRunnerWakeUpDelay($taskRunnerWakeUpDelay)
    {
        $this->getConfigRepository()->set('CLEVERREACH_TASK_RUNNER_WAKEUP_DELAY', $taskRunnerWakeUpDelay);
    }

    /**
     * Gets maximal time in seconds allowed for runner instance to stay in alive (running) status.
     *
     * After this period system will automatically start new runner instance and shutdown old one.
     *
     * @return int|null
     *   Return null to use default system value (60).
     */
    public function getTaskRunnerMaxAliveTime()
    {
        return (int)$this->getConfigRepository()->get('CLEVERREACH_MAX_ALIVE_TIME') ?: null;
    }

    /**
     * Sets maximal time in seconds allowed for runner instance to stay in alive (running) status.
     *
     * After this period system will automatically start new runner instance and shutdown old one.
     *
     * @param int $taskRunnerMaxAliveTime Maximal time in seconds allowed for runner instance to stay in running status.
     */
    public function setTaskRunnerMaxAliveTime($taskRunnerMaxAliveTime)
    {
        $this->getConfigRepository()->set('CLEVERREACH_MAX_ALIVE_TIME', $taskRunnerMaxAliveTime);
    }

    /**
     * Gets maximum number of failed task execution retries.
     *
     * System will retry task execution in case of error until this number is reached.
     *
     * @return int|null
     *   Return null to use default system value (5).
     */
    public function getMaxTaskExecutionRetries()
    {
        return (int)$this->getConfigRepository()->get('CLEVERREACH_MAX_TASK_EXECUTION_RETRIES') ?: null;
    }

    /**
     * Sets maximum number of failed task execution retries.
     *
     * System will retry task execution in case of error until this number is reached.
     *
     * @param int $maxTaskExecutionRetries Maximum number of failed task execution retries.
     */
    public function setMaxTaskExecutionRetries($maxTaskExecutionRetries)
    {
        $this->getConfigRepository()->set('CLEVERREACH_MAX_TASK_EXECUTION_RETRIES', $maxTaskExecutionRetries);
    }

    /**
     * Gets maximum inactivity period for a task in seconds.
     *
     * After inactivity period is passed, system will fail such tasks as expired.
     *
     * @return int|null
     *   Return null to use default system value (30).
     */
    public function getMaxTaskInactivityPeriod()
    {
        return (int)$this->getConfigRepository()->get('CLEVERREACH_MAX_TASK_INACTIVITY_PERIOD') ?: null;
    }

    /**
     * Sets maximum inactivity period for a task in seconds.
     *
     * After inactivity period is passed, system will fail such tasks as expired.
     *
     * @param int $maxTaskInactivityPeriod Maximum inactivity period for a task in seconds.
     */
    public function setMaxTaskInactivityPeriod($maxTaskInactivityPeriod)
    {
        $this->getConfigRepository()->set('CLEVERREACH_MAX_TASK_INACTIVITY_PERIOD', $maxTaskInactivityPeriod);
    }

    /**
     * Gets task runner status information.
     *
     * @return array
     *   Runner status information as an associative array.
     */
    public function getTaskRunnerStatus()
    {
        return json_decode($this->getConfigRepository()->get('CLEVERREACH_TASK_RUNNER_STATUS'), true);
    }

    /**
     * Sets task runner status information as JSON encoded string.
     *
     * @param string $guid Unique generated code.
     * @param int $timestamp Runner timestamp.
     *
     * @throws TaskRunnerStatusStorageUnavailableException
     */
    public function setTaskRunnerStatus($guid, $timestamp)
    {
        $taskRunnerStatus = json_encode(array('guid' => $guid, 'timestamp' => $timestamp));
        $response = $this->getConfigRepository()->set('CLEVERREACH_TASK_RUNNER_STATUS', $taskRunnerStatus);

        if (empty($response)) {
            throw new TaskRunnerStatusStorageUnavailableException('Task runner status storage is not available.');
        }
    }

    /**
     * Save access token in integration database.
     *
     * @param string $accessToken User access token.
     */
    public function setAccessToken($accessToken)
    {
        $this->getConfigRepository()->set('CLEVERREACH_ACCESS_TOKEN', $accessToken);
        $this->accessToken = $accessToken;
    }

    /**
     * Gets ID of registered content.
     *
     * @return int ID of registered content.
     */
    public function getProductSearchContentId()
    {
        return $this->getConfigRepository()->get('CLEVERREACH_PRODUCT_SEARCH_CONTENT_ID');
    }

    /**
     * Sets ID of registered content.
     *
     * @param int $contentId ID of registered content.
     */
    public function setProductSearchContentId($contentId)
    {
        $this->getConfigRepository()->set('CLEVERREACH_PRODUCT_SEARCH_CONTENT_ID', $contentId);
    }

    /**
     * Gets search password used on CleverReach to protect endpoint from public access.
     *
     * @return int|string
     *   Search password.
     */
    public function getProductSearchEndpointPassword()
    {
        return $this->getConfigRepository()->get('CLEVERREACH_PRODUCT_SEARCH_PASSWORD');
    }

    /**
     * Sets search password used on CleverReach to protect endpoint from public access.
     *
     * @param string $password Random string that will be used as password.
     */
    public function setProductSearchEndpointPassword($password)
    {
        $this->getConfigRepository()->set('CLEVERREACH_PRODUCT_SEARCH_PASSWORD', $password);
    }

    /**
     * Get queue item name based on context.
     *
     * @return string
     *   Queue item name.
     */
    public function getQueueName()
    {
        return $this->getContext() . ' - Default';
    }

    /**
     * Gets batch size for synchronization set in configuration.
     *
     * @return int
     *   Number of records processed in on batch.
     */
    public function getRecipientsSynchronizationBatchSize()
    {
        return (int)$this->getConfigRepository()->get('CLEVERREACH_RECIPIENT_SYNC_BATCH_SIZE') ?:
            self::INITIAL_BATCH_SIZE;
    }

    /**
     * Sets synchronization batch size.
     *
     * @param int $batchSize Number of records that will be processed in on batch.
     *
     * @return int|null
     *   True on success, false on failure.
     */
    public function setRecipientsSynchronizationBatchSize($batchSize)
    {
        return $this->getConfigRepository()->set('CLEVERREACH_RECIPIENT_SYNC_BATCH_SIZE', $batchSize);
    }

    /**
     * Sets failed login message parameters.
     *
     * @param string $params Login message parameters.
     *
     * @return bool|int
     *   True on success, false on failure.
     */
    public function setCleverReachFailedLoginMessageParams($params)
    {
        return $this->getConfigRepository()->set('CLEVERREACH_FAILED_LOGIN_MESSAGE_PARAMS', $params);
    }

    /**
     * Gets failed login message parameters.
     *
     * @return int|string
     *   Login message parameters.
     */
    public function getCleverReachFailedLoginMessageParams()
    {
        return $this->getConfigRepository()->get('CLEVERREACH_FAILED_LOGIN_MESSAGE_PARAMS');
    }

    /**
     * Returns async process request timeout.
     *
     * @return int
     *   Async process request timeout in seconds.
     */
    public function getAsyncProcessRequestTimeout()
    {
        return (int)$this->getConfigRepository()->get('CLEVERREACH_ASYNC_REQUEST_TIMEOUT')
            ?: self::DEFAULT_CLEVERREACH_ASYNC_REQUEST_TIMEOUT;
    }

    /**
     * Saves async process request timeout.
     *
     * @param int $value Async process request timeout in seconds.
     */
    public function setAsyncProcessRequestTimeout($value)
    {
        $this->getConfigRepository()->set('CLEVERREACH_ASYNC_REQUEST_TIMEOUT', $value);
    }

    /**
     * Gets the name of CleverReach group/list that will be created.
     *
     * @return string
     *   List name.
     */
    public function getIntegrationListName()
    {
        return $this->getIntegrationName();
    }

    /**
     * Retrieves generated token for webhook validation
     *
     * @return string
     *   Token used for webhook validation.
     */
    public function getCrEventHandlerVerificationToken()
    {
        $verificationToken = $this->getConfigRepository()->get('CLEVERREACH_EVENT_VERIFICATION_TOKEN');
        if (empty($verificationToken)) {
            $verificationToken = md5(time() . $this->getAccessToken());
            $this->getConfigRepository()->set('CLEVERREACH_EVENT_VERIFICATION_TOKEN', $verificationToken);
        }

        return $verificationToken;
    }

    /**
     * Store token that CleverReach will send upon successful registration of webhook handler and in webhook calls
     *
     * @param string $token Call token used in webhooks calls.
     */
    public function setCrEventHandlerCallToken($token)
    {
        $this->getConfigRepository()->set('CLEVERREACH_EVENT_CALL_TOKEN', $token);
    }

    /**
     * Retrieves call token that CleverReach will send in webhook calls so it will be used for call authenticity
     *
     * @return string
     *   Call token used in webhooks calls.
     */
    public function getCrEventHandlerCallToken()
    {
        return $this->getConfigRepository()->get('CLEVERREACH_EVENT_CALL_TOKEN');
    }

    /**
     * Sets token that CleverReach will send upon successful registration of webhook handler and in webhook calls for form events.
     *
     * @param string $token Call token used in webhooks calls.
     */
    public function setCrFormEventHandlerCallToken($token)
    {
        $this->getConfigRepository()->set('CLEVERREACH_FORM_EVENT_CALL_TOKEN', $token);
    }

    /**
     * Returns call token that CleverReach will send in webhook calls for form events so it will be used for call authenticity
     *
     * @return string
     *   Call token used in webhooks calls.
     */
    public function getCrFormEventHandlerCallToken()
    {
        return $this->getConfigRepository()->get('CLEVERREACH_FORM_EVENT_CALL_TOKEN');
    }

    /**
     * Retrieves URL of a controller that will handle webhook calls (GET for verification, POST for handling)
     *
     * @return string
     *   URL of webhook handler controller.
     */
    abstract public function getCrEventHandlerURL();

    /**
     * Gets indicator if import statistics should be displayed or not.
     *
     * @return bool If import statistics should be displayed returns true, otherwise false.
     */
    public function isImportStatisticsDisplayed()
    {
        $importStatisticsDisplayed = (int)$this->getConfigRepository()->get('CLEVERREACH_IMPORT_STATISTICS_DISPLAYED');

        return $importStatisticsDisplayed === 1;
    }

    /**
     * Sets if import statistics should be displayed.
     *
     * @param bool $value If import statistics should be displayed pass true, otherwise false.
     */
    public function setImportStatisticsDisplayed($value)
    {
        $this->getConfigRepository()->set('CLEVERREACH_IMPORT_STATISTICS_DISPLAYED', $value);
    }

    /**
     * Gets number of synced recipients.
     *
     * @return int Number of synced recipients.
     */
    public function getNumberOfSyncedRecipients()
    {
        return $this->getConfigRepository()->get('CLEVERREACH_NUMBER_OF_SYNCED_RECIPIENTS');
    }

    /**
     * Sets number of synced recipients.
     *
     * @param int $value Number of synced recipients.
     */
    public function setNumberOfSyncedRecipients($value)
    {
        $this->getConfigRepository()->set('CLEVERREACH_NUMBER_OF_SYNCED_RECIPIENTS', $value);
    }

    /**
     * Returns scheduler time threshold between checks.
     *
     * @return int Threshold in seconds.
     */
    public function getSchedulerTimeThreshold()
    {
        $schedulerTimeThreshold = $this->getConfigRepository()->get('CLEVERREACH_SCHEDULER_TIME_TRESHOLD');

        return !empty($schedulerTimeThreshold) ? (int) $schedulerTimeThreshold :  static::SCHEDULER_TIME_THRESHOLD;
    }

    /**
     * Returns scheduler queue name.
     *
     * @return string Queue name.
     */
    public function getSchedulerQueueName()
    {
        $schedulerQueueName = $this->getConfigRepository()->get('CLEVERREACH_SCHEDULER_QUEUE_NAME');

        return !empty($schedulerQueueName) ? $schedulerQueueName : static::DEFAULT_SCHEDULER_QUEUE_NAME;
    }

    /**
     * Returns last poll ID retrieved from CleverReach poll endpoint.
     *
     * @return string last poll id
     */
    public function getLastPollId()
    {
        return $this->getConfigRepository()->get('CLEVERREACH_LAST_POLL_ID');
    }

    /**
     * Sets last poll ID retrieved from CleverReach poll endpoint.
     *
     * @param string $pollId
     */
    public function setLastPollId($pollId)
    {
        $this->getConfigRepository()->set('CLEVERREACH_LAST_POLL_ID', $pollId);
    }

    /**
     * Retrieves user language.
     *
     * @return string
     */
    public function getLanguage()
    {
        $lang = $this->getConfigRepository()->get('CLEVERREACH_USER_LANGUAGE');

        return !empty($lang) ? $lang : 'en';
    }

    /**
     * Sets user language.
     *
     * @param string $language
     */
    public function setLanguage($language)
    {
        $this->getConfigRepository()->set('CLEVERREACH_USER_LANGUAGE', $language);
    }

    /**
     * Return whether integration supports integration with CleverReach or not.
     *
     * @return bool
     *   If forms are enabled returns true, otherwise false.
     */
    public function isFormSyncEnabled()
    {
        return false;
    }

    /**
     * Returns whether all forms should be retrieved from CleverReach and cached,
     * instead of only forms connected to synchronization list.
     *
     * @return bool
     */
    public function shouldCacheAllForms()
    {
        return false;
    }

    /**
     * Return name of CleverReach form for specific integration
     *
     * @return string
     */
    public function getIntegrationFormName()
    {
        return $this->getIntegrationListName();
    }

    /**
     * Retrieves color code of authentication iframe background.
     *
     * @return string
     *     Color code.
     */
    public function getAuthIframeColor()
    {
        return 'ffffff';
    }

    /**
     * Return url of plugin with CleverReach poll popup
     *
     * @return string
     */
    abstract public function getPluginUrl();

    /**
     * Return notification message that will be shown user as system notification
     *
     * @return string
     */
    abstract public function getNotificationMessage();

    /**
     * Retrieves integration name.
     *
     * @return string
     *   Integration name.
     */
    abstract public function getIntegrationName();

    /**
     * CleverReach client ID for specific integration.
     *
     * @return string
     *   Integration client ID.
     */
    abstract public function getClientId();

    /**
     * CleverReach client secret for specific integration.
     *
     * @return string
     *   Integration client secret.
     */
    abstract public function getClientSecret();

    /**
     * Gets instance on configuration service.
     *
     * @return ConfigRepositoryInterface
     *   Instance of configuration service.
     */
    protected function getConfigRepository()
    {
        if ($this->configRepository === null) {
            $this->configRepository = ServiceRegister::getService(ConfigRepositoryInterface::CLASS_NAME);
        }

        return $this->configRepository;
    }
}
