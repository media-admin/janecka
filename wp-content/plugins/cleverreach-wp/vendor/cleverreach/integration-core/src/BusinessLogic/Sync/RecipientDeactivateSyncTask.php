<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync;

use CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\SpecialTag;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\SpecialTagCollection;

/**
 * Class RecipientDeactivateSyncTask.
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync
 */
class RecipientDeactivateSyncTask extends RecipientStatusUpdateSyncTask
{
    /**
     * Runs task execution.
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function execute()
    {
        $progress = 5;
        $this->reportProgress($progress);
        $proxy = $this->getProxy();
        $configService = $this->getConfigService();
        $step = 50 / count($this->recipientEmails);
        $recipients = array();
        foreach ($this->recipientEmails as $recipient) {
            if (empty($recipient) || (is_array($recipient) && empty($recipient['email']))) {
                continue;
            }

            $email = is_array($recipient) ? $recipient['email'] : $recipient;
            $recipient = $proxy->getRecipient($configService->getIntegrationId(), $email);

            if ($recipient) {
                $specialTags = new SpecialTagCollection(array(SpecialTag::subscriber()));
                // Tags collection on recipient has both special and regular tags.
                // We need to remove subscriber special tag
                $recipient->getTags()->remove($specialTags);
                $specialTags->markDeleted();
                $recipient->getTags()->add($specialTags);
                $recipient->setNewsletterSubscription(false);
                $recipient->setActive(false);
            }

            $recipients[] = $recipient;

            $progress += $step;
            $this->reportProgress($progress);
        }

        $proxy->deactivateRecipients($recipients);

        $this->reportProgress(100);
    }
}
