<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MemberProjectController extends Controller
{
    public function index(Request $request)
    {
        $projects = $request->user()->projects()
            ->with(['committee'])
            ->orderBy('deadline', 'asc')
            ->get();

        return Inertia::render('Member/Projects/Index', [
            'projects' => $projects,
        ]);
    }

    public function show(Project $project)
    {
        // Ensure user is member of the project, or is an admin
        $user = auth()->user();
        $userRole = $user->role instanceof \UnitEnum ? $user->role->value : $user->role;
        $isMember = $project->members()->where('users.id', $user->id)->exists();
        $isAdmin = in_array($userRole, ['admin', 'super_admin'], true);

        if (!$isMember && !$isAdmin) {
            abort(403);
        }

        $project->load(['committee', 'members']);

        return Inertia::render('Member/Projects/Show', [
            'project' => $project,
        ]);
    }

    public function update(Request $request, Project $project)
    {
        // Ensure user is member of the project, or is an admin
        $user = auth()->user();
        $userRole = $user->role instanceof \UnitEnum ? $user->role->value : $user->role;
        $isMember = $project->members()->where('users.id', $user->id)->exists();
        $isAdmin = in_array($userRole, ['admin', 'super_admin'], true);

        if (!$isMember && !$isAdmin) {
            abort(403);
        }

        $oldStatus = $project->status;

        $validated = $request->validate([
            'status' => 'required|in:todo,in_progress,done',
        ]);

        $project->update([
            'status' => $validated['status'],
        ]);

        // Notify on status change (same logic as AdminProjectController)
        if ($oldStatus !== $project->status) {
            $notification = new \App\Notifications\ProjectUpdateNotification($project, $oldStatus, false);
            
            $admins = \App\Models\User::where('role', \App\Enums\UserRole::Admin->value)
                ->orWhere('role', \App\Enums\UserRole::SuperAdmin->value)
                ->get();
            
            $membersToNotify = $project->members;
            
            // Exclude the current user from notifications (they already know they changed it)
            $usersToNotify = $admins->merge($membersToNotify)
                ->unique('id')
                ->reject(fn($user) => $user->id === auth()->id());
            
            \Log::info('Sending project update notification (from member)', [
                'project_id' => $project->id,
                'project_title' => $project->title,
                'old_status' => $oldStatus,
                'new_status' => $project->status,
                'updated_by' => auth()->user()->name,
                'users_to_notify_count' => $usersToNotify->count(),
                'user_ids' => $usersToNotify->pluck('id')->toArray(),
            ]);
            
            foreach ($usersToNotify as $user) {
                $user->notify($notification);
            }
        }

        return back()->with('success', 'Stato aggiornato e notifiche inviate.');
    }
}
