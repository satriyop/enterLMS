<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tag\StoreTagRequest;
use App\Http\Requests\Tag\UpdateTagRequest;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TagController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Tag::class);

        $query = Tag::query()
            ->withCount('courses');

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $tags = $query->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/tags/Index', [
            'tags' => $tags,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Tag::class);

        return Inertia::render('admin/tags/Edit', [
            'tag' => null,
        ]);
    }

    public function store(StoreTagRequest $request): RedirectResponse
    {
        Tag::create($request->validated());

        return redirect()
            ->route('admin.tags.index')
            ->with('success', 'Tag berhasil ditambahkan.');
    }

    public function edit(Tag $tag): Response
    {
        Gate::authorize('update', $tag);

        return Inertia::render('admin/tags/Edit', [
            'tag' => $tag,
        ]);
    }

    public function update(UpdateTagRequest $request, Tag $tag): RedirectResponse
    {
        $tag->update($request->validated());

        return redirect()
            ->route('admin.tags.index')
            ->with('success', 'Tag berhasil diperbarui.');
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        Gate::authorize('delete', $tag);

        if ($tag->courses()->exists()) {
            return redirect()
                ->route('admin.tags.index')
                ->with('error', 'Tag tidak dapat dihapus karena masih digunakan oleh kursus.');
        }

        $tag->delete();

        return redirect()
            ->route('admin.tags.index')
            ->with('success', 'Tag berhasil dihapus.');
    }
}
