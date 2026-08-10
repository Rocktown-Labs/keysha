<?php

namespace App\Http\Controllers\Api;

use App\Crypto\ProviderRegistry;
use App\Crypto\VaultEngine;
use App\Http\Controllers\Controller;
use App\Models\Environment;
use App\Models\EnvironmentBinding;
use App\Models\Project;
use App\Models\ProjectVariable;
use App\Models\VaultEntry;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VaultApiController extends Controller
{
    public function whoami(Request $request)
    {
        $user = $request->user();
        $workspace = $user->personalWorkspace();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'workspace' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'slug' => $workspace->slug,
            ],
        ]);
    }

    public function listProjects(Request $request)
    {
        $user = $request->user();
        $workspace = $user->personalWorkspace();

        $projects = Project::where('workspace_id', $workspace->id)
            ->with(['environments', 'variables'])
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'description' => $p->description,
                'environments' => $p->environments->map(fn ($e) => $e->slug),
                'variables_count' => $p->variables->count(),
            ]);

        return response()->json(['projects' => $projects]);
    }

    public function createProject(Request $request, AuditService $audit)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $user = $request->user();
        $workspace = $user->personalWorkspace();

        $slug = Str::slug($request->input('name'));

        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => $request->input('name'),
            'slug' => $slug,
            'description' => $request->input('description'),
            'created_by' => $user->id,
        ]);

        $project->environments()->createMany([
            ['name' => 'Development', 'slug' => 'development', 'position' => 1, 'protected' => false],
            ['name' => 'Preview', 'slug' => 'preview', 'position' => 2, 'protected' => false],
            ['name' => 'Production', 'slug' => 'production', 'position' => 3, 'protected' => true],
        ]);

        $audit->log($workspace, 'project.created', $user, Project::class, $project->id, $project->id);

        return response()->json(['project' => $project], 201);
    }

    public function listVariables(Request $request, string $projectSlug)
    {
        $user = $request->user();
        $workspace = $user->personalWorkspace();

        $project = Project::where('workspace_id', $workspace->id)
            ->where('slug', $projectSlug)
            ->with(['variables', 'environments.bindings.vaultEntry'])
            ->firstOrFail();

        $envSlug = $request->query('env', 'production');
        $env = $project->environments->firstWhere('slug', $envSlug) ?? $project->environments->first();

        $variables = $project->variables->map(function ($var) use ($env) {
            $binding = $env->bindings->firstWhere('project_variable_id', $var->id);
            $isConfigured = $binding && $binding->vaultEntry && $binding->vaultEntry->current_version_id;

            return [
                'id' => $var->id,
                'key' => $var->key,
                'classification' => $var->classification,
                'provider' => $var->provider_hint ?? 'custom',
                'configured' => (bool) $isConfigured,
                'environment' => $env->slug,
            ];
        });

        return response()->json([
            'project' => $project->name,
            'environment' => $env->slug,
            'variables' => $variables,
        ]);
    }

    public function inspectVariable(Request $request, string $projectSlug, string $key)
    {
        $user = $request->user();
        $workspace = $user->personalWorkspace();

        $project = Project::where('workspace_id', $workspace->id)
            ->where('slug', $projectSlug)
            ->firstOrFail();

        $var = ProjectVariable::where('project_id', $project->id)
            ->where('key', strtoupper($key))
            ->firstOrFail();

        return response()->json([
            'key' => $var->key,
            'classification' => $var->classification,
            'provider' => $var->provider_hint ?? ProviderRegistry::detectProvider($var->key),
            'description' => $var->description,
            'required' => (bool) $var->required,
            'updated_at' => $var->updated_at->toIso8601String(),
        ]);
    }

    public function setVariable(Request $request, VaultEngine $vault, AuditService $audit)
    {
        $request->validate([
            'project_slug' => 'required|string',
            'environment_slug' => 'nullable|string',
            'key' => 'required|string',
            'value' => 'required|string',
            'classification' => 'nullable|string|in:secret,config',
        ]);

        $user = $request->user();
        $workspace = $user->personalWorkspace();

        $project = Project::where('workspace_id', $workspace->id)
            ->where('slug', $request->input('project_slug'))
            ->firstOrFail();

        $envSlug = $request->input('environment_slug', 'production');
        $env = Environment::where('project_id', $project->id)->where('slug', $envSlug)->firstOrFail();

        $key = strtoupper(trim($request->input('key')));
        $classification = $request->input('classification') ?? ProviderRegistry::classifyKey($key);
        $provider = ProviderRegistry::detectProvider($key);

        $var = ProjectVariable::firstOrCreate(
            ['project_id' => $project->id, 'key' => $key],
            [
                'classification' => $classification,
                'provider_hint' => $provider,
                'created_by' => $user->id,
            ]
        );

        $binding = EnvironmentBinding::where('environment_id', $env->id)
            ->where('project_variable_id', $var->id)
            ->first();

        if ($binding) {
            $vaultEntry = $binding->vaultEntry;
        } else {
            $vaultEntry = VaultEntry::create([
                'workspace_id' => $workspace->id,
                'label' => "{$project->name} / {$key}",
                'classification' => $classification,
                'sharing_mode' => 'restricted',
                'created_by' => $user->id,
            ]);

            $binding = EnvironmentBinding::create([
                'environment_id' => $env->id,
                'project_variable_id' => $var->id,
                'vault_entry_id' => $vaultEntry->id,
                'created_by' => $user->id,
            ]);
        }

        $version = $vault->encryptSecret($workspace, $vaultEntry, $request->input('value'), $user);

        $audit->log($workspace, 'variable.set', $user, ProjectVariable::class, $var->id, $project->id, $env->id, ['key' => $key]);

        return response()->json([
            'status' => 'success',
            'key' => $key,
            'environment' => $env->slug,
            'version' => $version->id,
        ]);
    }

    public function getVariableValue(Request $request, string $projectSlug, string $key, VaultEngine $vault, AuditService $audit)
    {
        $user = $request->user();
        $workspace = $user->personalWorkspace();

        $project = Project::where('workspace_id', $workspace->id)
            ->where('slug', $projectSlug)
            ->firstOrFail();

        $envSlug = $request->query('env', 'production');
        $env = Environment::where('project_id', $project->id)->where('slug', $envSlug)->firstOrFail();

        $var = ProjectVariable::where('project_id', $project->id)
            ->where('key', strtoupper($key))
            ->firstOrFail();

        $binding = EnvironmentBinding::where('environment_id', $env->id)
            ->where('project_variable_id', $var->id)
            ->firstOrFail();

        if (! $binding->vaultEntry || ! $binding->vaultEntry->currentVersion) {
            return response()->json(['error' => 'Not configured'], 404);
        }

        $plaintext = $vault->decryptSecret($binding->vaultEntry->currentVersion, $workspace);

        $audit->log($workspace, 'secret.revealed', $user, ProjectVariable::class, $var->id, $project->id, $env->id, ['key' => $var->key]);

        return response()->json([
            'key' => $var->key,
            'value' => $plaintext,
            'environment' => $env->slug,
        ]);
    }

    public function template(Request $request, string $projectSlug)
    {
        $user = $request->user();
        $workspace = $user->personalWorkspace();

        $project = Project::where('workspace_id', $workspace->id)
            ->where('slug', $projectSlug)
            ->with('variables')
            ->firstOrFail();

        $content = $project->variables->map(fn ($v) => "{$v->key}=")->implode("\n");

        return response($content, 200)->header('Content-Type', 'text/plain');
    }

    public function diff(Request $request, string $projectSlug)
    {
        $user = $request->user();
        $workspace = $user->personalWorkspace();

        $project = Project::where('workspace_id', $workspace->id)
            ->where('slug', $projectSlug)
            ->with(['environments.bindings', 'variables'])
            ->firstOrFail();

        $envs = $project->environments;
        $matrix = [];

        foreach ($project->variables as $var) {
            $row = ['key' => $var->key];
            foreach ($envs as $env) {
                $binding = $env->bindings->firstWhere('project_variable_id', $var->id);
                $row[$env->slug] = (bool) ($binding && $binding->vaultEntry && $binding->vaultEntry->current_version_id);
            }
            $matrix[] = $row;
        }

        return response()->json([
            'project' => $project->name,
            'environments' => $envs->map(fn ($e) => $e->slug),
            'diff' => $matrix,
        ]);
    }
}
