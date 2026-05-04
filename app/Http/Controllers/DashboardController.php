<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Import;
use App\Models\SmsCampaign;
use App\Models\SmsMessage;
use App\Models\Tag;
use App\Models\ListModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        // Contact stats
        $totalContacts = Contact::count();
        $activeContacts = Contact::where('status', 'active')->count();

        // Campaign stats
        $activeCampaigns = SmsCampaign::whereIn('status', ['sending', 'queued'])->count();
        $scheduledCampaigns = SmsCampaign::where('status', 'scheduled')->count();

        // SMS stats this month
        $startOfMonth = now()->startOfMonth();
        $sentThisMonth = SmsMessage::where('status', 'sent')
            ->where('sent_at', '>=', $startOfMonth)
            ->count();
        $failedThisMonth = SmsMessage::where('status', 'failed')
            ->where('failed_at', '>=', $startOfMonth)
            ->count();

        // Recent campaigns
        $recentCampaigns = SmsCampaign::with('creator')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($campaign) => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'status' => $campaign->status,
                'total_recipients' => $campaign->total_recipients,
                'sent_count' => $campaign->sent_count,
                'failed_count' => $campaign->failed_count,
                'success_rate' => $campaign->success_rate,
                'created_at' => $campaign->created_at?->toISOString(),
                'creator' => $campaign->creator?->name,
            ]);

        // Recent imports
        $recentImports = Import::with('creator')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($import) => [
                'id' => $import->id,
                'original_filename' => $import->original_filename,
                'status' => $import->status,
                'total_rows' => $import->total_rows,
                'successful_rows' => $import->successful_rows,
                'failed_rows' => $import->failed_rows,
                'progress_percent' => $import->progress_percent,
                'created_at' => $import->created_at?->toISOString(),
                'creator' => $import->creator?->name,
            ]);

        // Contact growth data (last 12 months)
        $contactGrowth = Contact::select(
            DB::raw("TO_CHAR(created_at, 'YYYY-MM') as month"),
            DB::raw('COUNT(*) as count')
        )
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Campaign success/failure data (last 12 months)
        $campaignData = SmsCampaign::select(
            DB::raw("TO_CHAR(created_at, 'YYYY-MM') as month"),
            DB::raw('SUM(sent_count) as sent'),
            DB::raw('SUM(failed_count) as failed')
        )
            ->where('created_at', '>=', now()->subMonths(12))
            ->where('status', 'completed')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Top lists by contact count
        $topLists = ListModel::withCount('contacts')
            ->orderByDesc('contacts_count')
            ->limit(5)
            ->get()
            ->map(fn ($list) => [
                'id' => $list->id,
                'name' => $list->name,
                'colour' => $list->colour,
                'contact_count' => $list->contacts_count,
            ]);

        // Top tags by contact count
        $topTags = Tag::withCount('contacts')
            ->orderByDesc('contacts_count')
            ->limit(5)
            ->get()
            ->map(fn ($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'colour' => $tag->colour,
                'contact_count' => $tag->contacts_count,
            ]);

        // Contacts by status
        $contactsByStatus = Contact::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_contacts' => $totalContacts,
                'active_contacts' => $activeContacts,
                'sent_this_month' => $sentThisMonth,
                'failed_this_month' => $failedThisMonth,
                'active_campaigns' => $activeCampaigns,
                'scheduled_campaigns' => $scheduledCampaigns,
            ],
            'recent_campaigns' => $recentCampaigns,
            'recent_imports' => $recentImports,
            'contact_growth' => $contactGrowth,
            'campaign_data' => $campaignData,
            'top_lists' => $topLists,
            'top_tags' => $topTags,
            'contacts_by_status' => $contactsByStatus,
        ]);
    }
}
