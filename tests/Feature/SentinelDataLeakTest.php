<?php

use App\Crypto\EnvironmentMasterKeyProvider;
use App\Crypto\VaultEngine;
use App\Models\User;
use App\Models\VaultEntry;
use App\Models\VaultEntryVersion;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('sentinel secret plaintext is never persisted in database ciphertext, audit events, or metadata', function () {
    $sentinelSecret = 'KEYSHA_SENTINEL_SECRET_DO_NOT_LEAK_998877665544';

    $user = User::factory()->create();
    $workspace = $user->ensurePersonalWorkspace();

    $entry = VaultEntry::create([
        'workspace_id' => $workspace->id,
        'label' => 'SENTINEL_KEY',
        'classification' => 'secret',
        'sharing_mode' => 'restricted',
        'created_by' => $user->id,
    ]);

    $masterProvider = new EnvironmentMasterKeyProvider;
    $vault = new VaultEngine($masterProvider);

    $version = $vault->encryptSecret($workspace, $entry, $sentinelSecret, $user);

    // 1. Assert DB version ciphertext does NOT contain plaintext
    $rawVersionRecord = VaultEntryVersion::find($version->id);
    expect($rawVersionRecord->ciphertext)->not->toContain($sentinelSecret);

    // 2. Audit log test
    $audit = new AuditService;
    $auditRecord = $audit->log(
        workspace: $workspace,
        event: 'secret.revealed',
        actor: $user,
        subjectType: VaultEntry::class,
        subjectId: $entry->id,
        metadata: ['value' => $sentinelSecret, 'key' => 'SENTINEL_KEY']
    );

    // Assert audit metadata redacted the value
    expect(json_encode($auditRecord->metadata))->not->toContain($sentinelSecret);
    expect($auditRecord->metadata['value'])->toBe('[REDACTED]');
});
