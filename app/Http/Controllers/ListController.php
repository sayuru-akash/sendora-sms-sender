<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListRequest;
use App\Models\ListModel;
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
            ->orderBy($request->get('sort_by', 'name'), $request->get('sort_dir', 'asc'))
            ->paginate($request->get('per_page', 25));

        return Inertia::render('Lists/Index', [
            'lists' => $lists,
            'filters' => $request->only(['search', 'sort_by', 'sort_dir', 'per_page']),
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
            'colour' => $request->colour,
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
        $contacts = $list->contacts()
            ->when($request->search, fn ($q, $search) => $q->search($search))
            ->paginate($request->get('per_page', 25));

        return Inertia::render('Lists/Show', [
            'list' => $list,
            'contacts' => $contacts,
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
            'colour' => $request->colour,
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
            'message' => count($request->contact_ids) . ' contact(s) added to list.',
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
            'message' => count($request->contact_ids) . ' contact(s) removed from list.',
        ]);
    }

    /**
     * Export list contacts as CSV.
     */
    public function export(Request $request, ListModel $list): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $list->slug . '_contacts_' . now()->format('Y-m_d_His') . '.csv"',
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
