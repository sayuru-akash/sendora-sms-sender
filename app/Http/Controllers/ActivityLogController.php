<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $request): Response
    {
        $activities = $this->paginate(
            Activity::with('causer')
                ->when($request->search, function ($q, $search) {
                    $q->where(function ($query) use ($search) {
                        $query->where('description', 'ilike', "%{$search}%")
                            ->orWhere('log_name', 'ilike', "%{$search}%");
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
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function formatActivity(Activity $activity): array
    {
        return [
            'id' => $activity->id,
            'event' => $activity->event,
            'description' => $activity->description,
            'subject_type' => $activity->subject_type,
            'subject_id' => $activity->subject_id,
            'subject_name' => self::subjectName($activity),
            'subject_url' => self::subjectUrl($activity),
            'causer_id' => $activity->causer_id,
            'causer_name' => $activity->causer?->name,
            'properties' => $activity->properties?->toArray() ?? [],
            'created_at' => $activity->created_at,
        ];
    }

    private static function subjectName(Activity $activity): ?string
    {
        $properties = $activity->properties?->toArray() ?? [];

        return $properties['name'] ?? null;
    }

    private static function subjectUrl(Activity $activity): ?string
    {
        return match ($activity->subject_type) {
            'App\\Models\\SmsCampaign' => route('campaigns.show', $activity->subject_id),
            'App\\Models\\Contact' => route('contacts.show', $activity->subject_id),
            'App\\Models\\Import' => route('imports.show', $activity->subject_id),
            default => null,
        };
    }
}
