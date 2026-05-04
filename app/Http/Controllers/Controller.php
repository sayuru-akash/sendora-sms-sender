<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Format a paginator into a consistent shape for Inertia/Vue consumption.
     * Returns { data: [...], meta: { current_page, last_page, per_page, total, from, to } }
     */
    protected function paginate(Builder|Relation $query, Request $request, int $defaultPerPage = 25): array
    {
        $paginator = $query->paginate($request->get('per_page', $defaultPerPage));

        return [
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ];
    }
}
