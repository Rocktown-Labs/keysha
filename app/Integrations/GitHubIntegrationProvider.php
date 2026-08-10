<?php

namespace App\Integrations;

use App\Models\Integration;
use App\Models\IntegrationTarget;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GitHubIntegrationProvider implements IntegrationProvider
{
    public function validateConnection(Integration $integration): bool
    {
        $token = $integration->metadata['token'] ?? null;
        if (! $token) {
            return false;
        }

        $res = Http::withToken($token)
            ->withHeaders(['User-Agent' => 'Keysha-Vault'])
            ->get('https://api.github.com/user');

        return $res->successful();
    }

    public function listTargets(Integration $integration): array
    {
        $token = $integration->metadata['token'] ?? null;
        if (! $token) {
            return [];
        }

        $res = Http::withToken($token)
            ->withHeaders(['User-Agent' => 'Keysha-Vault'])
            ->get('https://api.github.com/user/repos');

        if (! $res->successful()) {
            return [];
        }

        return collect($res->json())->map(fn ($r) => [
            'external_id' => (string) $r['id'],
            'external_type' => 'repository',
            'label' => $r['full_name'],
            'metadata' => ['owner' => $r['owner']['login'], 'repo' => $r['name']],
        ])->all();
    }

    public function pushSecret(Integration $integration, IntegrationTarget $target, string $remoteKey, string $secretValue): bool
    {
        $token = $integration->metadata['token'] ?? null;
        $owner = $target->metadata['owner'] ?? null;
        $repo = $target->metadata['repo'] ?? null;

        if (! $token || ! $owner || ! $repo) {
            throw new RuntimeException('Missing GitHub target metadata.');
        }

        // 1. Get GitHub repo public key for secret encryption
        $pkRes = Http::withToken($token)
            ->withHeaders(['User-Agent' => 'Keysha-Vault'])
            ->get("https://api.github.com/repos/{$owner}/{$repo}/actions/secrets/public-key");

        if (! $pkRes->successful()) {
            throw new RuntimeException("Failed to fetch GitHub public key for {$owner}/{$repo}");
        }

        $pkData = $pkRes->json();
        $pubKeyBytes = base64_decode($pkData['key']);
        $keyId = $pkData['key_id'];

        // 2. Encrypt secret using Libsodium sealed box
        $sealedBytes = sodium_crypto_box_seal($secretValue, $pubKeyBytes);
        $encryptedBase64 = base64_encode($sealedBytes);

        // 3. Put secret to GitHub
        $putRes = Http::withToken($token)
            ->withHeaders(['User-Agent' => 'Keysha-Vault'])
            ->put("https://api.github.com/repos/{$owner}/{$repo}/actions/secrets/{$remoteKey}", [
                'encrypted_value' => $encryptedBase64,
                'key_id' => $keyId,
            ]);

        return $putRes->successful();
    }

    public function pushConfig(Integration $integration, IntegrationTarget $target, string $remoteKey, string $configValue): bool
    {
        $token = $integration->metadata['token'] ?? null;
        $owner = $target->metadata['owner'] ?? null;
        $repo = $target->metadata['repo'] ?? null;

        if (! $token || ! $owner || ! $repo) {
            throw new RuntimeException('Missing GitHub target metadata.');
        }

        $putRes = Http::withToken($token)
            ->withHeaders(['User-Agent' => 'Keysha-Vault'])
            ->post("https://api.github.com/repos/{$owner}/{$repo}/actions/variables", [
                'name' => $remoteKey,
                'value' => $configValue,
            ]);

        return $putRes->successful();
    }
}
