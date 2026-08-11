<?php

namespace App\Livewire\Activity;

use App\Models\AuditEvent;
use Livewire\Component;

class ActivityIndex extends Component
{
    public string $search = '';

    public function exportCsv()
    {
        $user = auth()->user();
        $workspace = $user->currentWorkspace();

        $events = AuditEvent::where('workspace_id', $workspace->id)
            ->with(['actor', 'project', 'environment'])
            ->latest()
            ->get();

        $csv = "Timestamp,Event,Actor,Project,Environment\n";
        foreach ($events as $e) {
            $actor = $e->actor->name ?? 'System';
            $project = $e->project->name ?? '-';
            $env = $e->environment->name ?? '-';
            $csv .= "\"{$e->created_at}\",\"{$e->event}\",\"{$actor}\",\"{$project}\",\"{$env}\"\n";
        }

        return response()->streamDownload(fn () => print ($csv), 'keysha-audit-log-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function render()
    {
        $user = auth()->user();
        $workspace = $user->currentWorkspace();

        $events = AuditEvent::where('workspace_id', $workspace->id)
            ->with(['actor', 'project', 'environment'])
            ->when($this->search, fn ($q) => $q->where('event', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate(25);

        return view('livewire.activity.activity-index', [
            'events' => $events,
        ])->layout('layouts.app', ['title' => 'Activity Audit Log']);
    }
}
