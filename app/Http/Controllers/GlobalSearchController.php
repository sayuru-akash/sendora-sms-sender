<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\ListModel;
use App\Models\SmsCampaign;
use App\Models\SmsTemplate;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class GlobalSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $query = trim((string) ($validated['q'] ?? ''));

        if (mb_strlen($query) < 2) {
            return response()->json([
                'query' => $query,
                'groups' => [],
            ]);
        }

        return response()->json([
            'query' => $query,
            'groups' => array_values(array_filter([
                $this->contacts($query),
                $this->campaigns($query),
                $this->templates($query),
                $this->lists($query),
                $this->tags($query),
                $this->activities($query),
            ], fn (array $group): bool => ! empty($group['items']))),
        ]);
    }

    /**
     * @return array{label: string, items: array<int, array<string, mixed>>}
     */
    private function contacts(string $query): array
    {
        return [
            'label' => 'Contacts',
            'items' => Contact::query()
                ->select(['id', 'full_name', 'phone_normalised', 'email', 'status'])
                ->search($query)
                ->orderBy('full_name')
                ->limit(5)
                ->get()
                ->map(fn (Contact $contact): array => [
                    'id' => 'contact-'.$contact->id,
                    'title' => $contact->display_name,
                    'subtitle' => trim(collect([$contact->phone_normalised, $contact->email])->filter()->join(' · ')),
                    'badge' => $contact->status,
                    'url' => route('contacts.show', $contact),
                ])
                ->all(),
        ];
    }

    /**
     * @return array{label: string, items: array<int, array<string, mixed>>}
     */
    private function campaigns(string $query): array
    {
        return [
            'label' => 'Campaigns',
            'items' => SmsCampaign::query()
                ->select(['id', 'name', 'status', 'total_recipients', 'sent_count', 'failed_count'])
                ->search($query)
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (SmsCampaign $campaign): array => [
                    'id' => 'campaign-'.$campaign->id,
                    'title' => $campaign->name,
                    'subtitle' => "{$campaign->sent_count}/{$campaign->total_recipients} sent · {$campaign->failed_count} failed",
                    'badge' => $campaign->status,
                    'url' => route('campaigns.show', $campaign),
                ])
                ->all(),
        ];
    }

    /**
     * @return array{label: string, items: array<int, array<string, mixed>>}
     */
    private function templates(string $query): array
    {
        return [
            'label' => 'Templates',
            'items' => SmsTemplate::query()
                ->select(['id', 'name', 'category', 'status'])
                ->search($query)
                ->orderBy('name')
                ->limit(5)
                ->get()
                ->map(fn (SmsTemplate $template): array => [
                    'id' => 'template-'.$template->id,
                    'title' => $template->name,
                    'subtitle' => $template->category ?? 'Template',
                    'badge' => $template->status,
                    'url' => route('templates.show', $template),
                ])
                ->all(),
        ];
    }

    /**
     * @return array{label: string, items: array<int, array<string, mixed>>}
     */
    private function lists(string $query): array
    {
        return [
            'label' => 'Lists',
            'items' => ListModel::query()
                ->select(['id', 'name', 'description', 'status'])
                ->search($query)
                ->orderBy('name')
                ->limit(5)
                ->get()
                ->map(fn (ListModel $list): array => [
                    'id' => 'list-'.$list->id,
                    'title' => $list->name,
                    'subtitle' => $list->description ?? 'Contact list',
                    'badge' => $list->status,
                    'url' => route('lists.show', $list),
                ])
                ->all(),
        ];
    }

    /**
     * @return array{label: string, items: array<int, array<string, mixed>>}
     */
    private function tags(string $query): array
    {
        return [
            'label' => 'Tags',
            'items' => Tag::query()
                ->select(['id', 'name', 'description'])
                ->search($query)
                ->orderBy('name')
                ->limit(5)
                ->get()
                ->map(fn (Tag $tag): array => [
                    'id' => 'tag-'.$tag->id,
                    'title' => $tag->name,
                    'subtitle' => $tag->description ?? 'Contact tag',
                    'badge' => 'tag',
                    'url' => route('tags.show', $tag),
                ])
                ->all(),
        ];
    }

    /**
     * @return array{label: string, items: array<int, array<string, mixed>>}
     */
    private function activities(string $query): array
    {
        $search = '%'.mb_strtolower($query).'%';

        return [
            'label' => 'Activity',
            'items' => Activity::query()
                ->where(function ($activityQuery) use ($search): void {
                    $activityQuery->whereRaw("LOWER(COALESCE(description, '')) LIKE ?", [$search])
                        ->orWhereRaw("LOWER(COALESCE(event, '')) LIKE ?", [$search]);
                })
                ->latest('id')
                ->limit(3)
                ->get()
                ->map(fn (Activity $activity): array => [
                    'id' => 'activity-'.$activity->id,
                    'title' => $activity->description,
                    'subtitle' => str_replace('_', ' ', $activity->event ?? 'activity'),
                    'badge' => 'log',
                    'url' => route('activity-logs.index', ['search' => $query]),
                ])
                ->all(),
        ];
    }
}
