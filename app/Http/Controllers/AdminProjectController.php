<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $projects = Project::with(['assignee', 'members', 'committee'])
            ->orderBy('created_at', 'desc')
            ->get();

        $users = \App\Models\User::orderBy('name')->get();
        $committees = \App\Models\Committee::orderBy('name')->get();

        return Inertia::render('Admin/Projects/Board', [
            'projects' => $projects,
            'users' => $users,
            'committees' => $committees
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'status' => 'required|string|in:todo,in_progress,done',
            'priority' => 'required|string|in:low,medium,high',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'deadline' => 'nullable|date',
            'committee_id' => 'nullable|exists:committees,id',
            'members' => 'nullable|array',
            'members.*' => 'exists:users,id',
        ]);

        $project = Project::create($validated);

        if (!empty($validated['members'])) {
            $project->members()->sync($validated['members']);
        }

        // Notify Admins and Assigned Members
        $admins = \App\Models\User::where('role', \App\Enums\UserRole::Admin->value)
            ->orWhere('role', \App\Enums\UserRole::SuperAdmin->value)
            ->get();
            
        $membersToNotify = $project->members;
        $usersToNotify = $admins->merge($membersToNotify)->unique('id');

        $notification = new \App\Notifications\ProjectUpdateNotification($project, null, true);
        
        \Log::info('Sending project creation notification', [
            'project_id' => $project->id,
            'project_title' => $project->title,
            'users_to_notify_count' => $usersToNotify->count(),
            'user_ids' => $usersToNotify->pluck('id')->toArray(),
        ]);
        
        foreach ($usersToNotify as $user) {
            $user->notify($notification);
        }

        return redirect()->back()->with('success', 'Progetto creato e notifiche inviate.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project)
    {
        $oldStatus = $project->status;

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'status' => 'sometimes|required|string|in:todo,in_progress,done',
            'priority' => 'sometimes|required|string|in:low,medium,high',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'deadline' => 'nullable|date',
            'committee_id' => 'nullable|exists:committees,id',
            'members' => 'nullable|array',
            'members.*' => 'exists:users,id',
        ]);

        $project->update($validated);

        if ($request->has('members')) {
            $project->members()->sync($validated['members'] ?? []);
        }

        // Notify on status change
        if ($oldStatus !== $project->status) {
            $notification = new \App\Notifications\ProjectUpdateNotification($project, $oldStatus, false);
            
            $admins = \App\Models\User::where('role', \App\Enums\UserRole::Admin->value)
                ->orWhere('role', \App\Enums\UserRole::SuperAdmin->value)
                ->get();
            
            $membersToNotify = $project->members;
            $usersToNotify = $admins->merge($membersToNotify)->unique('id');
            
            \Log::info('Sending project update notification', [
                'project_id' => $project->id,
                'project_title' => $project->title,
                'old_status' => $oldStatus,
                'new_status' => $project->status,
                'users_to_notify_count' => $usersToNotify->count(),
                'user_ids' => $usersToNotify->pluck('id')->toArray(),
            ]);
            
            foreach ($usersToNotify as $user) {
                $user->notify($notification);
            }
        }

        return redirect()->back();
    }
    
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        $project->delete();
        return redirect()->back();
    }
}
