<?php

namespace App\Http\Controllers;

use App\Http\Requests\SmsTemplateRequest;
use App\Models\SmsTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SmsTemplateController extends Controller
{
    public function index(Request $request): Response
    {
        $templates = SmsTemplate::with('creator')
            ->when($request->search, fn ($q, $search) => $q->search($search))
            ->when($request->category, fn ($q, $category) => $q->byCategory($category))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->get();

        return Inertia::render('Templates/Index', [
            'templates' => $templates,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Templates/Create');
    }

    public function store(SmsTemplateRequest $request)
    {
        $template = SmsTemplate::create([
            'name' => $request->name,
            'category' => $request->category,
            'body' => $request->body,
            'variables' => $request->variables,
            'status' => $request->status ?? 'active',
            'created_by' => $request->user()->id,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['template' => $template], 201);
        }

        return redirect()->route('templates.index')
            ->with('success', 'Template created successfully.');
    }

    public function show(SmsTemplate $template): Response
    {
        $template->load('creator');

        return Inertia::render('Templates/Show', [
            'template' => $template,
        ]);
    }

    public function edit(SmsTemplate $template): Response
    {
        return Inertia::render('Templates/Edit', [
            'template' => $template,
        ]);
    }

    public function update(SmsTemplateRequest $request, SmsTemplate $template)
    {
        $template->update([
            'name' => $request->name,
            'category' => $request->category,
            'body' => $request->body,
            'variables' => $request->variables,
            'status' => $request->status,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['template' => $template->fresh()]);
        }

        return redirect()->route('templates.index')
            ->with('success', 'Template updated successfully.');
    }

    public function destroy(Request $request, SmsTemplate $template)
    {
        $template->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Template deleted successfully.']);
        }

        return redirect()->route('templates.index')
            ->with('success', 'Template deleted successfully.');
    }

    /**
     * Duplicate a template.
     */
    public function duplicate(Request $request, SmsTemplate $template)
    {
        $newTemplate = $template->replicate();
        $newTemplate->name = $template->name.' (Copy)';
        $newTemplate->created_by = $request->user()->id;
        $newTemplate->save();

        if ($request->expectsJson()) {
            return response()->json(['template' => $newTemplate], 201);
        }

        return redirect()->route('templates.edit', $newTemplate)
            ->with('success', 'Template duplicated successfully.');
    }
}
