<?php

namespace App\Jobs;

use App\Models\CampaignRecipient;
use App\Models\Contact;
use App\Models\SavedSegment;
use App\Models\SmsCampaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PrepareCampaignRecipients implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 1800; // 30 minutes

    public function __construct(
        public SmsCampaign $campaign,
    ) {}

    public function handle(): void
    {
        $this->campaign->markQueued();

        $batch = [];

        foreach ($this->resolveContacts()->orderBy('id')->cursor() as $contact) {
            $batch[] = [
                'campaign_id' => $this->campaign->id,
                'contact_id' => $contact->id,
                'phone_normalised' => $contact->phone_normalised,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= 500) {
                CampaignRecipient::insertOrIgnore($batch);
                $batch = [];
            }
        }

        if (! empty($batch)) {
            CampaignRecipient::insertOrIgnore($batch);
        }

        $totalRecipients = CampaignRecipient::where('campaign_id', $this->campaign->id)->count();
        $pendingRecipients = CampaignRecipient::where('campaign_id', $this->campaign->id)
            ->where('status', 'pending')
            ->count();

        $this->campaign->update([
            'total_recipients' => $totalRecipients,
            'pending_count' => $pendingRecipients,
        ]);
    }

    /**
     * Resolve contacts based on campaign target type.
     */
    protected function resolveContacts(): Builder
    {
        $query = Contact::query()
            ->select(['id', 'phone_normalised'])
            ->canReceiveSms();

        switch ($this->campaign->target_type) {
            case 'all_contacts':
                // All contacts that can receive SMS (already filtered above)
                break;

            case 'list':
                $listIds = $this->campaign->target_filters['list_ids'] ?? [];
                if (! empty($listIds)) {
                    $query->whereHas('lists', fn ($q) => $q->whereIn('lists.id', $listIds));
                }
                break;

            case 'tag':
                $tagIds = $this->campaign->target_filters['tag_ids'] ?? [];
                if (! empty($tagIds)) {
                    $query->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $tagIds));
                }
                break;

            case 'saved_segment':
                $segmentId = $this->campaign->target_filters['segment_id'] ?? null;
                if ($segmentId) {
                    $segment = SavedSegment::find($segmentId);
                    if ($segment) {
                        $query = $this->applySegmentFilters($query, $segment->filters);
                    }
                }
                break;

            case 'manual_selection':
                $contactIds = $this->campaign->target_filters['contact_ids'] ?? [];
                if (! empty($contactIds)) {
                    $query->whereIn('id', $contactIds);
                }
                break;

            case 'advanced_filter':
                $filters = $this->campaign->target_filters['advanced'] ?? [];
                $query = $this->applyAdvancedFilters($query, $filters);
                break;
        }

        return $query;
    }

    /**
     * Apply saved segment filters to a query.
     */
    protected function applySegmentFilters($query, array $filters)
    {
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
            $query->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $filters['tag_ids']));
        }

        if (! empty($filters['list_ids'])) {
            $query->whereHas('lists', fn ($q) => $q->whereIn('lists.id', $filters['list_ids']));
        }

        return $query;
    }

    /**
     * Apply advanced filter criteria.
     */
    protected function applyAdvancedFilters($query, array $filters)
    {
        foreach ($filters as $filter) {
            $field = $filter['field'] ?? null;
            $operator = $filter['operator'] ?? '=';
            $value = $filter['value'] ?? null;

            if (! $field || $value === null) {
                continue;
            }

            $allowedFields = [
                'first_name', 'last_name', 'full_name', 'email',
                'company', 'job_title', 'district', 'city',
                'gender', 'source', 'status', 'country',
            ];

            if (! in_array($field, $allowedFields)) {
                continue;
            }

            switch ($operator) {
                case '=':
                case 'equals':
                    $query->where($field, $value);
                    break;
                case '!=':
                case 'not_equals':
                    $query->where($field, '!=', $value);
                    break;
                case 'contains':
                    $query->where($field, 'LIKE', "%{$value}%");
                    break;
                case 'starts_with':
                    $query->where($field, 'LIKE', "{$value}%");
                    break;
                case 'ends_with':
                    $query->where($field, 'LIKE', "%{$value}");
                    break;
                case 'in':
                    if (is_array($value)) {
                        $query->whereIn($field, $value);
                    }
                    break;
                case 'not_in':
                    if (is_array($value)) {
                        $query->whereNotIn($field, $value);
                    }
                    break;
            }
        }

        return $query;
    }
}
