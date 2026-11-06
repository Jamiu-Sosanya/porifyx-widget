<?php

namespace App\Http\Controllers;

use App\Models\ChatFlow;
use App\Models\Widget;
use App\Services\FlowEngine;
use Illuminate\Http\Request;

/** Chat flow builder: list, edit, delete and a dry-run tester. */
class FlowController extends Controller
{
    public function index(Request $request, Widget $widget)
    {
        $this->authorize('update', $widget);
        $flows = ChatFlow::where('widget_id', $widget->id)->latest()->get();

        return view('widgets.flows', [
            'widget'  => $widget,
            'flows'   => $flows,
            'editing' => $request->get('edit') === 'new' ? null : $flows->firstWhere('id', $request->integer('edit')),
            'isNew'   => $request->get('edit') === 'new',
        ]);
    }

    public function save(Request $request, Widget $widget, FlowEngine $engine)
    {
        $this->authorize('update', $widget);
        $d = $request->validate([
            'id'               => 'nullable|integer',
            'name'             => 'required|string|max:120',
            'trigger_keywords' => 'required|string|max:500',
            'match_mode'       => 'required|in:any,all,exact',
            'steps'            => 'required|array|min:1|max:40',
            'steps.*.type'     => 'required|in:' . implode(',', array_keys(ChatFlow::STEP_TYPES)),
            'steps.*.text'     => 'nullable|string|max:1000',
            'steps.*.var'      => ['nullable', 'string', 'max:40', 'regex:/^[a-z0-9_]*$/i'],
            'steps.*.choices'  => 'nullable|string|max:300',
            'steps.*.op'       => 'nullable|in:' . implode(',', array_keys(ChatFlow::OPERATORS)),
            'steps.*.value'    => 'nullable|string|max:200',
            'steps.*.goto'     => 'nullable|integer|min:1|max:40',
            'steps.*.tag'      => 'nullable|string|max:30',
            'steps.*.url'      => 'nullable|url:https|max:500',
            'steps.*.x'        => 'nullable|integer|min:0|max:20000',
            'steps.*.y'        => 'nullable|integer|min:0|max:20000',
        ]);
        $steps = array_values(array_map(fn ($s) => array_intersect_key($s, array_flip(['type', 'text', 'var', 'choices', 'op', 'value', 'goto', 'tag', 'url', 'x', 'y'])), $d['steps']));

        $errors = [];
        foreach ($steps as $i => $s) {
            $n = $i + 1;
            if ($s['type'] === 'question' && blank($s['var'] ?? null)) $errors["steps.$i.var"] = "Step $n: name the answer (for example: budget) so later steps can use it.";
            if (in_array($s['type'], ['message', 'question']) && blank($s['text'] ?? null)) $errors["steps.$i.text"] = "Step $n needs text.";
            if ($s['type'] === 'branch' && (blank($s['var'] ?? null) || blank($s['goto'] ?? null) || ($s['goto'] ?? 0) > count($steps))) $errors["steps.$i.goto"] = "Step $n: pick the answer to check and a step that exists to jump to.";
            if ($s['type'] === 'webhook' && ! $engine->safeUrl((string) ($s['url'] ?? ''))) $errors["steps.$i.url"] = "Step $n: the webhook must be a public https address.";
        }
        if ($errors) return back()->withErrors($errors)->withInput();

        $flow = filled($d['id'] ?? null) ? ChatFlow::where('widget_id', $widget->id)->findOrFail($d['id']) : new ChatFlow(['widget_id' => $widget->id]);
        $flow->fill(['name' => $d['name'], 'trigger_keywords' => $d['trigger_keywords'], 'match_mode' => $d['match_mode'], 'steps' => $steps, 'is_active' => $request->boolean('is_active')])->save();

        return redirect()->route('widgets.flows', [$widget, 'edit' => $flow->id])->with('success', 'Flow saved.');
    }

    public function destroy(Widget $widget, ChatFlow $flow)
    {
        $this->authorize('update', $widget);
        abort_unless($flow->widget_id === $widget->id, 404);
        $flow->delete();

        return redirect()->route('widgets.flows', $widget)->with('success', 'Flow deleted.');
    }

    /** Dry run: no webhooks, no counters, nothing saved on a real conversation. */
    public function test(Request $request, Widget $widget, FlowEngine $engine)
    {
        $this->authorize('update', $widget);
        $d = $request->validate(['message' => 'required|string|max:500', 'state' => 'nullable|array']);
        $r = $engine->handle($widget, $d['state'] ?? null, $d['message'], true);

        return response()->json($r ?? ['reply' => null, 'choices' => [], 'state' => null, 'tags' => [], 'escalate' => false]);
    }
}
