<?php

namespace App\Http\Controllers;

use App\Models\CampaignRecipient;
use App\Models\Contact;
use App\Models\SmsCampaign;
use App\Models\SmsMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        // Overall SMS stats
        $totalSent = SmsMessage::where('status', 'sent')->count();
        $totalFailed = SmsMessage::where('status', 'failed')->count();
        $totalDelivered = SmsMessage::where('status', 'delivered')->count();
        $totalPending = SmsMessage::where('status', 'pending')->count();

        // This month stats
        $startOfMonth = now()->startOfMonth();
        $sentThisMonth = SmsMessage::where('status', 'sent')
            ->where('sent_at', '>=', $startOfMonth)
            ->count();
        $failedThisMonth = SmsMessage::where('status', 'failed')
            ->where('failed_at', '>=', $startOfMonth)
            ->count();

        // Campaign stats
        $totalCampaigns = SmsCampaign::count();
        $completedCampaigns = SmsCampaign::where('status', 'completed')->count();

        // Daily SMS volume (last 30 days)
        $dailyVolume = SmsMessage::select(
            DB::raw("TO_CHAR(sent_at, 'YYYY-MM-DD') as date"),
            DB::raw("SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent"),
            DB::raw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed")
        )
            ->where('sent_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top campaigns by volume
        $topCampaigns = SmsCampaign::where('status', 'completed')
            ->orderByDesc('total_recipients')
            ->limit(10)
            ->get()
            ->map(fn ($campaign) => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'total_recipients' => $campaign->total_recipients,
                'sent_count' => $campaign->sent_count,
                'failed_count' => $campaign->failed_count,
                'success_rate' => $campaign->success_rate,
                'completed_at' => $campaign->completed_at?->toISOString(),
            ]);

        // SMS by provider
        $byProvider = SmsMessage::select('provider', DB::raw('COUNT(*) as count'))
            ->whereNotNull('provider')
            ->groupBy('provider')
            ->pluck('count', 'provider');

        return Inertia::render('Reports/Index', [
            'stats' => [
                'total_sent' => $totalSent,
                'total_failed' => $totalFailed,
                'total_delivered' => $totalDelivered,
                'total_pending' => $totalPending,
                'sent_this_month' => $sentThisMonth,
                'failed_this_month' => $failedThisMonth,
                'total_campaigns' => $totalCampaigns,
                'completed_campaigns' => $completedCampaigns,
            ],
            'daily_volume' => $dailyVolume,
            'top_campaigns' => $topCampaigns,
            'by_provider' => $byProvider,
        ]);
    }

    /**
     * Campaign-specific report.
     */
    public function campaignReport(SmsCampaign $campaign, Request $request): Response
    {
        $campaign->load(['creator']);

        $statusCounts = CampaignRecipient::where('campaign_id', $campaign->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $hourlyData = SmsMessage::where('campaign_id', $campaign->id)
            ->whereNotNull('sent_at')
            ->selectRaw("TO_CHAR(sent_at, 'YYYY-MM-DD HH24:00') as hour, COUNT(*) as count")
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        $failedMessages = SmsMessage::where('campaign_id', $campaign->id)
            ->where('status', 'failed')
            ->with('contact')
            ->limit(100)
            ->get();

        return Inertia::render('Reports/Campaign', [
            'campaign' => $campaign,
            'status_counts' => $statusCounts,
            'hourly_data' => $hourlyData,
            'failed_messages' => $failedMessages,
        ]);
    }

    /**
     * Export campaign report as CSV.
     */
    public function exportCampaign(SmsCampaign $campaign): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="campaign_report_' . $campaign->id . '_' . now()->format('Y-m_d_His') . '.csv"',
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
