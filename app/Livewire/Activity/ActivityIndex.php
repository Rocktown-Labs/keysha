<?php

namespace App\Livewire\Activity;

use App\Models\AuditEvent;
use Livewire\Component;

class ActivityIndex extends Component
{
    public string $search = '';

    public function render()
    {
        $user = auth()->user();
        $workspace = $user->personalWorkspace();

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
