<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventCheckin;
use App\Models\Membership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminEventCheckinController extends Controller
{
    public function index(Event $event)
    {
        $checkins = EventCheckin::query()
            ->where('event_id', $event->id)
            ->with(['membership.user', 'checkedInBy'])
            ->orderByDesc('checked_in_at')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Admin/Events/Checkins', [
            'event' => $event,
            'checkins' => $checkins,
        ]);
    }

    public function store(Request $request, Event $event)
    {
        $validated = $request->validate([
            'qr_code' => 'required|uuid',
            'role' => 'nullable|string|max:255',
        ]);

        $eventYear = (int) $event->start_date->format('Y');

        $user = \App\Models\User::find($validated['qr_code']);

        if (! $user) {
            return redirect()->back()->with('error', 'Socio non trovato.');
        }

        $membership = $user->memberships()
            ->where('year', $eventYear)
            ->first();

        if (! $membership) {
            return redirect()->back()->with('error', 'Tessera non valida per questo anno ('.$eventYear.').');
        }

        if ($membership->status !== 'active') {
            return redirect()->back()->with('error', 'Tessera non attiva.');
        }

        $already = EventCheckin::query()
            ->where('event_id', $event->id)
            ->where('membership_id', $membership->id)
            ->exists();

        if ($already) {
            return redirect()->back()->with('error', 'Check-in già effettuato.');
        }

        DB::transaction(function () use ($event, $membership, $request, $validated) {
            EventCheckin::create([
                'event_id' => $event->id,
                'membership_id' => $membership->id,
                'role' => $validated['role'] ?? null,
                'checked_in_by_user_id' => $request->user()->id,
                'checked_in_at' => now(),
                'metadata' => [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
            ]);
        });

        return redirect()->back()->with('success', 'Check-in registrato: '.$membership->user->name);
    }

    public function update(Request $request, Event $event, EventCheckin $checkin)
    {
        $validated = $request->validate([
            'role' => 'nullable|string|max:255',
        ]);

        $checkin->update($validated);

        return redirect()->back()->with('success', 'Ruolo aggiornato.');
    }

    public function destroy(Event $event, EventCheckin $checkin)
    {
        $checkin->delete();

        return redirect()->back()->with('success', 'Check-in rimosso.');
    }

    public function exportCsv(Event $event)
    {
        $rows = EventCheckin::query()
            ->where('event_id', $event->id)
            ->with(['membership.user', 'checkedInBy'])
            ->orderBy('checked_in_at')
            ->get()
            ->map(function ($c) {
                return [
                    'checked_in_at' => optional($c->checked_in_at)->toDateTimeString(),
                    'member_name' => $c->membership?->user?->name,
                    'member_email' => $c->membership?->user?->email,
                    'role' => $c->role,
                    'membership_year' => $c->membership?->year,
                    'checked_in_by' => $c->checkedInBy?->name,
                ];
            });

        $filename = 'checkins-'.$event->id.'.csv';

        $handle = fopen('php://temp', 'w+');
        fputcsv($handle, ['checked_in_at', 'member_name', 'member_email', 'role', 'membership_year', 'checked_in_by']);
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}


