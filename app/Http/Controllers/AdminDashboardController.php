<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\ActivityLog;
use App\Models\Membership;
use App\Models\Project;
use App\Models\User;
use App\Models\Committee;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $year = now()->year;
        $totalMembers = User::count();

        // Committee colors (HSL-based for variety)
        $committeeColors = [
            'hsl(210, 100%, 50%)',  // Blue
            'hsl(160, 100%, 40%)',  // Teal
            'hsl(30, 100%, 50%)',   // Orange
            'hsl(280, 100%, 50%)',  // Purple
            'hsl(340, 100%, 50%)',  // Pink
            'hsl(120, 60%, 45%)',   // Green
        ];

        $committees = Committee::withCount('members')->get();
        $committeeStats = $committees->map(function($c, $index) use ($committeeColors, $totalMembers) {
            return [
                'name' => $c->name,
                'count' => $c->members_count,
                'total' => $totalMembers,
                'percentage' => $totalMembers > 0 ? round(($c->members_count / $totalMembers) * 100, 1) : 0,
                'color' => $committeeColors[$index % count($committeeColors)],
            ];
        });

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'membersTotal' => $totalMembers,
                'membersActive' => Membership::where('year', $year)->where('status', 'active')->count(),
                
                'eventsTotal' => Event::whereYear('start_date', $year)->count(),
                'eventsUpcoming' => Event::where('start_date', '>=', now())->whereYear('start_date', $year)->count(),
                
                'projectsTotal' => Project::count(),
                'projectsTodo' => Project::where('status', 'todo')->count(),
                'projectsInProgress' => Project::where('status', 'in_progress')->count(),
                'projectsDone' => Project::where('status', 'done')->count(),
                
                'contentPagesTotal' => \App\Models\ContentPage::count(),
                
                'committeeStats' => $committeeStats,
                
                'notificationStats' => [
                    'sent' => DB::table('notifications')
                        ->whereYear('created_at', $year)
                        ->whereRaw("(data::jsonb)->>'type' = ?", ['broadcast'])
                        ->count(),
                    'read' => DB::table('notifications')
                        ->whereYear('created_at', $year)
                        ->whereRaw("(data::jsonb)->>'type' = ?", ['broadcast'])
                        ->whereNotNull('read_at')
                        ->count(),
                ],
            ],
            'activity' => ActivityLog::query()
                ->with('actor:id,name')
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'recentActivity' => ActivityLog::query()
                ->where('created_at', '>=', now()->subDays(7))
                ->latest()
                ->get(),
        ]);
    }
    
    /**
     * Clear activity logs older than a specified date.
     * Only accessible by super admins.
     */
    public function clearActivityLogs(Request $request)
    {
        $validated = $request->validate([
            'before_date' => 'required|date',
        ]);
        
        $deletedCount = ActivityLog::where('created_at', '<', $validated['before_date'])->delete();
        
        return redirect()
            ->back()
            ->with('success', "Eliminate {$deletedCount} attività precedenti al " . \Carbon\Carbon::parse($validated['before_date'])->format('d/m/Y'));
    }
}


