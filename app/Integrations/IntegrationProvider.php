<?php

namespace App\Integrations;

use App\Models\Integration;
use App\Models\IntegrationTarget;

interface IntegrationProvider
{
    public function validateConnection(Integration $integration): bool;

    public function listTargets(Integration $integration): array;

    public function pushSecret(Integration $integration, IntegrationTarget $target, string $remoteKey, string $secretValue): bool;

    public function pushConfig(Integration $integration, IntegrationTarget $target, string $remoteKey, string $configValue): bool;
}
