<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class AdminEventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);

        $date = Carbon::createFromDate($year, $month, 1);
        $start = $date->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $end = $date->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $events = Event::whereBetween('start_date', [$start, $end])
            ->orWhereBetween('end_date', [$start, $end])
            ->orderBy('start_date')
            ->get();

        return Inertia::render('Admin/Events/Calendar', [
            'events' => $events,
            'currentDate' => $date->format('Y-m-d'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'required|string|in:fair,festival,meeting',
            'metadata' => 'nullable|array',
        ]);

        $event = Event::create($validated);

        ActivityLog::create([
            'actor_user_id' => $request->user()?->id,
            'action' => 'created',
            'subject_type' => 'Event',
            'subject_id' => $event->id,
            'summary' => 'Creato evento: '.$event->title,
        ]);

        return redirect()->back()->with('success', 'Evento creato con successo.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Event $event)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'required|string|in:fair,festival,meeting',
            'metadata' => 'nullable|array',
        ]);

        $event->update($validated);

        ActivityLog::create([
            'actor_user_id' => $request->user()?->id,
            'action' => 'updated',
            'subject_type' => 'Event',
            'subject_id' => $event->id,
            'summary' => 'Aggiornato evento: '.$event->title,
        ]);

        return redirect()->back()->with('success', 'Evento aggiornato con successo.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Event $event)
    {
        $summary = 'Eliminato evento: '.$event->title;
        $event->delete();

        ActivityLog::create([
            'actor_user_id' => request()->user()?->id,
            'action' => 'deleted',
            'subject_type' => 'Event',
            'subject_id' => $event->id,
            'summary' => $summary,
        ]);

        return redirect()->back()->with('success', 'Evento eliminato con successo.');
    }
}
