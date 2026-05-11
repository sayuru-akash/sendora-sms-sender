<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\SavedSegment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SavedSegmentController extends Controller
{
    public function index(Request $request): Response
    {
        $segments = $this->paginate(
            SavedSegment::with('creator')
                ->when($request->search, fn ($q, $search) => $q->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($search).'%']))
                ->latest(),
            $request,
            25
        );

        return Inertia::render('Segments/Index', [
            'segments' => $segments,
            'filters' => $request->only(['search', 'per_page']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Segments/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'filters' => ['required', 'array'],
            'filters.status' => ['nullable', 'in:active,inactive,unsubscribed,blocked,invalid,bounced'],
            'filters.source' => ['nullable', 'string', 'max:255'],
            'filters.district' => ['nullable', 'string', 'max:255'],
            'filters.city' => ['nullable', 'string', 'max:255'],
            'filters.gender' => ['nullable', 'in:male,female,other'],
            'filters.date_from' => ['nullable', 'date'],
            'filters.date_to' => ['nullable', 'date'],
            'filters.tag_ids' => ['nullable', 'array'],
            'filters.tag_ids.*' => ['integer', 'exists:tags,id'],
            'filters.list_ids' => ['nullable', 'array'],
            'filters.list_ids.*' => ['integer', 'exists:lists,id'],
        ]);

        $segment = SavedSegment::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'filters' => $validated['filters'],
            'created_by' => $request->user()->id,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['segment' => $segment], 201);
        }

        return redirect()->route('segments.index')
            ->with('success', 'Segment created successfully.');
    }

    public function show(SavedSegment $segment): Response
    {
        return Inertia::render('Segments/Show', [
            'segment' => $segment,
        ]);
    }

    public function edit(SavedSegment $segment): Response
    {
        return Inertia::render('Segments/Edit', [
            'segment' => $segment,
        ]);
    }

    public function update(Request $request, SavedSegment $segment)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'filters' => ['required', 'array'],
        ]);

        $segment->update($validated);

        if ($request->expectsJson()) {
            return response()->json(['segment' => $segment->fresh()]);
        }

        return redirect()->route('segments.index')
            ->with('success', 'Segment updated successfully.');
    }

    public function destroy(Request $request, SavedSegment $segment)
    {
        $segment->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Segment deleted successfully.']);
        }

        return redirect()->route('segments.index')
            ->with('success', 'Segment deleted successfully.');
    }

    /**
     * Preview contacts matching a segment.
     */
    public function preview(Request $request, SavedSegment $segment): JsonResponse
    {
        $query = $this->applySegmentFilters(Contact::query(), $segment->filters);
        $count = $query->count();
        $sample = $query->limit(10)->get(['id', 'first_name', 'last_name', 'full_name', 'phone_normalised', 'email', 'status']);

        return response()->json([
            'count' => $count,
            'sample' => $sample,
        ]);
    }

    /**
     * Apply segment filters to a query.
     */
    protected function applySegmentFilters($query, array $filters)
    {
        $query->canReceiveSms();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        if (! empty($filters['district'])) {
            $query->where('district', $filters['district']);
        }

        if (! empty($filters['city'])) {
            $query->where('city', $filters['city']);
        }

        if (! empty($filters['gender'])) {
            $query->where('gender', $filters['gender']);
        }

        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['tag_ids'])) {
            $query->whereHas('tags', fn ($tq) => $tq->whereIn('tags.id', $filters['tag_ids']));
        }

        if (! empty($filters['list_ids'])) {
            $query->whereHas('lists', fn ($lq) => $lq->whereIn('lists.id', $filters['list_ids']));
        }

        return $query;
    }
}
