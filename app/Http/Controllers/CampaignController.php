<?php

namespace App\Http\Controllers;

use App\Http\Requests\CampaignRequest;
use App\Http\Requests\CampaignSendRequest;
use App\Jobs\FinalizeCampaign;
use App\Jobs\PrepareCampaignRecipients;
use App\Jobs\SendCampaignMessages;
use App\Models\CampaignRecipient;
use App\Models\SmsCampaign;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CampaignController extends Controller
{
    public function __construct(
        protected ActivityLogger $activityLogger,
    ) {}

    public function index(Request $request): Response
    {
        $campaigns = SmsCampaign::with('creator')
            ->when($request->search, fn ($q, $search) => $q->search($search))
            ->when($request->status, fn ($q, $status) => $q->byStatus($status))
            ->latest()
            ->paginate($request->get('per_page', 25));

        return Inertia::render('Campaigns/Index', [
            'campaigns' => $campaigns,
            'filters' => $request->only(['search', 'status', 'per_page']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Campaigns/Create', [
            'lists' => \App\Models\ListModel::active()->orderBy('name')->get(['id', 'name', 'colour']),
            'tags' => \App\Models\Tag::orderBy('name')->get(['id', 'name', 'colour']),
            'segments' => \App\Models\SavedSegment::orderBy('name')->get(['id', 'name', 'description']),
            'templates' => \App\Models\SmsTemplate::active()->orderBy('name')->get(['id', 'name', 'body']),
        ]);
    }

    public function store(CampaignRequest $request)
    {
        $campaign = SmsCampaign::create([
            'uuid' => Str::uuid(),
            'name' => $request->name,
            'message_body' => $request->message_body,
            'sender_id' => $request->sender_id,
            'target_type' => $request->target_type,
            'target_filters' => $request->target_filters,
            'status' => $request->status ?? 'draft',
            'scheduled_at' => $request->scheduled_at,
            'created_by' => $request->user()->id,
        ]);

        $this->activityLogger->logCampaignCreated($campaign);

        if ($request->expectsJson()) {
            return response()->json(['campaign' => $campaign], 201);
        }

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campaign created successfully.');
    }

    public function show(SmsCampaign $campaign, Request $request): Response
    {
        $campaign->load(['creator', 'approver']);

        return Inertia::render('Campaigns/Show', [
            'campaign' => $campaign,
        ]);
    }

    public function edit(SmsCampaign $campaign): Response
    {
        if (!$campaign->isDraft()) {
            abort(422, 'Only draft campaigns can be edited.');
        }

        return Inertia::render('Campaigns/Edit', [
            'campaign' => $campaign,
            'lists' => \App\Models\ListModel::active()->orderBy('name')->get(['id', 'name', 'colour']),
            'tags' => \App\Models\Tag::orderBy('name')->get(['id', 'name', 'colour']),
            'segments' => \App\Models\SavedSegment::orderBy('name')->get(['id', 'name', 'description']),
        ]);
    }

    public function update(CampaignRequest $request, SmsCampaign $campaign)
    {
        if (!$campaign->isDraft()) {
            abort(422, 'Only draft campaigns can be edited.');
        }

        $campaign->update([
            'name' => $request->name,
            'message_body' => $request->message_body,
            'sender_id' => $request->sender_id,
            'target_type' => $request->target_type,
            'target_filters' => $request->target_filters,
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
        if (!$campaign->canBeCancelled()) {
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
        if (!$campaign->canBeSent()) {
            abort(422, 'Campaign cannot be sent in its current status: ' . $campaign->status);
        }

        // Dispatch recipient preparation, then sending chain
        PrepareCampaignRecipients::dispatch($campaign)
            ->chain([
                new SendCampaignMessages($campaign),
                new FinalizeCampaign($campaign),
            ]);

        $this->activityLogger->logCampaignSent($campaign);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Campaign is being processed.']);
        }

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campaign sending has started.');
    }

    /**
     * Pause a campaign.
     */
    public function pause(Request $request, SmsCampaign $campaign): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        if (!$campaign->canBePaused()) {
            abort(422, 'Campaign cannot be paused in its current status.');
        }

        $campaign->markPaused();
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
    public function resume(Request $request, SmsCampaign $campaign): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        if (!$campaign->isPaused()) {
            abort(422, 'Only paused campaigns can be resumed.');
        }

        $campaign->markSending();

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
    public function cancel(Request $request, SmsCampaign $campaign): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        if (!$campaign->canBeCancelled()) {
            abort(422, 'Campaign cannot be cancelled in its current status.');
        }

        $campaign->markCancelled();
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
        $newCampaign->name = $campaign->name . ' (Copy)';
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
        $recipients = CampaignRecipient::where('campaign_id', $campaign->id)
            ->with('contact')
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->orderBy('id')
            ->paginate($request->get('per_page', 50));

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

        $hourlyData = \App\Models\SmsMessage::where('campaign_id', $campaign->id)
            ->whereNotNull('sent_at')
            ->selectRaw("TO_CHAR(sent_at, 'YYYY-MM-DD HH24:00') as hour, COUNT(*) as count")
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        if ($request->expectsJson()) {
            return response()->json([
                'campaign' => $campaign,
                'status_counts' => $statusCounts,
                'hourly_data' => $hourlyData,
            ]);
        }

        return Inertia::render('Campaigns/Report', [
            'campaign' => $campaign,
            'status_counts' => $statusCounts,
            'hourly_data' => $hourlyData,
        ]);
    }
}
