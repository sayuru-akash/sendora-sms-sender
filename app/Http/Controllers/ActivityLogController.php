<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $request): Response
    {
        $request->merge([
            'per_page' => $this->perPage($request),
        ]);

        $activities = $this->paginate(
            Activity::with('causer')
                ->when($request->search, function ($q, $search) {
                    $search = '%'.mb_strtolower($search).'%';

                    $q->where(function ($query) use ($search) {
                        $query->whereRaw("LOWER(COALESCE(description, '')) LIKE ?", [$search])
                            ->orWhereRaw("LOWER(COALESCE(event, '')) LIKE ?", [$search])
                            ->orWhereRaw("LOWER(COALESCE(log_name, '')) LIKE ?", [$search])
                            ->orWhereHas('causer', fn ($causerQuery) => $causerQuery->whereRaw("LOWER(COALESCE(name, '')) LIKE ?", [$search])
                                ->orWhereRaw("LOWER(COALESCE(email, '')) LIKE ?", [$search]));

                        if ($query->getConnection()->getDriverName() === 'pgsql') {
                            $query->orWhereRaw('LOWER(properties::text) LIKE ?', [$search]);
                        } else {
                            $query->orWhereRaw("LOWER(COALESCE(properties, '')) LIKE ?", [$search]);
                        }
                    });
                })
                ->when($request->event, fn ($q, $event) => $q->where('event', $event))
                ->when($request->log_name, fn ($q, $logName) => $q->where('log_name', $logName))
                ->when($request->causer_id, fn ($q, $causerId) => $q->where('causer_id', $causerId))
                ->when($request->subject_type, fn ($q, $subjectType) => $q->where('subject_type', $subjectType))
                ->when($request->subject_id, fn ($q, $subjectId) => $q->where('subject_id', $subjectId))
                ->latest('id'),
            $request,
            25
        );
        $activities['data'] = collect($activities['data'])
            ->map(fn (Activity $activity): array => $this->formatActivity($activity))
            ->all();

        return Inertia::render('ActivityLogs/Index', [
            'activities' => $activities,
            'filters' => $request->only(['search', 'event', 'log_name', 'causer_id', 'subject_type', 'subject_id', 'per_page']),
            'filterOptions' => [
                'events' => $this->activityOptionValues('event'),
                'logNames' => $this->activityOptionValues('log_name'),
                'subjectTypes' => $this->activityOptionValues('subject_type'),
                'causers' => User::query()
                    ->whereIn('id', Activity::query()
                        ->where('causer_type', User::class)
                        ->whereNotNull('causer_id')
                        ->select('causer_id'))
                    ->orderBy('name')
                    ->limit(100)
                    ->get(['id', 'name', 'email'])
                    ->map(fn (User $user): array => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ])
                    ->all(),
                'perPage' => [10, 25, 50, 100],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function formatActivity(Activity $activity): array
    {
        return [
            'id' => $activity->id,
            'event' => $activity->event ?: 'activity',
            'description' => $activity->description,
            'subject_type' => $activity->subject_type,
            'subject_id' => $activity->subject_id,
            'subject_name' => self::subjectName($activity),
            'subject_url' => self::subjectUrl($activity),
            'subject_action_label' => self::subjectActionLabel($activity),
            'causer_id' => $activity->causer_id,
            'causer_name' => $activity->causer?->name,
            'causer_email' => $activity->causer?->email,
            'properties' => $activity->properties?->toArray() ?? [],
            'created_at' => $activity->created_at?->toISOString(),
        ];
    }

    private function perPage(Request $request): int
    {
        $perPage = $request->integer('per_page', 25);

        return in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;
    }

    /**
     * @return array<int, string>
     */
    private function activityOptionValues(string $column): array
    {
        return Activity::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->values()
            ->all();
    }

    private static function subjectName(Activity $activity): ?string
    {
        $properties = $activity->properties?->toArray() ?? [];

        return $properties['name'] ?? null;
    }

    private static function subjectUrl(Activity $activity): ?string
    {
        return match ($activity->subject_type) {
            'App\\Models\\SmsCampaign' => route('campaigns.show', [
                'campaign' => $activity->subject_id,
                'activity_id' => $activity->id,
            ]).'#activity',
            'App\\Models\\Contact' => route('contacts.show', $activity->subject_id),
            'App\\Models\\Import' => route('imports.show', $activity->subject_id),
            default => null,
        };
    }

    private static function subjectActionLabel(Activity $activity): ?string
    {
        return match ($activity->subject_type) {
            'App\\Models\\SmsCampaign' => 'Open campaign',
            'App\\Models\\Contact' => 'Open contact',
            'App\\Models\\Import' => 'Open import',
            default => null,
        };
    }
}
