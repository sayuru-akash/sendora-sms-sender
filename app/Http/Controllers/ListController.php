<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListRequest;
use App\Models\ListModel;
use App\Models\SmsCampaign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListController extends Controller
{
    public function index(Request $request): Response
    {
        $lists = ListModel::withCount('contacts')
            ->when($request->search, fn ($q, $search) => $q->search($search))
            ->orderBy(
                in_array($request->get('sort_by'), ['name', 'status', 'created_at', 'contacts_count'], true) ? $request->get('sort_by') : 'name',
                $request->get('sort_dir') === 'desc' ? 'desc' : 'asc',
            )
            ->get();

        return Inertia::render('Lists/Index', [
            'lists' => $lists,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Lists/Create');
    }

    public function store(ListRequest $request)
    {
        $list = ListModel::create([
            'name' => $request->name,
            'description' => $request->description,
            'colour' => $request->input('colour', $request->input('color')),
            'status' => $request->status ?? 'active',
            'created_by' => $request->user()->id,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['list' => $list], 201);
        }

        return redirect()->route('lists.index')
            ->with('success', 'List created successfully.');
    }

    public function show(ListModel $list, Request $request): Response
    {
        $list->loadCount('contacts');

        $contacts = $this->paginate(
            $list->contacts()
                ->when($request->search, fn ($q, $search) => $q->search($search)),
            $request,
            25
        );

        $campaigns = SmsCampaign::whereJsonContains('target_filters->list_ids', $list->id)
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

        return Inertia::render('Lists/Show', [
            'list' => $list,
            'contacts' => $contacts,
            'campaigns' => $campaigns,
        ]);
    }

    public function edit(ListModel $list): Response
    {
        return Inertia::render('Lists/Edit', [
            'list' => $list,
        ]);
    }

    public function update(ListRequest $request, ListModel $list)
    {
        $list->update([
            'name' => $request->name,
            'description' => $request->description,
            'colour' => $request->input('colour', $request->input('color')),
            'status' => $request->status,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['list' => $list->fresh()]);
        }

        return redirect()->route('lists.index')
            ->with('success', 'List updated successfully.');
    }

    public function destroy(Request $request, ListModel $list)
    {
        $list->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'List deleted successfully.']);
        }

        return redirect()->route('lists.index')
            ->with('success', 'List deleted successfully.');
    }

    /**
     * Add contacts to a list.
     */
    public function addContacts(Request $request, ListModel $list): JsonResponse
    {
        $request->validate([
            'contact_ids' => ['required', 'array', 'min:1'],
            'contact_ids.*' => ['integer', 'exists:contacts,id'],
        ]);

        $list->contacts()->syncWithoutDetaching($request->contact_ids);

        return response()->json([
            'message' => count($request->contact_ids).' contact(s) added to list.',
        ]);
    }

    /**
     * Remove contacts from a list.
     */
    public function removeContacts(Request $request, ListModel $list): JsonResponse
    {
        $request->validate([
            'contact_ids' => ['required', 'array', 'min:1'],
            'contact_ids.*' => ['integer', 'exists:contacts,id'],
        ]);

        $list->contacts()->detach($request->contact_ids);

        return response()->json([
            'message' => count($request->contact_ids).' contact(s) removed from list.',
        ]);
    }

    /**
     * Export list contacts as CSV.
     */
    public function export(Request $request, ListModel $list): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$list->slug.'_contacts_'.now()->format('Y-m_d_His').'.csv"',
        ];

        return response()->stream(function () use ($list) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID', 'First Name', 'Last Name', 'Full Name', 'Phone', 'Phone (Normalised)',
                'Email', 'Company', 'Status', 'Created At',
            ]);

            $list->contacts()->chunk(500, function ($contacts) use ($handle) {
                foreach ($contacts as $contact) {
                    fputcsv($handle, [
                        $contact->id,
                        $contact->first_name,
                        $contact->last_name,
                        $contact->full_name,
                        $contact->phone,
                        $contact->phone_normalised,
                        $contact->email,
                        $contact->company,
                        $contact->status,
                        $contact->created_at?->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }
}
