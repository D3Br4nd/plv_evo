<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
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
        $projects = Project::with('assignee')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Admin/Projects/Board', [
            'projects' => $projects
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
        ]);

        $project = Project::create($validated);

        ActivityLog::create([
            'actor_user_id' => $request->user()?->id,
            'action' => 'created',
            'subject_type' => 'Project',
            'subject_id' => $project->id,
            'summary' => 'Creato task: '.$project->title,
        ]);
        
        return redirect()->back()->with('success', 'Task creato.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:todo,in_progress,done',
            // Allow updating other fields too if needed, but primarily status for DnD
        ]);

        $project->update($validated);

        ActivityLog::create([
            'actor_user_id' => $request->user()?->id,
            'action' => 'updated',
            'subject_type' => 'Project',
            'subject_id' => $project->id,
            'summary' => 'Aggiornato task: '.$project->title,
        ]);

        return redirect()->back(); // Inertia handles state preservation
    }
    
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        $summary = 'Eliminato task: '.$project->title;
        $project->delete();

        ActivityLog::create([
            'actor_user_id' => request()->user()?->id,
            'action' => 'deleted',
            'subject_type' => 'Project',
            'subject_id' => $project->id,
            'summary' => $summary,
        ]);

        return redirect()->back();
    }
}
