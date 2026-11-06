<?php

namespace App\Services;

use App\Models\ChatFlow;
use App\Models\Widget;
use Illuminate\Support\Facades\Http;

/**
 * Runs scripted chat flows. State lives with the conversation:
 * ['flow_id' => int, 'step' => int (index waiting for an answer), 'vars' => [name => value]].
 *
 * Steps: message{text} · question{text, var, choices} · branch{var, op, value, goto} (goto = step number, 1-based;
 * falls through when false) · tag{tag} · webhook{url} · escalate{text}. {{var}} placeholders are filled in.
 */
class FlowEngine
{
    public const MAX_JUMPS = 32; // loop guard per turn

    /**
     * Handle one visitor message. Returns null when no flow is running or triggered (the AI answers),
     * otherwise ['reply' => text, 'choices' => [...], 'state' => ?array, 'tags' => [...], 'escalate' => bool].
     */
    public function handle(Widget $widget, ?array $state, string $message, bool $dryRun = false): ?array
    {
        $flow = null;
        $vars = [];
        $index = 0;

        if ($state && ($flow = ChatFlow::where('widget_id', $widget->id)->where('is_active', true)->find($state['flow_id'] ?? 0))) {
            // Answer to the question we asked.
            $vars = (array) ($state['vars'] ?? []);
            $q = $flow->steps[$state['step']] ?? null;
            if ($q && ($q['type'] ?? '') === 'question' && filled($q['var'] ?? null)) $vars[$q['var']] = mb_substr(trim($message), 0, 500);
            $index = ($state['step'] ?? 0) + 1;
        } else {
            $flow = ChatFlow::where('widget_id', $widget->id)->where('is_active', true)->get()->first(fn (ChatFlow $f) => $f->matches($message));
            if (! $flow) return null;
            if (! $dryRun) $flow->increment('runs');
        }

        return $this->run($flow, $index, $vars, $dryRun);
    }

    public function run(ChatFlow $flow, int $index, array $vars, bool $dryRun = false): array
    {
        $out = ['reply' => '', 'choices' => [], 'state' => null, 'tags' => [], 'escalate' => false];
        $lines = [];
        $steps = array_values($flow->steps ?? []);

        for ($jumps = 0; $index < count($steps) && $jumps < self::MAX_JUMPS; $jumps++) {
            $s = $steps[$index];
            $text = $this->fill((string) ($s['text'] ?? ''), $vars);

            switch ($s['type'] ?? '') {
                case 'message':
                    if ($text !== '') $lines[] = $text;
                    $index++;
                    break;
                case 'question':
                    if ($text !== '') $lines[] = $text;
                    $out['choices'] = array_values(array_filter(array_map('trim', explode(',', (string) ($s['choices'] ?? '')))));
                    $out['state'] = ['flow_id' => $flow->id, 'step' => $index, 'vars' => $vars];
                    $out['reply'] = implode("\n\n", $lines);
                    return $out; // wait for the visitor
                case 'branch':
                    $goto = (int) ($s['goto'] ?? 0) - 1;
                    $index = $this->test($vars[$s['var'] ?? ''] ?? '', $s['op'] ?? 'equals', (string) ($s['value'] ?? '')) && $goto >= 0 ? $goto : $index + 1;
                    break;
                case 'tag':
                    if (filled($s['tag'] ?? null)) $out['tags'][] = mb_substr($this->fill($s['tag'], $vars), 0, 30);
                    $index++;
                    break;
                case 'webhook':
                    if (! $dryRun && $this->safeUrl((string) ($s['url'] ?? ''))) {
                        try {
                            Http::timeout(4)->asJson()->post($s['url'], ['flow' => $flow->name, 'widget_id' => $flow->widget_id, 'variables' => $vars]);
                        } catch (\Throwable $e) {
                            report($e);
                        }
                    }
                    $index++;
                    break;
                case 'escalate':
                    $lines[] = $text !== '' ? $text : 'Connecting you with our team now.';
                    $out['escalate'] = true;
                    $index = count($steps); // flow ends here
                    break;
                default:
                    $index++;
            }
        }

        $out['reply'] = implode("\n\n", $lines);

        return $out;
    }

    private function fill(string $text, array $vars): string
    {
        return preg_replace_callback('/\{\{\s*([a-z0-9_]+)\s*\}\}/i', fn ($m) => (string) ($vars[$m[1]] ?? ''), $text);
    }

    private function test(string $v, string $op, string $expected): bool
    {
        $v = mb_strtolower(trim($v));
        $e = mb_strtolower(trim($expected));

        return match ($op) {
            'contains'    => $e !== '' && str_contains($v, $e),
            'starts_with' => $e !== '' && str_starts_with($v, $e),
            'is_empty'    => $v === '',
            'not_empty'   => $v !== '',
            default       => $v === $e,
        };
    }

    /** Public https URLs only (no localhost or private network addresses). */
    public function safeUrl(string $url): bool
    {
        return \App\Support\SafeUrl::isPublic($url, false);
    }
}
