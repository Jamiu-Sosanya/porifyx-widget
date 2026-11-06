<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessTrainingSource;
use App\Models\KnowledgeBase;
use App\Models\TrainingSource;
use App\Models\Widget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Content sync for CMS plugins (WordPress): push a page into the widget's knowledge base.
 * Auth: the widget's secret token (X-Widget-Token), shown in the widget's Security settings.
 * Same URL again = the page is replaced, so edits in the CMS update the assistant.
 */
class ContentSyncController extends Controller
{
    private function widget(Request $request, string $uuid): Widget
    {
        $w = Widget::where('uuid', $uuid)->firstOrFail();
        abort_unless(hash_equals((string) $w->token, (string) $request->header('X-Widget-Token')), 401, 'Invalid widget token.');

        return $w;
    }

    public function ping(Request $request, string $uuid): JsonResponse
    {
        $w = $this->widget($request, $uuid);

        return response()->json(['ok' => true, 'widget' => $w->name]);
    }

    public function upsert(Request $request, string $uuid): JsonResponse
    {
        $w = $this->widget($request, $uuid);
        $d = $request->validate([
            'url'     => 'required|url:http,https|max:2048',
            'title'   => 'required|string|max:255',
            'content' => 'required|string|max:200000',
        ]);

        $kb = $w->activeKnowledgeBase ?? KnowledgeBase::create(['user_id' => $w->user_id, 'widget_id' => $w->id, 'name' => $w->name . ' knowledge', 'status' => 'ready', 'is_active' => true]);
        $source = TrainingSource::where('knowledge_base_id', $kb->id)->where('source_url', $d['url'])->first();

        if (! $source && ! $w->user->canUse('training_sources')) {
            return response()->json(['ok' => false, 'error' => 'Training source limit reached on this plan.'], 402);
        }

        if ($source) {
            $source->chunks()->delete();
            $source->update(['name' => $d['title'], 'content' => $d['content'], 'status' => 'pending', 'error_message' => null]);
        } else {
            $source = TrainingSource::create([
                'knowledge_base_id' => $kb->id, 'user_id' => $w->user_id, 'type' => 'text',
                'name' => $d['title'], 'content' => $d['content'], 'source_url' => $d['url'], 'status' => 'pending',
            ]);
        }
        ProcessTrainingSource::dispatch($source);

        return response()->json(['ok' => true, 'source_id' => $source->id, 'updated' => $source->wasRecentlyCreated === false]);
    }

    public function remove(Request $request, string $uuid): JsonResponse
    {
        $w = $this->widget($request, $uuid);
        $url = (string) $request->validate(['url' => 'required|string|max:2048'])['url'];
        $kbIds = KnowledgeBase::where('widget_id', $w->id)->pluck('id');
        TrainingSource::whereIn('knowledge_base_id', $kbIds)->where('source_url', $url)->get()->each(function ($s) {
            $s->chunks()->delete();
            $s->delete();
        });

        return response()->json(['ok' => true]);
    }
}
