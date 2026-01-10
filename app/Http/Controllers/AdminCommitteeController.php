<?php

namespace App\Http\Controllers;

use App\Models\Committee;
use App\Models\CommitteePost;
use App\Models\User;
use App\Models\CommitteeUser;
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
        $committee = Committee::findOrFail($id);
        
        $committee->load([
            'members' => function ($query) {
                $query->select('users.id', 'users.name', 'users.email', 'users.avatar_path')
                    ->orderBy('committee_user.joined_at', 'desc');
            },
            'posts' => function($query) {
                $query->withCount('readers')->latest();
            },
            'posts.author:id,name,avatar_path'
        ]);

        // Get all members for the "add member" dropdown, excluding already attached members
        $today = now()->toDateString();
        $currentYear = now()->year;
        
        $availableMembers = User::whereNotIn('id', $committee->members->pluck('id'))
            ->where(function ($query) use ($today, $currentYear) {
                $query->whereDate('plv_expires_at', '>=', $today)
                    ->orWhereHas('memberships', function ($q) use ($currentYear) {
                        $q->where('year', $currentYear);
                    });
            })
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

        CommitteeUser::create([
            'committee_id' => $committee->id,
            'user_id' => $validated['user_id'],
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
        CommitteeUser::where('committee_id', $committeeId)
            ->where('user_id', $userId)
            ->first()?->delete();

        return back()->with('flash', [
            'type' => 'success',
            'message' => 'Socio rimosso dal comitato.',
        ]);
    }

    /**
     * Show the form for creating a new post.
     */
    public function createPost(string $committeeId)
    {
        $committee = Committee::findOrFail($committeeId);
        return Inertia::render('Admin/Committees/CreatePost', [
            'committee' => $committee,
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
            'featured_image' => ['nullable', 'image', 'max:2048'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $committee = Committee::findOrFail($committeeId);

        $post = new CommitteePost();
        $post->committee_id = $committee->id;
        $post->author_id = auth()->id();
        $post->title = $validated['title'];
        $post->content = $validated['content'];

        // Handle featured image upload
        if ($request->hasFile('featured_image')) {
            $image = $request->file('featured_image');
            $path = $image->store('committees/posts/images', 'public');
            $post->featured_image_path = $path;
        }

        // Handle attachment upload
        if ($request->hasFile('attachment')) {
            $attachment = $request->file('attachment');
            $post->attachment_name = $attachment->getClientOriginalName();
            $path = $attachment->store('committees/posts/attachments', 'public');
            $post->attachment_path = $path;
        }

        $post->save();

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

    public function editPost(string $committeeId, string $postId)
    {
        $committee = Committee::findOrFail($committeeId);
        $post = CommitteePost::where('committee_id', $committeeId)->findOrFail($postId);

        return Inertia::render('Admin/Committees/EditPost', [
            'committee' => $committee,
            'post' => $post,
        ]);
    }

    public function updatePost(Request $request, string $committeeId, string $postId)
    {
        $post = CommitteePost::where('committee_id', $committeeId)->findOrFail($postId);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'featured_image' => ['nullable', 'image', 'max:2048'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'remove_featured_image' => 'nullable|boolean',
            'remove_attachment' => 'nullable|boolean',
        ]);

        $post->title = $validated['title'];
        $post->content = $validated['content'];

        // Handle removals
        if ($request->boolean('remove_featured_image')) {
            if ($post->featured_image_path) {
                Storage::disk('public')->delete($post->featured_image_path);
                $post->featured_image_path = null;
            }
        }

        if ($request->boolean('remove_attachment')) {
            if ($post->attachment_path) {
                Storage::disk('public')->delete($post->attachment_path);
                $post->attachment_path = null;
                $post->attachment_name = null;
            }
        }

        // Handle featured image upload
        if ($request->hasFile('featured_image')) {
            // Delete old image if exists
            if ($post->featured_image_path) {
                Storage::disk('public')->delete($post->featured_image_path);
            }
            $image = $request->file('featured_image');
            $path = $image->store('committees/posts/images', 'public');
            $post->featured_image_path = $path;
        }

        // Handle attachment upload
        if ($request->hasFile('attachment')) {
            // Delete old attachment if exists
            if ($post->attachment_path) {
                Storage::disk('public')->delete($post->attachment_path);
            }
            $attachment = $request->file('attachment');
            $post->attachment_name = $attachment->getClientOriginalName();
            $path = $attachment->store('committees/posts/attachments', 'public');
            $post->attachment_path = $path;
        }

        $post->save();

        return to_route('committees.show', $committeeId)->with('flash', [
            'type' => 'success',
            'message' => 'Post aggiornato con successo.',
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

        // Delete associated database notifications
        \DB::table('notifications')
            ->where('type', 'App\\Notifications\\NewCommitteePost')
            ->whereRaw("(data::jsonb)->>'post_id' = ?", [$postId])
            ->delete();

        // Delete associated files if any
        if ($post->featured_image_path) {
            \Storage::disk('public')->delete($post->featured_image_path);
        }
        if ($post->attachment_path) {
            \Storage::disk('public')->delete($post->attachment_path);
        }

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


        return back()->with('flash', [
            'type' => 'success',
            'message' => 'Immagine eliminata con successo.',
        ]);
    }
}
