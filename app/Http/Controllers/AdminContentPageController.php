<?php

namespace App\Http\Controllers;

use App\Models\ContentPage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AdminContentPageController extends Controller
{
    public function index()
    {
        $pages = ContentPage::query()
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/ContentPages/Index', [
            'pages' => $pages,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/ContentPages/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'excerpt' => 'nullable|string|max:500',
            'body' => 'required|string',
            'status' => 'required|string|in:draft,published',
        ]);

        $slug = $validated['slug'] ?: Str::slug($validated['title']);

        $page = ContentPage::create([
            'title' => $validated['title'],
            'slug' => $slug,
            'excerpt' => $validated['excerpt'] ?? null,
            'body' => $validated['body'],
            'status' => $validated['status'],
            'published_at' => $validated['status'] === 'published' ? now() : null,
            'created_by_user_id' => $request->user()->id,
            'updated_by_user_id' => $request->user()->id,
        ]);


        if ($page->status === 'published') {
            $notification = new \App\Notifications\NewContentPageNotification($page);
            // Notify all users? Or just admins for now?
            // The user wants social/members notified too.
            $users = \App\Models\User::all();
            \Illuminate\Support\Facades\Notification::send($users, $notification);
        }

        return redirect()->route('content-pages.index')->with('success', 'Pagina creata e notifiche inviate.');
    }

    public function edit(ContentPage $content_page)
    {
        return Inertia::render('Admin/ContentPages/Edit', [
            'page' => $content_page,
        ]);
    }

    public function update(Request $request, ContentPage $content_page)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:content_pages,slug,'.$content_page->id.',id',
            'excerpt' => 'nullable|string|max:500',
            'body' => 'required|string',
            'status' => 'required|string|in:draft,published',
        ]);

        $content_page->update([
            'title' => $validated['title'],
            'slug' => $validated['slug'],
            'excerpt' => $validated['excerpt'] ?? null,
            'body' => $validated['body'],
            'status' => $validated['status'],
            'published_at' => $validated['status'] === 'published'
                ? ($content_page->published_at ?? now())
                : null,
            'updated_by_user_id' => $request->user()->id,
        ]);


        $wasPublished = $content_page->wasChanged('status') && $content_page->status === 'published';

        if ($wasPublished) {
            $notification = new \App\Notifications\NewContentPageNotification($content_page);
            $users = \App\Models\User::all();
            \Illuminate\Support\Facades\Notification::send($users, $notification);
        }

        return redirect()->route('content-pages.index')->with('success', 'Pagina aggiornata.');
    }

    public function destroy(ContentPage $content_page)
    {
        $summary = 'Eliminata pagina contenuto: '.$content_page->title;
        $content_page->delete();


        return redirect()->back()->with('success', 'Pagina eliminata.');
    }
}


