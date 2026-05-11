<?php

namespace App\Http\Controllers;

use App\Http\Requests\TagRequest;
use App\Models\SmsCampaign;
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
            ->orderBy(
                in_array($request->get('sort_by'), ['name', 'created_at', 'contacts_count'], true) ? $request->get('sort_by') : 'name',
                $request->get('sort_dir') === 'desc' ? 'desc' : 'asc',
            )
            ->get();

        return Inertia::render('Tags/Index', [
            'tags' => $tags,
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
            'colour' => $request->input('colour', $request->input('color')),
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
        $tag->loadCount('contacts');

        $contacts = $this->paginate(
            $tag->contacts()
                ->when($request->search, fn ($q, $search) => $q->search($search)),
            $request,
            25
        );

        $campaigns = SmsCampaign::whereJsonContains('target_filters->tag_ids', $tag->id)
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($campaign) => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'status' => $campaign->status,
                'total_recipients' => $campaign->total_recipients,
                'sent_count' => $campaign->sent_count,
                'failed_count' => $campaign->failed_count,
                'success_rate' => $campaign->success_rate,
                'target_type' => $campaign->target_type,
                'target_config' => $campaign->target_config,
                'sender_id' => $campaign->sender_id,
                'message_body' => $campaign->message_body,
                'template_id' => $campaign->template_id,
                'template_name' => $campaign->template_name,
                'skipped_count' => $campaign->skipped_count,
                'pending_count' => $campaign->pending_count,
                'scheduled_at' => $campaign->scheduled_at?->toISOString(),
                'started_at' => $campaign->started_at?->toISOString(),
                'completed_at' => $campaign->completed_at?->toISOString(),
                'created_by' => $campaign->created_by,
                'created_by_name' => $campaign->created_by_name,
                'notes' => $campaign->notes,
                'created_at' => $campaign->created_at?->toISOString(),
                'updated_at' => $campaign->updated_at?->toISOString(),
            ]);

        return Inertia::render('Tags/Show', [
            'tag' => $tag,
            'contacts' => $contacts,
            'campaigns' => $campaigns,
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
            'colour' => $request->input('colour', $request->input('color')),
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
