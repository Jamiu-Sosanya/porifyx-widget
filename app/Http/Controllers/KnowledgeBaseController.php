<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeBase;
use App\Models\TrainingSource;
use App\Models\Widget;
use App\Jobs\ProcessTrainingSource;
use Illuminate\Http\Request;

class KnowledgeBaseController extends Controller
{
    public function index(Request $request, Widget $widget)
    {
        $this->authorize('view', $widget);

        $knowledgeBases = $widget->knowledgeBases()
            ->withCount('chunks', 'trainingSources')
            ->latest()
            ->get();

        return view('knowledge.index', compact('widget', 'knowledgeBases'));
    }

    public function show(Widget $widget, KnowledgeBase $knowledgeBase)
    {
        $this->authorize('view', $widget);

        $knowledgeBase->load(['trainingSources' => fn ($q) => $q->orderBy('created_at', 'desc')]);

        return view('knowledge.show', compact('widget', 'knowledgeBase'));
    }

    public function store(Request $request, Widget $widget)
    {
        $this->authorize('update', $widget);

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $kb = $widget->knowledgeBases()->create([
            'user_id'     => $request->user()->id,
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('widgets.knowledge.show', [$widget, $kb])
            ->with('success', 'Knowledge base created! Add training sources to get started.');
    }

    public function addSource(Request $request, Widget $widget, KnowledgeBase $knowledgeBase)
    {
        $this->authorize('update', $widget);

        $validated = $request->validate([
            'type'       => 'required|in:url,pdf,text,faq,sitemap',
            'name'       => 'required|string|max:255',
            'content'    => 'required_if:type,text,faq|nullable|string',
            'source_url' => 'required_if:type,url,sitemap|nullable|url',
            'file'       => 'required_if:type,pdf|nullable|file|mimes:pdf|max:20480',
        ]);

        if (! $request->user()->canUse('training_sources')) {
            return back()->with('error', 'You have used all the training sources in your plan. Upgrade to add more.');
        }

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('training-sources/' . $widget->id, 'local');
        }

        $source = $knowledgeBase->trainingSources()->create([
            'user_id'    => $request->user()->id,
            'type'       => $validated['type'],
            'name'       => $validated['name'],
            'content'    => $validated['content'] ?? null,
            'source_url' => $validated['source_url'] ?? null,
            'file_path'  => $filePath,
        ]);

        // Dispatch async processing
        ProcessTrainingSource::dispatch($source);

        return back()->with('success', 'Training source added! Processing will begin shortly.');
    }

    public function deleteSource(Widget $widget, KnowledgeBase $knowledgeBase, TrainingSource $source)
    {
        $this->authorize('update', $widget);

        // Delete associated chunks
        $source->chunks()->delete();
        $source->delete();

        // Update KB stats
        $knowledgeBase->update([
            'total_chunks' => $knowledgeBase->chunks()->count(),
            'total_tokens' => $knowledgeBase->chunks()->sum('token_count'),
        ]);

        return back()->with('success', 'Training source removed.');
    }

    public function retrain(Widget $widget, KnowledgeBase $knowledgeBase)
    {
        $this->authorize('update', $widget);

        $knowledgeBase->update([
            'status'  => 'processing',
            'version' => $knowledgeBase->version + 1,
        ]);

        // Delete existing chunks so they are rebuilt fresh
        $knowledgeBase->chunks()->delete();

        // Dispatch a background job per source (same as addSource)
        $knowledgeBase->load('trainingSources');
        foreach ($knowledgeBase->trainingSources as $source) {
            $source->update(['status' => 'pending']);
            ProcessTrainingSource::dispatch($source);
        }

        return back()->with('success', 'Re-training queued! Sources will be processed in the background.');
    }

    public function activate(Widget $widget, KnowledgeBase $knowledgeBase)
    {
        $this->authorize('update', $widget);

        // Deactivate other KBs for this widget
        $widget->knowledgeBases()->update(['is_active' => false]);
        $knowledgeBase->update(['is_active' => true]);

        return back()->with('success', 'Knowledge base activated.');
    }

    public function destroy(Widget $widget, KnowledgeBase $knowledgeBase)
    {
        $this->authorize('update', $widget);

        $knowledgeBase->delete();

        return redirect()->route('widgets.knowledge.index', $widget)->with('success', 'Knowledge base deleted.');
    }
}
