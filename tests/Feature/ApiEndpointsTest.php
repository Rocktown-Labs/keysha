<?php

use App\Models\DeviceAuthorization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('device authorization request and token poll flow works', function () {
    $codeRes = $this->postJson('/api/v1/auth/device/code', [
        'device_name' => 'Test Terminal',
    ]);

    $codeRes->assertStatus(200)
        ->assertJsonStructure(['device_code', 'user_code', 'verification_uri']);

    $deviceCode = $codeRes->json('device_code');
    $userCode = $codeRes->json('user_code');

    // Poll before approval
    $pendingPoll = $this->postJson('/api/v1/auth/device/token', ['device_code' => $deviceCode]);
    $pendingPoll->assertStatus(400)->assertJson(['error' => 'authorization_pending']);

    // Approve device
    $user = User::factory()->create();
    $auth = DeviceAuthorization::where('user_code_hash', hash('sha256', str_replace('-', '', $userCode)))->first();
    $auth->update(['status' => 'approved', 'approved_by' => $user->id]);

    // Poll after approval
    $successPoll = $this->postJson('/api/v1/auth/device/token', ['device_code' => $deviceCode]);
    $successPoll->assertStatus(200)->assertJsonStructure(['access_token', 'token_type']);
});

test('api allows project creation and variable management', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test', ['*'])->plainTextToken;

    // 1. Create Project
    $createProject = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/projects', ['name' => 'Mingle']);

    $createProject->assertStatus(201);
    $slug = $createProject->json('project.slug');

    // 2. Set Variable
    $setVar = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/variables/set', [
            'project_slug' => $slug,
            'environment_slug' => 'production',
            'key' => 'STRIPE_SECRET_KEY',
            'value' => 'sk_test_123456',
        ]);

    $setVar->assertStatus(200)->assertJson(['status' => 'success']);

    // 3. Inspect Variable Metadata
    $inspect = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/v1/projects/{$slug}/variables/STRIPE_SECRET_KEY");

    $inspect->assertStatus(200)
        ->assertJson([
            'key' => 'STRIPE_SECRET_KEY',
            'classification' => 'secret',
            'provider' => 'stripe',
        ]);

    // 4. Get Variable Plaintext Value
    $getVal = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/v1/projects/{$slug}/variables/STRIPE_SECRET_KEY/value?env=production");

    $getVal->assertStatus(200)
        ->assertJson([
            'key' => 'STRIPE_SECRET_KEY',
            'value' => 'sk_test_123456',
        ]);
});
