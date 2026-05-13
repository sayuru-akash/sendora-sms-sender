<?php

namespace App\Http\Controllers;

use App\Models\CampaignRecipient;
use App\Models\Contact;
use App\Models\ListModel;
use App\Models\SmsCampaign;
use App\Models\SmsMessage;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        // Overall stats
        $totalContacts = Contact::count();
        $totalSmsSent = SmsMessage::whereIn('status', ['sent', 'delivered'])->count();
        $totalCampaigns = SmsCampaign::count();
        $completedCampaigns = SmsCampaign::where('status', 'completed');
        $avgSuccessRate = $completedCampaigns->count() > 0
            ? round($completedCampaigns->get()->avg('success_rate'), 1)
            : 0;

        // SMS over time (last 12 months)
        $monthExpression = $this->smsOverTimeMonthExpression();
        $timestampExpression = $this->smsOverTimeTimestampExpression();
        $smsOverTime = SmsMessage::select(
            DB::raw("{$monthExpression} as month"),
            DB::raw("SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent"),
            DB::raw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed")
        )
            ->whereRaw("{$timestampExpression} >= ?", [now()->subMonths(12)])
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Contacts by source
        $contactsBySource = Contact::select('source', DB::raw('COUNT(*) as count'))
            ->whereNotNull('source')
            ->groupBy('source')
            ->get()
            ->map(fn ($item) => ['source' => $item->source ?? 'unknown', 'count' => (int) $item->count]);

        // Contacts by status
        $contactsByStatus = Contact::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->map(fn ($item) => ['status' => $item->status, 'count' => (int) $item->count]);

        // Top lists
        $topLists = ListModel::withCount('contacts')
            ->orderByDesc('contacts_count')
            ->limit(10)
            ->get()
            ->map(fn ($list) => ['name' => $list->name, 'count' => (int) $list->contacts_count]);

        // Top tags
        $topTags = Tag::withCount('contacts')
            ->orderByDesc('contacts_count')
            ->limit(10)
            ->get()
            ->map(fn ($tag) => ['name' => $tag->name, 'count' => (int) $tag->contacts_count]);

        return Inertia::render('Reports/Index', [
            'stats' => [
                'total_contacts' => $totalContacts,
                'total_sms_sent' => $totalSmsSent,
                'total_campaigns' => $totalCampaigns,
                'avg_success_rate' => $avgSuccessRate,
            ],
            'sms_over_time' => $smsOverTime,
            'contacts_by_source' => $contactsBySource,
            'contacts_by_status' => $contactsByStatus,
            'top_lists' => $topLists,
            'top_tags' => $topTags,
        ]);
    }

    /**
     * Campaign-specific report.
     */
    public function campaignReport(SmsCampaign $campaign, Request $request): Response
    {
        $campaign->load(['creator']);

        $rawStatusCounts = CampaignRecipient::where('campaign_id', $campaign->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $statusCounts = [
            'sent' => (int) ($rawStatusCounts['sent'] ?? 0),
            'failed' => (int) ($rawStatusCounts['failed'] ?? 0),
            'skipped' => (int) ($rawStatusCounts['skipped'] ?? 0),
            'pending' => (int) ($rawStatusCounts['pending'] ?? 0) + (int) ($rawStatusCounts['queued'] ?? 0),
        ];

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
                'count' => (int) $item->sent + (int) $item->failed,
            ]);

        $failedMessages = SmsMessage::where('campaign_id', $campaign->id)
            ->where('status', 'failed')
            ->with('contact')
            ->limit(100)
            ->get()
            ->map(fn (SmsMessage $message): array => [
                'id' => $message->id,
                'campaign_id' => $message->campaign_id,
                'campaign_name' => $campaign->name,
                'contact_id' => $message->contact_id,
                'contact_name' => $message->contact?->display_name ?? 'N/A',
                'contact_phone' => $message->phone_normalised,
                'message' => $message->message_body,
                'status' => $message->status,
                'error_message' => $message->error_message,
                'sent_at' => $message->sent_at?->toISOString(),
                'delivered_at' => null,
                'segments' => 0,
                'cost' => null,
                'created_at' => $message->created_at?->toISOString(),
            ]);

        return Inertia::render('Reports/Campaign', [
            'campaign' => $campaign,
            'status_counts' => $statusCounts,
            'hourly_data' => $hourlyData,
            'failed_messages' => $failedMessages,
        ]);
    }

    protected function campaignReportHourExpression(): string
    {
        $timestamp = 'COALESCE(sent_at, failed_at, created_at)';

        return DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m-%d %H:00', {$timestamp})"
            : "TO_CHAR({$timestamp}, 'YYYY-MM-DD HH24:00')";
    }

    protected function smsOverTimeTimestampExpression(): string
    {
        return 'COALESCE(sent_at, failed_at, created_at)';
    }

    protected function smsOverTimeMonthExpression(): string
    {
        $timestamp = $this->smsOverTimeTimestampExpression();

        return DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', {$timestamp})"
            : "TO_CHAR({$timestamp}, 'YYYY-MM')";
    }

    /**
     * Export campaign report as CSV.
     */
    public function exportCampaign(SmsCampaign $campaign): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="campaign_report_'.$campaign->id.'_'.now()->format('Y-m_d_His').'.csv"',
        ];

        return response()->stream(function () use ($campaign) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Contact Name', 'Phone', 'Status', 'Provider Message ID',
                'Error Message', 'Sent At', 'Failed At',
            ]);

            CampaignRecipient::where('campaign_id', $campaign->id)
                ->with('contact')
                ->chunk(500, function ($recipients) use ($handle) {
                    foreach ($recipients as $recipient) {
                        fputcsv($handle, [
                            $recipient->contact?->display_name ?? 'N/A',
                            $recipient->phone_normalised,
                            $recipient->status,
                            $recipient->provider_message_id,
                            $recipient->error_message,
                            $recipient->sent_at?->format('Y-m-d H:i:s'),
                            $recipient->failed_at?->format('Y-m-d H:i:s'),
                        ]);
                    }
                });

            fclose($handle);
        }, 200, $headers);
    }
}
