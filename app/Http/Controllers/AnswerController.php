<?php

namespace App\Http\Controllers;

use App\Models\CuratedAnswer;
use App\Models\KnowledgeGap;
use App\Models\Widget;
use Illuminate\Http\Request;

/** Curated answers (hand-written replies) and the questions the AI could not answer. */
class AnswerController extends Controller
{
    public function index(Widget $widget)
    {
        $this->authorize('update', $widget);

        return view('widgets.answers', [
            'widget'  => $widget,
            'answers' => CuratedAnswer::where('widget_id', $widget->id)->latest()->get(),
            'gaps'    => KnowledgeGap::where('widget_id', $widget->id)->where('status', 'open')->orderByDesc('times_asked')->orderByDesc('last_asked_at')->limit(100)->get(),
        ]);
    }

    public function store(Request $request, Widget $widget)
    {
        $this->authorize('update', $widget);
        $data = $this->validated($request);
        CuratedAnswer::create($data + ['widget_id' => $widget->id]);

        if ($request->filled('gap_id')) {
            KnowledgeGap::where('widget_id', $widget->id)->whereKey($request->integer('gap_id'))->update(['status' => 'answered']);
        }

        return back()->with('success', 'Answer saved. The assistant will use it word for word.');
    }

    public function update(Request $request, Widget $widget, CuratedAnswer $answer)
    {
        $this->authorize('update', $widget);
        abort_unless($answer->widget_id === $widget->id, 404);
        $answer->update($this->validated($request) + ['is_active' => $request->boolean('is_active')]);

        return back()->with('success', 'Answer updated.');
    }

    public function destroy(Widget $widget, CuratedAnswer $answer)
    {
        $this->authorize('update', $widget);
        abort_unless($answer->widget_id === $widget->id, 404);
        $answer->delete();

        return back()->with('success', 'Answer deleted.');
    }

    public function dismissGap(Widget $widget, KnowledgeGap $gap)
    {
        $this->authorize('update', $widget);
        abort_unless($gap->widget_id === $widget->id, 404);
        $gap->update(['status' => 'dismissed']);

        return back()->with('success', 'Question dismissed.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'question' => 'required|string|max:255',
            'keywords' => 'nullable|string|max:255',
            'answer'   => 'required|string|max:4000',
        ]);
    }
}
