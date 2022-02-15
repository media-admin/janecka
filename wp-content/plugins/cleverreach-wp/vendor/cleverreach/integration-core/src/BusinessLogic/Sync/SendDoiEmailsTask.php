<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync;

use CleverReach\WordPress\IntegrationCore\BusinessLogic\Forms\Models\DoiEmail;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Proxy\FormProxy;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Logger\Logger;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Serializer;

/**
 * Class SendDoiEmailsTask
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync
 */
class SendDoiEmailsTask extends BaseSyncTask
{
    /**
     * @var DoiEmail[]
     */
    private $doiEmails;
    /**
     * @var FormProxy
     */
    private $formProxy;

    /**
     * Transforms array into entity.
     *
     * @param array $array
     *
     * @return \CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\Serializable
     */
    public static function fromArray($array)
    {
        $serializedDoiEmails = $array['doiEmails'];
        $doiEmails = array();
        foreach ($serializedDoiEmails as $serializedDoiEmail) {
            $doiEmails[] = Serializer::unserialize($serializedDoiEmail);
        }

        return new static($doiEmails);
    }

    /**
     * SendDoiEmailsTask constructor.
     *
     * @param DoiEmail[] $doiEmails
     */
    public function __construct($doiEmails)
    {
        $this->doiEmails = $doiEmails;
    }

    /**
     * String representation of object
     *
     * @inheritdoc
     */
    public function serialize()
    {
        return Serializer::serialize($this->doiEmails);
    }

    /**
     * Constructs the object.
     *
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        $this->doiEmails = Serializer::unserialize($serialized);
    }

    /**
     * Transforms entity to array.
     *
     * @return array
     */
    public function toArray()
    {
        $serializedDoiEmails = array();
        foreach ($this->doiEmails as $doiEmail) {
            $serializedDoiEmails[] = Serializer::serialize($doiEmail);
        }

        return array('doiEmails' => $serializedDoiEmails);
    }

    /**
     * Runs task execution.
     */
    public function execute()
    {
        $currentProgress = 5;
        $this->reportProgress($currentProgress);

        if (!empty($this->doiEmails)) {
            $progressStep = 95 / \count($this->doiEmails);

            foreach ($this->doiEmails as $doiEmail) {
                try {
                    $params = array(
                        'ip' => $doiEmail->getIp(),
                        'referer' => $doiEmail->getReferer(),
                        'agent' => $doiEmail->getAgent(),
                    );
                    $this->getFormProxy()->sendDoiEmail($doiEmail->getFormId(), $doiEmail->getEmail(), $params);
                } catch (\Exception $e) {
                    Logger::logError('Failed to send DOI email. Error: ' . $e->getMessage());
                }

                $currentProgress += $progressStep;
                $this->reportProgress($currentProgress);
            }
        }

        $this->reportProgress(100);
    }

    /**
     * Returns an instance of form proxy.
     *
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
