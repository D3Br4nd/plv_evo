<?php

namespace App\Http\Controllers;

use App\Models\BroadcastNotification;
use App\Models\User;
use App\Notifications\NewBroadcastNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AdminBroadcastController extends Controller
{
    /**
     * Display a listing of broadcast notifications.
     */
    public function index()
    {
        $broadcasts = BroadcastNotification::with('createdBy:id,name')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Admin/Broadcasts/Index', [
            'broadcasts' => $broadcasts,
        ]);
    }

    /**
     * Show the form for creating a new broadcast notification.
     */
    public function create()
    {
        $activeMembers = User::where('membership_status', 'active')->count();

        return Inertia::render('Admin/Broadcasts/Create', [
            'activeMembersCount' => $activeMembers,
        ]);
    }

    /**
     * Store a newly created broadcast notification and send to all active members.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'featured_image' => ['nullable', 'image', 'max:2048'], // 2MB
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'], // 10MB
        ]);

        $broadcast = new BroadcastNotification();
        $broadcast->title = $validated['title'];
        $broadcast->content = $validated['content'];
        $broadcast->created_by_user_id = auth()->id();

        // Handle featured image upload
        if ($request->hasFile('featured_image')) {
            $image = $request->file('featured_image');
            $path = $image->store('broadcasts/images', 'public');
            $broadcast->featured_image_path = $path;
        }

        // Handle attachment upload
        if ($request->hasFile('attachment')) {
            $attachment = $request->file('attachment');
            $broadcast->attachment_name = $attachment->getClientOriginalName();
            $path = $attachment->store('broadcasts/attachments', 'public');
            $broadcast->attachment_path = $path;
        }

        $broadcast->save();

        // Send notification to all active members
        $activeMembers = User::where('membership_status', 'active')->get();
        
        \Log::info('Sending broadcast notification', [
            'broadcast_id' => $broadcast->id,
            'title' => $broadcast->title,
            'recipients_count' => $activeMembers->count(),
        ]);

        Notification::send($activeMembers, new NewBroadcastNotification($broadcast));

        // Mark as sent
        $broadcast->update(['sent_at' => now()]);


        return redirect()->route('broadcasts.index')->with('flash', [
            'type' => 'success',
            'message' => "Notifica inviata a {$activeMembers->count()} soci attivi.",
        ]);
    }

    /**
     * Display the specified broadcast notification.
     */
    public function show(string $id)
    {
        $broadcast = BroadcastNotification::with('createdBy:id,name')
            ->findOrFail($id);

        return Inertia::render('Admin/Broadcasts/Show', [
            'broadcast' => $broadcast,
        ]);
    }

    /**
     * Remove the specified broadcast notification.
     */
    public function destroy(string $id)
    {
        $broadcast = BroadcastNotification::findOrFail($id);

        // Delete associated files
        if ($broadcast->featured_image_path) {
            Storage::disk('public')->delete($broadcast->featured_image_path);
        }
        if ($broadcast->attachment_path) {
            Storage::disk('public')->delete($broadcast->attachment_path);
        }

        $broadcast->delete();

        return redirect()->route('broadcasts.index')->with('flash', [
            'type' => 'success',
            'message' => 'Notifica eliminata.',
        ]);
    }
}
