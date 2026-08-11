<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WorkspaceController extends Controller
{
    public function switch(Request $request)
    {
        $request->validate([
            'workspace_id' => 'required|exists:workspaces,id',
        ]);

        $user = auth()->user();
        if ($user->switchWorkspace($request->input('workspace_id'))) {
            session()->flash('message', 'Switched workspace.');
        }

        return redirect()->back();
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user = auth()->user();

        $workspace = Workspace::create([
            'name' => $request->input('name'),
            'slug' => Str::slug($request->input('name').'-'.Str::random(4)),
            'personal' => false,
            'created_by' => $user->id,
        ]);

        WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);

        $user->switchWorkspace($workspace->id);

        session()->flash('message', "Workspace '{$workspace->name}' created and activated!");

        return redirect()->back();
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user = auth()->user();
        $workspace = Workspace::where('id', $id)
            ->where('created_by', $user->id)
            ->firstOrFail();

        $workspace->update([
            'name' => $request->input('name'),
        ]);

        session()->flash('message', "Workspace renamed to '{$workspace->name}'.");

        return redirect()->back();
    }

    public function destroy(Request $request, string $id)
    {
        $user = auth()->user();
        $workspace = Workspace::where('id', $id)
            ->where('created_by', $user->id)
            ->where('personal', false)
            ->firstOrFail();

        $name = $workspace->name;
        $workspace->delete();

        session()->forget('current_workspace_id');

        session()->flash('message', "Workspace '{$name}' deleted.");

        return redirect()->route('dashboard');
    }
}
