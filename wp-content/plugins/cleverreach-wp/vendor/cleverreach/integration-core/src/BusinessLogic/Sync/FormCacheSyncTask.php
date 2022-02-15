<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync;

use CleverReach\WordPress\IntegrationCore\BusinessLogic\Forms\Models\Form;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Proxy\FormProxy;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\RepositoryRegistry;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Interfaces\RepositoryInterface;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\QueryFilter\Operators;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\QueryFilter\QueryFilter;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\TimeProvider;

/**
 * Class FormCacheSyncTask
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync
 */
class FormCacheSyncTask extends BaseSyncTask
{
    const INITIAL_SYNC_PROGRESS = 10;
    /**
     * @var FormProxy
     */
    private $formProxy;
    /**
     * Current task progress in percent
     *
     * @var int
     */
    private $currentProgress = self::INITIAL_SYNC_PROGRESS;
    /**
     * Progress step
     *
     * @var int
     */
    private $progressStep;
    /**
     * Array of key-value pairs representing group ID and group name.
     *
     * @var array
     */
    private $groupMapping = array();
    /**
     * @var RepositoryInterface
     */
    private $formRepository;

    /**
     * @inheritDoc
     *
     * @throws InvalidConfigurationException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws RefreshTokenExpiredException
     * @throws RepositoryNotRegisteredException
     * @throws QueryFilterInvalidParamException
     */
    public function execute()
    {
        $this->reportProgress(self::INITIAL_SYNC_PROGRESS);
        $this->currentProgress = self::INITIAL_SYNC_PROGRESS;
        $sourceForms = $this->getFormProxy()->getFormList();
        $this->reportAlive();
        $existingForms = $this->getExistingForms();

        $totalNumberOfForms = count($sourceForms) + count($existingForms);
        if ($totalNumberOfForms === 0) {
            $this->reportProgress(100);
            return;
        }

        $this->progressStep = (int)((100 - $this->currentProgress) / $totalNumberOfForms);
        $this->updateCache($sourceForms, $existingForms);

        $this->reportProgress(100);
    }

    /**
     * Saves forms to cache table
     *
     * @param array $sourceForms forms fetched from CleverReach
     * @param Form[] $existingForms forms from cache
     *
     * @throws InvalidConfigurationException
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws RefreshTokenExpiredException
     */
    protected function updateCache(array $sourceForms, array $existingForms)
    {
        $this->deleteNonExistingFormsFromCache($sourceForms, $existingForms);
        $this->saveNewForms($sourceForms);
    }

    /**
     * Return existing forms from cache
     *
     * @return Form[]
     *
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     */
    protected function getExistingForms()
    {
        $filter = new QueryFilter();
        $filter->where('context', Operators::EQUALS, $this->getConfigService()->getContext());
        /** @var Form[] $existingForms */
        $existingForms = $this->getFormRepository()->select($filter);

        return is_array($existingForms) ? $existingForms : array();
    }

    /**
     * Removes forms from cache if not exist on CleverReach
     *
     * @param array $sourceForms forms fetched from API
     * @param Form[] $existingForms forms from existing cache
     *
     * @throws RepositoryNotRegisteredException
     */
    private function deleteNonExistingFormsFromCache(array $sourceForms, array $existingForms)
    {
        $fetchedFormIds = array_column($sourceForms, 'id');
        foreach ($existingForms as $existingForm) {
            $this->incrementProgress();
            if (!in_array($existingForm->getFormId(), $fetchedFormIds, false)) {
                $this->deleteForm($existingForm);
            }
        }
    }

    /**
     * Removes form from cache
     *
     * @param Form $form for delete
     *
     * @throws RepositoryNotRegisteredException
     */
    protected function deleteForm(Form $form)
    {
        $this->getFormRepository()->delete($form);
    }

