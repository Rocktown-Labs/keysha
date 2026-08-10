<?php

namespace App\Jobs;

use App\Crypto\VaultEngine;
use App\Integrations\GitHubIntegrationProvider;
use App\Models\IntegrationMapping;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

class SyncIntegrationMappingJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $mappingId
    ) {}

    public function handle(VaultEngine $vault): void
    {
        $mapping = IntegrationMapping::with(['environmentBinding.vaultEntry.currentVersion', 'environmentBinding.environment.project.workspace', 'integrationTarget.integration'])
            ->find($this->mappingId);

        if (! $mapping || ! $mapping->enabled) {
            return;
        }

        $binding = $mapping->environmentBinding;
        if (! $binding || ! $binding->vaultEntry || ! $binding->vaultEntry->currentVersion) {
            return;
        }

        $workspace = $binding->environment->project->workspace;
        $version = $binding->vaultEntry->currentVersion;

        // Decrypt immediately before provider transmission
        $plaintext = $vault->decryptSecret($version, $workspace);

        $target = $mapping->integrationTarget;
        $integration = $target->integration;

        $provider = new GitHubIntegrationProvider;

        try {
            if ($binding->vaultEntry->classification === 'secret') {
                $success = $provider->pushSecret($integration, $target, $mapping->remote_key, $plaintext);
            } else {
                $success = $provider->pushConfig($integration, $target, $mapping->remote_key, $plaintext);
            }

            if ($success) {
                $mapping->update(['last_synced_version_id' => $version->id]);
            }
        } catch (\Throwable $e) {
            // Log sanitized error without secret content
            throw new RuntimeException("Integration sync failed for {$mapping->remote_key}: {$e->getMessage()}");
        } finally {
            // Discard plaintext
            unset($plaintext);
        }
    }
}
