<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\ActivityLog;
use App\Models\Membership;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

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
            ],
            'activity' => ActivityLog::query()
                ->with('actor:id,name')
                ->latest()
                ->paginate(20)
                ->withQueryString(),
        ]);
    }
}


