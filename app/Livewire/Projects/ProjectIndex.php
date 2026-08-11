<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Models\SystemRecovery;
use App\Services\AuditService;
use Illuminate\Support\Str;
use Livewire\Component;

class ProjectIndex extends Component
{
    public string $name = '';

    public string $description = '';

    public bool $showCreateModal = false;

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string|max:1000',
    ];

    public function createProject(AuditService $audit)
    {
        $this->validate();

        $user = auth()->user();
        $workspace = $user->currentWorkspace();

        $slug = Str::slug($this->name);
        $count = Project::where('workspace_id', $workspace->id)->where('slug', 'like', "{$slug}%")->count();
        if ($count > 0) {
            $slug = "{$slug}-".($count + 1);
        }

        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => $this->name,
            'slug' => $slug,
            'description' => $this->description,
            'created_by' => $user->id,
        ]);

        // Default environments per Section 10
        $project->environments()->createMany([
            ['name' => 'Development', 'slug' => 'development', 'position' => 1, 'protected' => false],
            ['name' => 'Preview', 'slug' => 'preview', 'position' => 2, 'protected' => false],
            ['name' => 'Production', 'slug' => 'production', 'position' => 3, 'protected' => true],
        ]);

        $audit->log(
            workspace: $workspace,
            event: 'project.created',
            actor: $user,
            subjectType: Project::class,
            subjectId: $project->id,
            projectId: $project->id,
            metadata: ['name' => $project->name, 'slug' => $project->slug]
        );

        $this->reset(['name', 'description', 'showCreateModal']);
        session()->flash('message', "Project '{$project->name}' created.");
    }

    public function render()
    {
        $user = auth()->user();
        $workspace = $user->currentWorkspace();

        $projects = Project::where('workspace_id', $workspace->id)
            ->with(['environments.bindings', 'variables'])
            ->latest()
            ->get();

        return view('livewire.projects.project-index', [
            'workspace' => $workspace,
            'projects' => $projects,
            'hasRecoverySetup' => SystemRecovery::exists(),
        ])->layout('layouts.app', ['title' => 'Projects']);
    }
}
