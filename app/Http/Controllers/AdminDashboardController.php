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

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'membersTotal' => User::count(),
                'membersActive' => Membership::where('year', $year)->where('status', 'active')->count(),
                'eventsTotal' => Event::count(),
                'eventsUpcoming' => Event::where('start_date', '>=', now())->count(),
                'projectsTotal' => Project::count(),
                'projectsDone' => Project::where('status', 'done')->count(),
                'committeeStats' => Committee::withCount('members')->get()->map(fn($c) => [
                    'name' => $c->name,
                    'count' => $c->members_count,
                ]),
                'notificationStats' => [
                    'sent' => DB::table('notifications')->whereYear('created_at', $year)->count(),
                    'read' => DB::table('notifications')->whereYear('created_at', $year)->whereNotNull('read_at')->count(),
                ],
            ],
            'activity' => ActivityLog::query()
                ->with('actor:id,name')
                ->latest()
                ->paginate(20)
                ->withQueryString(),
        ]);
    }
}


