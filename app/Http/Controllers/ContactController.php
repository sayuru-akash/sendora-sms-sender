<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkActionRequest;
use App\Http\Requests\ContactRequest;
use App\Models\Contact;
use App\Models\ListModel;
use App\Models\Tag;
use App\Services\ActivityLogger;
use App\Services\PhoneNormalizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactController extends Controller
{
    public function __construct(
        protected PhoneNormalizer $phoneNormalizer,
        protected ActivityLogger $activityLogger,
    ) {}

    public function index(Request $request): Response
    {
        $query = $this->applyContactFilters(Contact::with(['tags', 'lists']), $request);

        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $allowedSorts = ['first_name', 'last_name', 'full_name', 'phone', 'email', 'company', 'status', 'source', 'created_at'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $contacts = $this->paginate($query, $request, 25);

        $tags = Tag::orderBy('name')->get(['id', 'name', 'colour']);
        $lists = ListModel::active()->orderBy('name')->get(['id', 'name', 'colour']);

        return Inertia::render('Contacts/Index', [
            'contacts' => $contacts,
            'tags' => $tags,
            'lists' => $lists,
            'filters' => $request->only(['search', 'status', 'source', 'district', 'city', 'tag_id', 'list_id', 'date_from', 'date_to', 'sort_by', 'sort_dir', 'per_page']),
        ]);
    }

    public function create(): Response
    {
        $tags = Tag::orderBy('name')->get(['id', 'name', 'colour']);
        $lists = ListModel::active()->orderBy('name')->get(['id', 'name', 'colour']);

        return Inertia::render('Contacts/Create', [
            'tags' => $tags,
            'lists' => $lists,
        ]);
    }

    public function store(ContactRequest $request)
    {
        $normalised = $this->phoneNormalizer->normalize($request->phone);

        $contact = DB::transaction(function () use ($request, $normalised) {
            $contact = Contact::create([
                'uuid' => Str::uuid(),
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'full_name' => $request->full_name ?? trim(($request->first_name ?? '').' '.($request->last_name ?? '')),
                'phone' => $request->phone,
                'phone_normalised' => $normalised,
                'email' => $request->email,
                'company' => $request->company,
                'job_title' => $request->job_title,
                'country' => $request->country ?? 'Sri Lanka',
                'district' => $request->district,
                'city' => $request->city,
                'gender' => $request->gender,
                'date_of_birth' => $request->date_of_birth,
                'source' => $request->source,
                'status' => $request->status ?? 'active',
                'notes' => $request->notes,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            if ($request->has('tags') && $request->tags) {
                $contact->tags()->sync($request->tags);
            }

            if ($request->has('lists') && $request->lists) {
                $contact->lists()->sync($request->lists);
            }

            return $contact;
        });

        $this->activityLogger->logContactCreated($contact);

        if ($request->expectsJson()) {
            return response()->json(['contact' => $contact->load(['tags', 'lists'])], 201);
        }

        return redirect()->route('contacts.show', $contact)
            ->with('success', 'Contact created successfully.');
    }

    public function show(Contact $contact): Response
    {
        $contact->load(['tags', 'lists']);

        $campaigns = $contact->campaignRecipients()
            ->with('campaign')
            ->get()
            ->map(fn ($recipient) => [
                'id' => $recipient->campaign->id,
                'name' => $recipient->campaign->name,
                'status' => $recipient->campaign->status,
                'total_recipients' => $recipient->campaign->total_recipients,
                'sent_count' => $recipient->campaign->sent_count,
                'failed_count' => $recipient->campaign->failed_count,
                'success_rate' => $recipient->campaign->success_rate,
                'target_type' => $recipient->campaign->target_type,
                'target_config' => $recipient->campaign->target_config,
                'sender_id' => $recipient->campaign->sender_id,
                'message_body' => $recipient->campaign->message_body,
                'template_id' => $recipient->campaign->template_id,
                'template_name' => $recipient->campaign->template_name,
                'total_recipients' => $recipient->campaign->total_recipients,
                'sent_count' => $recipient->campaign->sent_count,
                'failed_count' => $recipient->campaign->failed_count,
                'skipped_count' => $recipient->campaign->skipped_count,
                'pending_count' => $recipient->campaign->pending_count,
                'success_rate' => $recipient->campaign->success_rate,
                'scheduled_at' => $recipient->campaign->scheduled_at?->toISOString(),
                'started_at' => $recipient->campaign->started_at?->toISOString(),
                'completed_at' => $recipient->campaign->completed_at?->toISOString(),
                'created_by' => $recipient->campaign->created_by,
                'created_by_name' => $recipient->campaign->created_by_name,
                'notes' => $recipient->campaign->notes,
                'created_at' => $recipient->campaign->created_at?->toISOString(),
                'updated_at' => $recipient->campaign->updated_at?->toISOString(),
            ])
            ->unique('id')
            ->values();

        $smsHistory = $contact->smsMessages()
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn ($sms) => [
                'id' => $sms->id,
                'message' => $sms->message_body,
                'status' => $sms->status,
                'campaign_name' => $sms->campaign?->name,
                'sent_at' => $sms->sent_at?->toISOString(),
                'created_at' => $sms->created_at?->toISOString(),
            ]);

        return Inertia::render('Contacts/Show', [
            'contact' => $contact,
            'campaigns' => $campaigns,
            'sms_history' => $smsHistory,
        ]);
    }

    public function edit(Contact $contact): Response
    {
        $contact->load(['tags', 'lists']);
        $tags = Tag::orderBy('name')->get(['id', 'name', 'colour']);
        $lists = ListModel::active()->orderBy('name')->get(['id', 'name', 'colour']);

        return Inertia::render('Contacts/Edit', [
            'contact' => $contact,
            'tags' => $tags,
            'lists' => $lists,
        ]);
    }

    public function update(ContactRequest $request, Contact $contact)
    {
        $normalised = $this->phoneNormalizer->normalize($request->phone);

        DB::transaction(function () use ($request, $contact, $normalised) {
            $contact->update([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'full_name' => $request->full_name ?? trim(($request->first_name ?? '').' '.($request->last_name ?? '')),
                'phone' => $request->phone,
                'phone_normalised' => $normalised,
                'email' => $request->email,
                'company' => $request->company,
                'job_title' => $request->job_title,
                'country' => $request->country,
                'district' => $request->district,
                'city' => $request->city,
                'gender' => $request->gender,
                'date_of_birth' => $request->date_of_birth,
                'source' => $request->source,
                'status' => $request->status,
                'notes' => $request->notes,
                'updated_by' => $request->user()->id,
            ]);

            if ($request->has('tags')) {
                $contact->tags()->sync($request->tags ?? []);
            }

            if ($request->has('lists')) {
                $contact->lists()->sync($request->lists ?? []);
            }
        });

        $this->activityLogger->logContactUpdated($contact);

        if ($request->expectsJson()) {
            return response()->json(['contact' => $contact->fresh()->load(['tags', 'lists'])]);
        }

        return redirect()->route('contacts.show', $contact)
            ->with('success', 'Contact updated successfully.');
    }

    public function destroy(Request $request, Contact $contact)
    {
        $this->authorize('delete', $contact);

        $this->activityLogger->logContactDeleted($contact);
        $contact->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Contact deleted successfully.']);
        }

        return redirect()->route('contacts.index')
            ->with('success', 'Contact deleted successfully.');
    }

    public function block(Request $request, Contact $contact)
    {
        $contact->update(['status' => 'blocked', 'blocked_at' => now()]);

        $this->activityLogger->logContactUpdated($contact);

        if ($request->expectsJson()) {
            return response()->json(['contact' => $contact->fresh()]);
        }

        return redirect()->route('contacts.show', $contact)
            ->with('success', 'Contact blocked successfully.');
    }

    /**
     * Bulk actions on contacts.
     */
    public function bulkAction(BulkActionRequest $request): JsonResponse
    {
        $action = $request->input('action');
        $contactIds = $request->input('contact_ids');

        switch ($action) {
            case 'delete':
                Contact::whereIn('id', $contactIds)->delete();
                $message = count($contactIds).' contact(s) deleted.';
                break;

            case 'tag':
                $tagIds = $request->input('tag_ids', []);
                foreach ($contactIds as $contactId) {
                    $contact = Contact::find($contactId);
                    if ($contact) {
                        $contact->tags()->syncWithoutDetaching($tagIds);
                    }
                }
                $message = 'Tags applied to '.count($contactIds).' contact(s).';
                break;

            case 'untag':
                $tagIds = $request->input('tag_ids', []);
                foreach ($contactIds as $contactId) {
                    $contact = Contact::find($contactId);
                    if ($contact) {
                        $contact->tags()->detach($tagIds);
                    }
                }
                $message = 'Tags removed from '.count($contactIds).' contact(s).';
                break;

            case 'add_to_list':
                $listId = $request->input('list_id');
                $list = ListModel::find($listId);
                if ($list) {
                    $list->contacts()->syncWithoutDetaching($contactIds);
                }
                $message = count($contactIds).' contact(s) added to list.';
                break;

            case 'remove_from_list':
                $listId = $request->input('list_id');
                $list = ListModel::find($listId);
                if ($list) {
                    $list->contacts()->detach($contactIds);
                }
                $message = count($contactIds).' contact(s) removed from list.';
                break;

            case 'update_status':
                $status = $request->input('status');
                $updateData = ['status' => $status];

                if ($status === 'unsubscribed') {
                    $updateData['unsubscribed_at'] = now();
                } elseif ($status === 'blocked') {
                    $updateData['blocked_at'] = now();
                }

                Contact::whereIn('id', $contactIds)->update($updateData);
                $message = count($contactIds).' contact(s) updated to '.$status.'.';
                break;

            default:
                return response()->json(['message' => 'Unknown action.'], 400);
        }

        return response()->json(['message' => $message]);
    }

    /**
     * Export contacts as CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $query = $this->applyContactFilters(Contact::with(['tags', 'lists']), $request);

        // If specific IDs are provided
        if ($request->has('contact_ids')) {
            $query = Contact::with(['tags', 'lists'])->whereIn('id', $request->contact_ids);
        }

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="contacts_export_'.now()->format('Y-m_d_His').'.csv"',
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // Header row
            fputcsv($handle, [
                'ID', 'First Name', 'Last Name', 'Full Name', 'Phone', 'Phone (Normalised)',
                'Email', 'Company', 'Job Title', 'District', 'City', 'Gender',
                'Date of Birth', 'Source', 'Status', 'Tags', 'Lists', 'Notes',
                'Created At', 'Last Contacted At',
            ]);

            $query->chunk(500, function ($contacts) use ($handle) {
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
                        $contact->job_title,
                        $contact->district,
                        $contact->city,
                        $contact->gender,
                        $contact->date_of_birth?->format('Y-m-d'),
                        $contact->source,
                        $contact->status,
                        $contact->tags->pluck('name')->implode(', '),
                        $contact->lists->pluck('name')->implode(', '),
                        $contact->notes,
                        $contact->created_at?->format('Y-m-d H:i:s'),
                        $contact->last_contacted_at?->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * @param  Builder<Contact>  $query
     * @return Builder<Contact>
     */
    private function applyContactFilters(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->search, fn ($q, $search) => $q->search($search))
            ->when($request->status, fn ($q, $status) => $q->byStatus($status))
            ->when($request->source, fn ($q, $source) => $q->bySource($source))
            ->when($request->district, fn ($q, $district) => $q->byDistrict($district))
            ->when($request->city, fn ($q, $city) => $q->byCity($city))
            ->when($request->tag_id, fn ($q, $tagId) => $q->whereHas('tags', fn ($tq) => $tq->where('tags.id', $tagId)))
            ->when($request->list_id, fn ($q, $listId) => $q->whereHas('lists', fn ($lq) => $lq->where('lists.id', $listId)))
            ->when($request->date_from, fn ($q, $from) => $q->whereDate('created_at', '>=', $from))
            ->when($request->date_to, fn ($q, $to) => $q->whereDate('created_at', '<=', $to));
    }
}
