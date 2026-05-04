<?php

namespace App\Http\Controllers;

use App\Http\Requests\TagRequest;
use App\Models\Tag;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TagController extends Controller
{
    public function index(Request $request): Response
    {
        $tags = Tag::withCount('contacts')
            ->when($request->search, fn ($q, $search) => $q->search($search))
            ->orderBy($request->get('sort_by', 'name'), $request->get('sort_dir', 'asc'))
            ->paginate($request->get('per_page', 25));

        return Inertia::render('Tags/Index', [
            'tags' => $tags,
            'filters' => $request->only(['search', 'sort_by', 'sort_dir', 'per_page']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Tags/Create');
    }

    public function store(TagRequest $request)
    {
        $tag = Tag::create([
            'name' => $request->name,
            'colour' => $request->colour,
            'description' => $request->description,
            'created_by' => $request->user()->id,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['tag' => $tag], 201);
        }

        return redirect()->route('tags.index')
            ->with('success', 'Tag created successfully.');
    }

    public function show(Tag $tag, Request $request): Response
    {
        $contacts = $tag->contacts()
            ->when($request->search, fn ($q, $search) => $q->search($search))
            ->paginate($request->get('per_page', 25));

        return Inertia::render('Tags/Show', [
            'tag' => $tag,
            'contacts' => $contacts,
        ]);
    }

    public function edit(Tag $tag): Response
    {
        return Inertia::render('Tags/Edit', [
            'tag' => $tag,
        ]);
    }

    public function update(TagRequest $request, Tag $tag)
    {
        $tag->update([
            'name' => $request->name,
            'colour' => $request->colour,
            'description' => $request->description,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['tag' => $tag->fresh()]);
        }

        return redirect()->route('tags.index')
            ->with('success', 'Tag updated successfully.');
    }

    public function destroy(Request $request, Tag $tag)
    {
        $tag->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Tag deleted successfully.']);
        }

        return redirect()->route('tags.index')
            ->with('success', 'Tag deleted successfully.');
    }
}
