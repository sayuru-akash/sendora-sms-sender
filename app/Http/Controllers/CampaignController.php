<?php

namespace App\Http\Controllers;

use App\Http\Requests\CampaignRequest;
use App\Http\Requests\CampaignSendRequest;
use App\Jobs\FinalizeCampaign;
use App\Jobs\PrepareCampaignRecipients;
use App\Jobs\SendCampaignMessages;
use App\Jobs\SendSingleSms;
use App\Models\CampaignRecipient;
use App\Models\Contact;
use App\Models\ListModel;
use App\Models\SavedSegment;
use App\Models\SmsCampaign;
use App\Models\SmsMessage;
use App\Models\SmsTemplate;
use App\Models\Tag;
use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class CampaignController extends Controller
{
    public function __construct(
        protected ActivityLogger $activityLogger,
    ) {}

    public function index(Request $request): Response
    {
        $baseQuery = SmsCampaign::query()
            ->when($request->search, fn ($q, $search) => $q->search($search))
            ->when($request->status, fn ($q, $status) => $q->byStatus($status));

        $summary = (clone $baseQuery)
            ->selectRaw('COUNT(*) as campaigns_count')
            ->selectRaw('COALESCE(SUM(total_recipients), 0) as total_recipients_sum')
            ->selectRaw('COALESCE(SUM(sent_count), 0) as sent_count_sum')
            ->selectRaw('COALESCE(SUM(failed_count), 0) as failed_count_sum')
            ->selectRaw('COALESCE(SUM(pending_count), 0) as pending_count_sum')
            ->selectRaw('COALESCE(SUM(queued_count), 0) as queued_count_sum')
            ->first();

        $statusCounts = (clone $baseQuery)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $campaigns = $this->paginate(
            (clone $baseQuery)
                ->with('creator')
                ->latest(),
            $request,
            25
        );

        return Inertia::render('Campaigns/Index', [
            'campaigns' => $campaigns,
            'summary' => [
                'campaigns_count' => (int) $summary->campaigns_count,
                'total_recipients_sum' => (int) $summary->total_recipients_sum,
                'sent_count_sum' => (int) $summary->sent_count_sum,
                'failed_count_sum' => (int) $summary->failed_count_sum,
                'pending_count_sum' => (int) $summary->pending_count_sum,
                'queued_count_sum' => (int) $summary->queued_count_sum,
                'status_counts' => $statusCounts,
            ],
            'filters' => $request->only(['search', 'status', 'per_page']),
        ]);
    }

    public function builder(Request $request): Response
    {
        $lists = ListModel::active()->orderBy('name')->get(['id', 'name', 'colour']);
        $tags = Tag::orderBy('name')->get(['id', 'name', 'colour']);
        $templates = SmsTemplate::active()->orderBy('name')->get(['id', 'name', 'body']);
        $initialAudience = [
            'target_type' => 'all_contacts',
            'list_ids' => [],
            'tag_ids' => [],
            'contact_ids' => [],
        ];

        if ($request->integer('list_id') > 0) {
            $initialAudience['target_type'] = 'list';
            $initialAudience['list_ids'] = [$request->integer('list_id')];
        } elseif ($request->integer('tag_id') > 0) {
            $initialAudience['target_type'] = 'tag';
            $initialAudience['tag_ids'] = [$request->integer('tag_id')];
        }

        // Estimated count - all active contacts by default
        $estimatedCount = Contact::where('status', 'active')->count();

        return Inertia::render('Campaigns/Builder', [
            'lists' => $lists,
            'tags' => $tags,
            'templates' => $templates,
            'estimated_count' => $estimatedCount,
            'default_sender_id' => config('sms.source', ''),
            'initial_audience' => $initialAudience,
        ]);
    }

    public function audienceContacts(Request $request): JsonResponse
    {
        abort_unless($request->user()?->canCreateCampaigns(), 403);

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'ids' => ['nullable', 'array', 'max:500'],
            'ids.*' => ['integer', 'exists:contacts,id'],
        ]);

        $ids = collect($validated['ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $search = trim((string) ($validated['search'] ?? ''));

        $baseQuery = Contact::query()
            ->select(['id', 'full_name', 'first_name', 'last_name', 'email', 'phone_normalised', 'status'])
            ->canReceiveSms();

        $selectedContacts = $ids->isEmpty()
            ? collect()
            : (clone $baseQuery)->whereIn('id', $ids)->get();

        $searchContacts = (clone $baseQuery)
            ->when($search !== '', fn (Builder $query) => $query->search($search))
            ->orderBy('full_name')
            ->limit(30)
            ->get();

        $contacts = $selectedContacts
            ->concat($searchContacts)
            ->unique('id')
            ->values();

        return response()->json([
            'contacts' => $contacts->map(fn (Contact $contact) => $this->formatAudienceContact($contact)),
        ]);
    }

    public function audienceEstimate(Request $request): JsonResponse
    {
        abort_unless($request->user()?->canCreateCampaigns(), 403);

        $validated = Validator::make($request->all(), [
            'target_type' => ['required', 'string', 'in:all_contacts,list,tag,manual_selection'],
            'list_ids' => ['nullable', 'array'],
            'list_ids.*' => ['integer', 'exists:lists,id'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'contact_ids' => ['nullable', 'array'],
            'contact_ids.*' => ['integer', 'exists:contacts,id'],
        ])->validate();

        return response()->json([
            'count' => $this->audienceQuery(
                $validated['target_type'],
                $validated['list_ids'] ?? [],
                $validated['tag_ids'] ?? [],
                $validated['contact_ids'] ?? [],
            )->count(),
        ]);
    }

    protected function audienceQuery(string $targetType, array $listIds = [], array $tagIds = [], array $contactIds = []): Builder
    {
        $query = Contact::query()->canReceiveSms()->distinct('contacts.id');

        return match ($targetType) {
            'list' => empty($listIds)
                ? $query->whereRaw('1 = 0')
                : $query->whereHas('lists', fn ($listQuery) => $listQuery->whereIn('lists.id', $listIds)),
            'tag' => empty($tagIds)
                ? $query->whereRaw('1 = 0')
                : $query->whereHas('tags', fn ($tagQuery) => $tagQuery->whereIn('tags.id', $tagIds)),
            'manual_selection' => empty($contactIds)
                ? $query->whereRaw('1 = 0')
                : $query->whereIn('contacts.id', $contactIds),
            default => $query,
        };
    }

    /**
     * @return array{id: int, name: string, email: string|null, phone: string|null, status: string}
     */
    protected function formatAudienceContact(Contact $contact): array
    {
        return [
            'id' => $contact->id,
            'name' => $contact->display_name,
            'email' => $contact->email,
            'phone' => $contact->phone_normalised,
            'status' => $contact->status,
        ];
    }

    public function create(Request $request): Response
    {
        $initialAudience = [
            'target_type' => $request->integer('segment_id') > 0 ? 'saved_segment' : 'all_contacts',
            'segment_id' => $request->integer('segment_id') ?: null,
            'list_ids' => $request->integer('list_id') > 0 ? [$request->integer('list_id')] : [],
            'tag_ids' => $request->integer('tag_id') > 0 ? [$request->integer('tag_id')] : [],
            'contact_ids' => [],
        ];

        if ($initialAudience['list_ids'] !== []) {
            $initialAudience['target_type'] = 'list';
        } elseif ($initialAudience['tag_ids'] !== []) {
            $initialAudience['target_type'] = 'tag';
        }

        return Inertia::render('Campaigns/Create', [
            'lists' => ListModel::active()->orderBy('name')->get(['id', 'name', 'colour']),
            'tags' => Tag::orderBy('name')->get(['id', 'name', 'colour']),
            'segments' => SavedSegment::orderBy('name')->get(['id', 'name', 'description']),
            'templates' => SmsTemplate::active()->orderBy('name')->get(['id', 'name', 'body']),
            'initial_audience' => $initialAudience,
        ]);
    }

    public function store(CampaignRequest $request)
    {
        if ($request->boolean('send_now')) {
            abort_unless($request->user()->canSendCampaigns(), 403);
        }

        $campaign = SmsCampaign::create([
            'uuid' => Str::uuid(),
            'name' => $request->name,
            'message_body' => $request->message_body,
            'sender_id' => $request->sender_id,
            'target_type' => $request->target_type,
            'target_filters' => $request->target_filters,
            'template_id' => $request->template_id,
            'notes' => $request->notes,
            'status' => $request->status ?? 'draft',
            'scheduled_at' => $request->scheduled_at,
            'created_by' => $request->user()->id,
        ]);

        $this->activityLogger->logCampaignCreated($campaign);

        if ($request->boolean('send_now')) {
            $this->dispatchCampaignSend($campaign);
            $this->activityLogger->logCampaignSendRequested($campaign->fresh());

            if ($request->expectsJson()) {
                return response()->json(['campaign' => $campaign->fresh(), 'message' => 'Campaign is being processed.'], 201);
            }

            return redirect()->route('campaigns.show', $campaign)
                ->with('success', 'Campaign sending has started.');
        }

        if ($request->expectsJson()) {
            return response()->json(['campaign' => $campaign], 201);
        }

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campaign created successfully.');
    }

    public function show(SmsCampaign $campaign, Request $request): Response
    {
        $campaign->load(['creator', 'approver']);

        $result = $this->paginate(
            CampaignRecipient::where('campaign_id', $campaign->id)
                ->with('contact')
                ->when($request->status, fn ($q, $status) => $q->where('status', $status))
                ->orderBy('id'),
            $request,
            50
        );

        // Transform recipients to match CampaignRecipient type
        $recipients = [
            'data' => array_map(fn ($recipient) => [
                'id' => $recipient->id,
                'campaign_id' => $recipient->campaign_id,
                'contact_id' => $recipient->contact_id,
                'contact_name' => $recipient->contact?->full_name ?? 'N/A',
                'contact_phone' => $recipient->phone_normalised,
                'status' => $recipient->status,
                'error_message' => $recipient->error_message,
                'sent_at' => $recipient->sent_at?->toISOString(),
                'created_at' => $recipient->created_at?->toISOString(),
            ], $result['data']),
            'meta' => $result['meta'],
        ];
        $recentActivities = Activity::with('causer')
            ->where('subject_type', SmsCampaign::class)
            ->where('subject_id', $campaign->id)
            ->latest('id')
            ->limit(6)
            ->get()
            ->map(fn (Activity $activity): array => ActivityLogController::formatActivity($activity))
            ->all();

        return Inertia::render('Campaigns/Show', [
            'campaign' => $campaign,
            'recipients' => $recipients,
            'recent_activities' => $recentActivities,
        ]);
    }

    public function edit(SmsCampaign $campaign): Response
    {
        if (! $campaign->isDraft()) {
            abort(422, 'Only draft campaigns can be edited.');
        }

        return Inertia::render('Campaigns/Edit', [
            'campaign' => $campaign,
            'lists' => ListModel::active()->orderBy('name')->get(['id', 'name', 'colour']),
            'tags' => Tag::orderBy('name')->get(['id', 'name', 'colour']),
            'segments' => SavedSegment::orderBy('name')->get(['id', 'name', 'description']),
        ]);
    }

    public function update(CampaignRequest $request, SmsCampaign $campaign)
    {
        if (! $campaign->isDraft()) {
            abort(422, 'Only draft campaigns can be edited.');
        }

        $campaign->update([
            'name' => $request->name,
            'message_body' => $request->message_body,
            'sender_id' => $request->sender_id,
            'target_type' => $request->target_type,
            'target_filters' => $request->target_filters,
            'template_id' => $request->template_id,
            'notes' => $request->notes,
            'scheduled_at' => $request->scheduled_at,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['campaign' => $campaign->fresh()]);
        }

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campaign updated successfully.');
    }

    public function destroy(Request $request, SmsCampaign $campaign)
    {
        if (! $campaign->canBeCancelled()) {
            abort(422, 'This campaign cannot be deleted in its current status.');
        }

        $campaign->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Campaign deleted successfully.']);
        }

        return redirect()->route('campaigns.index')
            ->with('success', 'Campaign deleted successfully.');
    }

    /**
     * Send a campaign - dispatches jobs.
     */
    public function send(CampaignSendRequest $request, SmsCampaign $campaign)
    {
        $this->authorize('send', $campaign);

        if (! $campaign->canBeSent()) {
            abort(422, 'Campaign cannot be sent in its current status: '.$campaign->status);
        }

        if (! $this->dispatchCampaignSend($campaign)) {
            abort(422, 'Campaign cannot be sent in its current status: '.$campaign->fresh()->status);
        }

        $this->activityLogger->logCampaignSendRequested($campaign->fresh());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Campaign is being processed.']);
        }

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campaign sending has started.');
    }

    public function resendFailed(Request $request, SmsCampaign $campaign): JsonResponse|RedirectResponse
    {
        $this->authorize('send', $campaign);

        $recipients = DB::transaction(function () use ($campaign): Collection {
            $campaign->refresh();

            if (! $campaign->canRetryFailedRecipients()) {
                abort(422, 'This campaign has no failed recipients to resend.');
            }

            $recipients = CampaignRecipient::where('campaign_id', $campaign->id)
                ->where('status', 'failed')
                ->lockForUpdate()
                ->get();

            if ($recipients->isEmpty()) {
                abort(422, 'This campaign has no failed recipients to resend.');
            }

            $this->resetFailedRecipientsForRetry($recipients);
            $this->refreshCampaignRecipientCounts($campaign);
            $campaign->markSending();

            return $recipients->fresh();
        });

        $this->dispatchRetryRecipients($campaign->fresh(), $recipients);
        $this->activityLogger->logCampaignResendQueued($campaign->fresh(), $recipients->count());

        if ($request->expectsJson()) {
            return response()->json([
                'campaign' => $campaign->fresh(),
                'message' => "{$recipients->count()} failed recipient(s) queued for resend.",
            ]);
        }

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', "{$recipients->count()} failed recipient(s) queued for resend.");
    }

    public function resendRecipient(Request $request, SmsCampaign $campaign, CampaignRecipient $recipient): JsonResponse|RedirectResponse
    {
        $this->authorize('send', $campaign);

        $recipients = DB::transaction(function () use ($campaign, $recipient): Collection {
            $campaign->refresh();

            if (! $campaign->canRetryFailedRecipients()) {
                abort(422, 'This campaign has no failed recipients to resend.');
            }

            $recipient = CampaignRecipient::where('campaign_id', $campaign->id)
                ->whereKey($recipient->id)
                ->where('status', 'failed')
                ->lockForUpdate()
                ->first();

            if (! $recipient) {
                abort(422, 'Only failed recipients can be resent.');
            }

            $recipients = collect([$recipient]);

            $this->resetFailedRecipientsForRetry($recipients);
            $this->refreshCampaignRecipientCounts($campaign);
            $campaign->markSending();

            return $recipients->map->fresh();
        });

        $this->dispatchRetryRecipients($campaign->fresh(), $recipients);
        $this->activityLogger->logCampaignResendQueued($campaign->fresh(), 1, $recipients->first());

        if ($request->expectsJson()) {
            return response()->json([
                'campaign' => $campaign->fresh(),
                'message' => 'Recipient queued for resend.',
            ]);
        }

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Recipient queued for resend.');
    }

    protected function dispatchCampaignSend(SmsCampaign $campaign): bool
    {
        $claimed = SmsCampaign::whereKey($campaign->id)
            ->whereIn('status', ['draft', 'scheduled'])
            ->update([
                'status' => 'queued',
                'updated_at' => now(),
            ]);

        if ($claimed !== 1) {
            return false;
        }

        $campaign->refresh();
        $this->activityLogger->logCampaignQueued($campaign->fresh());

        PrepareCampaignRecipients::dispatch($campaign)
            ->chain([
                new SendCampaignMessages($campaign),
                new FinalizeCampaign($campaign),
            ]);

        return true;
    }

    /**
     * @param  Collection<int, CampaignRecipient>  $recipients
     */
    protected function resetFailedRecipientsForRetry(Collection $recipients): void
    {
        CampaignRecipient::whereKey($recipients->pluck('id')->all())->update([
            'status' => 'queued',
            'queued_at' => now(),
            'sent_at' => null,
            'failed_at' => null,
            'provider_message_id' => null,
            'provider_response' => null,
            'error_message' => null,
            'updated_at' => now(),
        ]);
    }

    protected function refreshCampaignRecipientCounts(SmsCampaign $campaign): void
    {
        $counts = CampaignRecipient::where('campaign_id', $campaign->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $campaign->forceFill([
            'total_recipients' => $counts->sum(),
            'pending_count' => (int) ($counts['pending'] ?? 0),
            'queued_count' => (int) ($counts['queued'] ?? 0),
            'sent_count' => (int) ($counts['sent'] ?? 0),
            'failed_count' => (int) ($counts['failed'] ?? 0),
            'skipped_count' => (int) ($counts['skipped'] ?? 0),
        ])->save();
    }

    /**
     * @param  Collection<int, CampaignRecipient>  $recipients
     */
    protected function dispatchRetryRecipients(SmsCampaign $campaign, Collection $recipients): void
    {
        $recipients->each(fn (CampaignRecipient $recipient) => SendSingleSms::dispatch($campaign, $recipient));
        FinalizeCampaign::dispatch($campaign);
    }

    /**
     * Pause a campaign.
     */
    public function pause(Request $request, SmsCampaign $campaign): JsonResponse|RedirectResponse
    {
        $this->authorize('pause', $campaign);

        if (! $campaign->canBePaused()) {
            abort(422, 'Campaign cannot be paused in its current status.');
        }

        $campaign->markPaused();
        CampaignRecipient::where('campaign_id', $campaign->id)
            ->where('status', 'queued')
            ->update([
                'status' => 'pending',
                'queued_at' => null,
                'updated_at' => now(),
            ]);
        $this->refreshCampaignRecipientCounts($campaign);
        $this->activityLogger->logCampaignPaused($campaign);

        if ($request->expectsJson()) {
            return response()->json(['campaign' => $campaign->fresh()]);
        }

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campaign paused.');
    }

    /**
     * Resume a paused campaign.
     */
    public function resume(Request $request, SmsCampaign $campaign): JsonResponse|RedirectResponse
    {
        $this->authorize('resume', $campaign);

        if (! $campaign->isPaused()) {
            abort(422, 'Only paused campaigns can be resumed.');
        }

        $campaign->markSending();
        $this->activityLogger->logCampaignResumed($campaign->fresh());

        // Re-dispatch sending
        SendCampaignMessages::dispatch($campaign);

        if ($request->expectsJson()) {
            return response()->json(['campaign' => $campaign->fresh()]);
        }

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campaign resumed.');
    }

    /**
     * Cancel a campaign.
     */
    public function cancel(Request $request, SmsCampaign $campaign): JsonResponse|RedirectResponse
    {
        $this->authorize('cancel', $campaign);

        if (! $campaign->canBeCancelled()) {
            abort(422, 'Campaign cannot be cancelled in its current status.');
        }

        $campaign->markCancelled();
        CampaignRecipient::where('campaign_id', $campaign->id)
            ->whereIn('status', ['pending', 'queued'])
            ->update([
                'status' => 'skipped',
                'skip_reason' => 'Campaign cancelled.',
                'updated_at' => now(),
            ]);
        $this->refreshCampaignRecipientCounts($campaign);
        $this->activityLogger->logCampaignCancelled($campaign);

        if ($request->expectsJson()) {
            return response()->json(['campaign' => $campaign->fresh()]);
        }

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campaign cancelled.');
    }

    /**
     * Duplicate a campaign.
     */
    public function duplicate(Request $request, SmsCampaign $campaign)
    {
        $this->authorize('duplicate', $campaign);

        $newCampaign = $campaign->replicate();
        $newCampaign->uuid = Str::uuid();
        $newCampaign->status = 'draft';
        $newCampaign->scheduled_at = null;
        $newCampaign->started_at = null;
        $newCampaign->completed_at = null;
        $newCampaign->total_recipients = 0;
        $newCampaign->queued_count = 0;
        $newCampaign->sent_count = 0;
        $newCampaign->failed_count = 0;
        $newCampaign->skipped_count = 0;
        $newCampaign->pending_count = 0;
        $newCampaign->approved_by = null;
        $newCampaign->created_by = $request->user()->id;
        $newCampaign->name = $campaign->name.' (Copy)';
        $newCampaign->save();

        if ($request->expectsJson()) {
            return response()->json(['campaign' => $newCampaign], 201);
        }

        return redirect()->route('campaigns.edit', $newCampaign)
            ->with('success', 'Campaign duplicated successfully.');
    }

    /**
     * Show campaign recipients with status filters.
     */
    public function recipients(Request $request, SmsCampaign $campaign): Response|JsonResponse
    {
        $recipients = $this->paginate(
            CampaignRecipient::where('campaign_id', $campaign->id)
                ->with('contact')
                ->when($request->status, fn ($q, $status) => $q->where('status', $status))
                ->orderBy('id'),
            $request,
            50
        );

        if ($request->expectsJson()) {
            return response()->json(['recipients' => $recipients]);
        }

        return Inertia::render('Campaigns/Recipients', [
            'campaign' => $campaign,
            'recipients' => $recipients,
            'filters' => $request->only(['status', 'per_page']),
        ]);
    }

    /**
     * Campaign report/stats.
     */
    public function report(SmsCampaign $campaign, Request $request): Response|JsonResponse
    {
        $campaign->load(['creator', 'approver']);

        $statusCounts = CampaignRecipient::where('campaign_id', $campaign->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $hourExpression = $this->campaignReportHourExpression();
        $hourlyData = SmsMessage::where('campaign_id', $campaign->id)
            ->whereIn('status', ['sent', 'delivered', 'failed'])
            ->selectRaw("{$hourExpression} as hour")
            ->selectRaw("SUM(CASE WHEN status IN ('sent', 'delivered') THEN 1 ELSE 0 END) as sent")
            ->selectRaw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed")
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->map(fn ($item) => [
                'hour' => $item->hour,
                'sent' => (int) $item->sent,
                'failed' => (int) $item->failed,
            ]);

        if ($request->expectsJson()) {
            return response()->json([
                'campaign' => $campaign,
                'status_counts' => $statusCounts,
                'hourly_data' => $hourlyData,
            ]);
        }

        $stats = [
            'total' => $campaign->total_recipients,
            'sent' => (int) ($statusCounts['sent'] ?? 0),
            'failed' => (int) ($statusCounts['failed'] ?? 0),
            'skipped' => (int) ($statusCounts['skipped'] ?? 0),
            'pending' => (int) ($statusCounts['pending'] ?? 0) + (int) ($statusCounts['queued'] ?? 0),
            'success_rate' => $campaign->success_rate,
            'timeline' => $hourlyData->map(fn ($item) => [
                'time' => $item['hour'],
                'sent' => $item['sent'],
                'failed' => $item['failed'],
            ])->toArray(),
        ];

        return Inertia::render('Campaigns/Report', [
            'campaign' => $campaign,
            'stats' => $stats,
        ]);
    }

    protected function campaignReportHourExpression(): string
    {
        $timestamp = 'COALESCE(sent_at, failed_at, created_at)';

        return DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m-%d %H:00', {$timestamp})"
            : "TO_CHAR({$timestamp}, 'YYYY-MM-DD HH24:00')";
    }
}
