<?php

namespace Sendcloud\Shipping\Service\Utility\Task;

use Sendcloud\Shipping\Core\BusinessLogic\Sync\BaseSyncTask;

class UpdateIntegrationTask extends BaseSyncTask
{

    public function execute()
    {
        if ($this->getConnectService()->isIntegrationConnected()) {
            $this->getConfigService()->updateWebhookUrl();
        }
    }
}