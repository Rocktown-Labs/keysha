<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('personal')->default(false);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('workspace_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default('member');
            $table->timestamps();

            $table->unique(['workspace_id', 'user_id']);
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->unique(['workspace_id', 'slug']);
        });

        Schema::create('environments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->integer('position')->default(0);
            $table->boolean('protected')->default(false);
            $table->timestamps();

            $table->unique(['project_id', 'slug']);
        });

        Schema::create('project_variables', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('key');
            $table->string('classification')->default('secret');
            $table->text('description')->nullable();
            $table->string('provider_hint')->nullable();
            $table->boolean('required')->default(true);
            $table->integer('position')->default(0);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['project_id', 'key']);
        });

        Schema::create('provider_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('provider_slug');
            $table->string('label');
            $table->string('external_account_reference')->nullable();
            $table->string('dashboard_url')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('vault_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignUuid('provider_profile_id')->nullable()->constrained('provider_profiles')->nullOnDelete();
            $table->string('label');
            $table->string('classification')->default('secret');
            $table->string('sharing_mode')->default('restricted');
            $table->uuid('current_version_id')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });

        Schema::create('environment_bindings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('environment_id')->constrained('environments')->cascadeOnDelete();
            $table->foreignUuid('project_variable_id')->constrained('project_variables')->cascadeOnDelete();
            $table->foreignUuid('vault_entry_id')->constrained('vault_entries')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['environment_id', 'project_variable_id']);
        });

        Schema::create('vault_entry_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('vault_entry_id')->constrained('vault_entries')->cascadeOnDelete();
            $table->text('ciphertext');
            $table->string('nonce');
            $table->text('wrapped_data_key');
            $table->string('wrapped_data_key_nonce');
            $table->string('algorithm')->default('XChaCha20-Poly1305');
            $table->integer('crypto_schema_version')->default(1);
            $table->integer('workspace_key_version')->default(1);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('workspace_keys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->integer('version')->default(1);
            $table->text('wrapped_key');
            $table->string('nonce');
            $table->string('master_key_version')->default('v1');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('device_authorizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('device_code_hash')->index();
            $table->string('user_code_hash')->index();
            $table->string('device_name');
            $table->string('requested_host')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('expires_at');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('integrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('provider');
            $table->string('label');
            $table->string('status')->default('active');
            $table->text('encrypted_credentials')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('integration_targets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('integration_id')->constrained('integrations')->cascadeOnDelete();
            $table->string('external_type');
            $table->string('external_id');
            $table->string('label');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('integration_mappings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('environment_binding_id')->constrained('environment_bindings')->cascadeOnDelete();
            $table->foreignUuid('integration_target_id')->constrained('integration_targets')->cascadeOnDelete();
            $table->string('remote_key');
            $table->string('remote_type');
            $table->foreignUuid('last_synced_version_id')->nullable()->constrained('vault_entry_versions')->nullOnDelete();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('sync_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('integration_id')->constrained('integrations')->cascadeOnDelete();
            $table->foreignUuid('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignUuid('environment_id')->nullable()->constrained('environments')->nullOnDelete();
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sync_run_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sync_run_id')->constrained('sync_runs')->cascadeOnDelete();
            $table->foreignUuid('integration_mapping_id')->constrained('integration_mappings')->cascadeOnDelete();
            $table->string('status');
            $table->string('error_code')->nullable();
            $table->text('sanitized_error')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_device_id')->nullable();
            $table->string('event');
            $table->string('subject_type')->nullable();
            $table->string('subject_id')->nullable();
            $table->foreignUuid('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignUuid('environment_id')->nullable()->constrained('environments')->nullOnDelete();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('system_recoveries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('master_key_fingerprint');
            $table->text('encrypted_master_key_backup');
            $table->string('recovery_nonce');
            $table->integer('recovery_schema_version')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_recoveries');
        Schema::dropIfExists('audit_events');
        Schema::dropIfExists('sync_run_items');
        Schema::dropIfExists('sync_runs');
        Schema::dropIfExists('integration_mappings');
        Schema::dropIfExists('integration_targets');
        Schema::dropIfExists('integrations');
        Schema::dropIfExists('device_authorizations');
        Schema::dropIfExists('workspace_keys');
        Schema::dropIfExists('vault_entry_versions');
        Schema::dropIfExists('environment_bindings');
        Schema::dropIfExists('vault_entries');
        Schema::dropIfExists('provider_profiles');
        Schema::dropIfExists('project_variables');
        Schema::dropIfExists('environments');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('workspace_members');
        Schema::dropIfExists('workspaces');
    }
};
