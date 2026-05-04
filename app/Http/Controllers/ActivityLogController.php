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
                    $q->where('description', 'ilike', "%{$search}%")
                        ->orWhere('log_name', 'ilike', "%{$search}%");
                })
                ->when($request->event, fn ($q, $event) => $q->where('event', $event))
                ->when($request->log_name, fn ($q, $logName) => $q->where('log_name', $logName))
                ->when($request->causer_id, fn ($q, $causerId) => $q->where('causer_id', $causerId))
                ->latest(),
            $request,
            25
        );

        return Inertia::render('ActivityLogs/Index', [
            'activities' => $activities,
            'filters' => $request->only(['search', 'event', 'log_name', 'causer_id', 'per_page']),
        ]);
    }
}