    /**
     * Saves forms to cache table
     *
     * @param array $sourceForms forms fetched from API
     *
     * @throws InvalidConfigurationException
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws RefreshTokenExpiredException
     */
    private function saveNewForms(array $sourceForms)
    {
        $this->groupMapping = $this->getGroupMapping();

        foreach ($sourceForms as $sourceForm) {
            $this->incrementProgress();
            if ($this->getConfigService()->shouldCacheAllForms()
                || (int)$sourceForm['customer_tables_id'] === $this->getConfigService()->getIntegrationId()) {
                $this->cacheFormIfUpdated($sourceForm);
            }
        }
    }

    /**
     * Saves form to cache table if is new or updated
     *
     * @param array $sourceForm
     *
     * @throws InvalidConfigurationException
     * @throws RepositoryNotRegisteredException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws RefreshTokenExpiredException
     * @throws QueryFilterInvalidParamException
     */
    private function cacheFormIfUpdated(array $sourceForm)
    {
        $form = $this->getForm($sourceForm);

        $formHtml = $this->getFormProxy()->getFormById($form->getFormId());
        $this->reportAlive();
        if ($form->getName() !== $sourceForm['name']
            || $form->getGroupId() !== (int)$sourceForm['customer_tables_id']
            || $form->getHash() !== md5($formHtml)
        ) {
            if (array_key_exists($sourceForm['customer_tables_id'], $this->groupMapping)) {
                $form->setName($sourceForm['name']);
                $form->setHtmlAndCreateHash($formHtml);
                $groupName = $this->groupMapping[$sourceForm['customer_tables_id']];
                $form->setGroupId((int)$sourceForm['customer_tables_id']);
                $form->setGroupName($groupName);
                $this->saveForm($form);
            }
        }
    }

    /**
     * Returns mappings for groups that exist on CleverReach for the current user.
     *
     * @return array
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    private function getGroupMapping()
    {
        $groups = $this->getProxy()->getGroups();
        $mapping = array();
        foreach ($groups as $group) {
            $mapping[$group['id']] = $group['name'];
        }

        return $mapping;
    }

    /**
     * Creates form object based on source
     *
     * @param array $sourceForm form fetched from API
     *
     * @return Form
     *   form object
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    private function getForm(array $sourceForm)
    {
        $filter = new QueryFilter();
        $filter->where('formId', Operators::EQUALS, $sourceForm['id'])
            ->where('context', Operators::EQUALS, $this->getConfigService()->getContext());

        /** @var Form $form */
        $form = $this->getFormRepository()->selectOne($filter);
        if ($form === null) {
            $form = new Form(
                $sourceForm['id'],
                $sourceForm['name'],
                $this->getConfigService()->getContext()
            );
        }

        return $form;
    }

    /**
     * @param Form $form for saving
     *
     * @throws RepositoryNotRegisteredException
     */
    protected function saveForm(Form $form)
    {
        /** @var TimeProvider $timeProvider */
        $timeProvider = ServiceRegister::getService(TimeProvider::CLASS_NAME);
        $form->setLastUpdateTimestamp($timeProvider->getCurrentLocalTime()->getTimestamp());
        $form->getId() === null ? $this->getFormRepository()->save($form) : $this->getFormRepository()->update($form);
    }

    /**
     * Increment progress base on progress step
     */
    private function incrementProgress()
    {
        $this->currentProgress += $this->progressStep;

        $this->reportProgress($this->currentProgress);
    }

    /**
     * @return RepositoryInterface
     *
     * @throws RepositoryNotRegisteredException
     */
    private function getFormRepository()
    {
        if ($this->formRepository === null) {
            $this->formRepository = RepositoryRegistry::getRepository(Form::getClassName());
        }

        return $this->formRepository;
    }

    /**
     * @return FormProxy
     */
    private function getFormProxy()
    {
        if ($this->formProxy === null) {
            $this->formProxy = ServiceRegister::getService(FormProxy::CLASS_NAME);
        }

        return $this->formProxy;
    }
}
