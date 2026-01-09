<?php

namespace App\Http\Controllers;

use App\Models\Committee;
use App\Models\CommitteePost;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AdminCommitteeController extends Controller
{
    /**
     * Display a listing of committees.
     */
    public function index()
    {
        $committees = Committee::withCount('members', 'posts')
            ->with('creator:id,name')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Admin/Committees/Index', [
            'committees' => $committees,
        ]);
    }

    /**
     * Store a newly created committee.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        $committee = Committee::create([
            ...$validated,
            'status' => $validated['status'] ?? 'active',
            'created_by_user_id' => auth()->id(),
        ]);

        return redirect()->route('committees.show', $committee->id)
            ->with('flash', [
                'type' => 'success',
                'message' => 'Comitato creato con successo.',
            ]);
    }

    /**
     * Display the specified committee.
     */
    public function show(string $id)
    {
        $committee = Committee::with([
            'members' => function ($query) {
                $query->select('users.id', 'users.name', 'users.email', 'users.avatar_path')
                    ->orderBy('committee_user.joined_at', 'desc');
            },
            'posts.author:id,name,avatar_path',
        ])->findOrFail($id);

        // Get all members for the "add member" dropdown, excluding already attached members
        $availableMembers = User::whereNotIn('id', $committee->members->pluck('id'))
            ->where('membership_status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return Inertia::render('Admin/Committees/Show', [
            'committee' => $committee,
            'availableMembers' => $availableMembers,
        ]);
    }

    /**
     * Update the specified committee.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        $committee = Committee::findOrFail($id);
        $committee->update($validated);

        return back()->with('flash', [
            'type' => 'success',
            'message' => 'Comitato aggiornato con successo.',
        ]);
    }

    /**
     * Remove the specified committee.
     */
    public function destroy(string $id)
    {
        $committee = Committee::findOrFail($id);
        $committee->delete();

        return redirect()->route('committees.index')
            ->with('flash', [
                'type' => 'success',
                'message' => 'Comitato eliminato con successo.',
            ]);
    }

    /**
     * Attach a member to a committee.
     */
    public function attachMember(Request $request, string $committeeId)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'nullable|string|max:255',
        ]);

        $committee = Committee::findOrFail($committeeId);

        $committee->members()->attach($validated['user_id'], [
            'role' => $validated['role'] ?? null,
            'joined_at' => now(),
        ]);

        return back()->with('flash', [
            'type' => 'success',
            'message' => 'Socio aggiunto al comitato.',
        ]);
    }

    /**
     * Detach a member from a committee.
     */
    public function detachMember(string $committeeId, string $userId)
    {
        $committee = Committee::findOrFail($committeeId);
        $committee->members()->detach($userId);

        return back()->with('flash', [
            'type' => 'success',
            'message' => 'Socio rimosso dal comitato.',
        ]);
    }

    /**
     * Store a new post in a committee (admin only).
     */
    public function storePost(Request $request, string $committeeId)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $committee = Committee::findOrFail($committeeId);

        $post = CommitteePost::create([
            'committee_id' => $committee->id,
            'author_id' => auth()->id(),
            'title' => $validated['title'],
            'content' => $validated['content'],
        ]);

        // Load relations needed for the notification
        $post->load(['committee', 'author']);

        // Notify all committee members except the author
        $members = $committee->members()->where('users.id', '!=', auth()->id())->get();
        
        \Log::info('Sending committee post notification', [
            'post_id' => $post->id,
            'committee_id' => $committee->id,
            'committee_name' => $post->committee->name,
            'author_name' => $post->author->name,
            'members_count' => $members->count(),
            'member_ids' => $members->pluck('id')->toArray(),
        ]);
        
        \Illuminate\Support\Facades\Notification::send($members, new \App\Notifications\NewCommitteePost($post));

        return back()->with('flash', [
            'type' => 'success',
            'message' => 'Post pubblicato nella bacheca.',
        ]);
    }

    /**
     * Remove a post from a committee (admin only).
     */
    public function destroyPost(string $committeeId, string $postId)
    {
        $post = CommitteePost::where('committee_id', $committeeId)
            ->where('id', $postId)
            ->firstOrFail();

        $post->delete();

        return back()->with('flash', [
            'type' => 'success',
            'message' => 'Post eliminato dalla bacheca.',
        ]);
    }

    /**
     * Upload or update committee image.
     */
    public function updateCommitteeImage(Request $request, string $id)
    {
        $validated = $request->validate([
            'image' => ['required', 'image', 'max:2048'], // 2MB
        ]);

        $committee = Committee::findOrFail($id);

        // Delete old image (if any)
        if ($committee->image_path) {
            Storage::disk('public')->delete($committee->image_path);
        }

        $dir = 'committees/'.$committee->id;
        $ext = $validated['image']->extension() ?: 'jpg';
        $path = $validated['image']->storePubliclyAs($dir, 'image.'.$ext, 'public');

        $committee->update(['image_path' => $path]);

        ActivityLog::create([
            'actor_user_id' => $request->user()?->id,
            'action' => 'updated',
            'subject_type' => Committee::class,
            'subject_id' => $committee->id,
            'summary' => 'Immagine comitato aggiornata',
        ]);

        return back()->with('flash', [
            'type' => 'success',
            'message' => 'Immagine caricata con successo.',
        ]);
    }

    /**
     * Delete committee image.
     */
    public function destroyCommitteeImage(string $id)
    {
        $committee = Committee::findOrFail($id);

        if ($committee->image_path) {
            Storage::disk('public')->delete($committee->image_path);
        }

        $committee->update(['image_path' => null]);

        ActivityLog::create([
            'actor_user_id' => request()->user()?->id,
            'action' => 'updated',
            'subject_type' => Committee::class,
            'subject_id' => $committee->id,
            'summary' => 'Immagine comitato rimossa',
        ]);

        return back()->with('flash', [
            'type' => 'success',
            'message' => 'Immagine eliminata con successo.',
        ]);
    }
}
