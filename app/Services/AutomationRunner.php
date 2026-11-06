<?php

namespace App\Services;

use App\Models\Automation;
use App\Models\AutomationLog;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\Widget;
use App\Support\SafeUrl;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

/**
 * Runs the owner's automations when something happens.
 * Triggers: chat_started, new_lead, hot_lead, chat_completed, ai_fallback, keyword_detected.
 * Actions: send_email, send_telegram, tag_lead, assign_priority, assign_agent, webhook, add_to_crm.
 */
class AutomationRunner
{
    public static function fire(string $trigger, Widget $widget, array $ctx = []): void
    {
        try {
            $rules = Automation::where('user_id', $widget->user_id)->where('is_active', true)->where('trigger', $trigger)
                ->where(fn ($q) => $q->whereNull('widget_id')->orWhere('widget_id', $widget->id))->get();
        } catch (\Throwable) {
            return;
        }
        foreach ($rules as $rule) {
            if ($trigger === 'keyword_detected' && ! self::keywordHit($rule, (string) ($ctx['message'] ?? ''))) continue;
            (new self)->run($rule, $widget, $ctx);
        }
    }

    private static function keywordHit(Automation $rule, string $message): bool
    {
        $msg = ' ' . mb_strtolower($message) . ' ';
        $keys = array_filter(array_map(fn ($k) => mb_strtolower(trim($k)), explode(',', (string) (($rule->trigger_conditions ?? [])['keywords'] ?? ''))));

        return $keys && collect($keys)->contains(fn ($k) => str_contains($msg, $k));
    }

    public function run(Automation $rule, Widget $widget, array $ctx, bool $dryRun = false): array
    {
        /** @var ?Lead $lead */ $lead = $ctx['lead'] ?? null;
        /** @var ?Conversation $conv */ $conv = $ctx['conversation'] ?? null;
        $cfg = (array) $rule->action_config;
        $data = array_filter([
            'event' => $rule->trigger, 'widget' => $widget->name, 'automation' => $rule->name,
            'lead' => $lead?->only(['id', 'name', 'email', 'phone', 'company', 'score', 'temperature', 'status']),
            'conversation' => $conv ? ['id' => $conv->uuid, 'url' => route('conversations.show', $conv), 'page' => $conv->current_page_url] : null,
            'message' => $ctx['message'] ?? null, 'at' => now()->toIso8601String(),
        ]);
        $status = 'success';
        $error = null;
        try {
            if (! $dryRun) {
                match ($rule->action) {
                    'send_email' => filled($cfg['email'] ?? null) ? Mail::raw(self::text($data), fn ($m) => $m->to($cfg['email'])->subject($cfg['subject'] ?? ('[' . $widget->name . '] ' . $rule->name))) : throw new \RuntimeException('No email address set.'),
                    'send_telegram' => self::telegram($rule, $cfg, self::text($data)),
                    'tag_lead' => $lead ? $lead->update(['tags' => array_values(array_unique(array_merge((array) $lead->tags, array_filter(array_map('trim', explode(',', (string) ($cfg['tags'] ?? '')))))))]) : throw new \RuntimeException('No lead in this event.'),
                    'assign_priority' => $lead ? $lead->update(['priority' => in_array($cfg['priority'] ?? 'high', ['high', 'medium', 'low'], true) ? ($cfg['priority'] ?? 'high') : 'high']) : throw new \RuntimeException('No lead in this event.'),
                    'assign_agent' => $conv && filled($cfg['agent_id'] ?? null) ? $conv->update(['assigned_agent_id' => (int) $cfg['agent_id']]) : throw new \RuntimeException('No conversation or agent.'),
                    'webhook' => self::webhook($rule, (string) ($cfg['url'] ?? ''), $data),
                    'add_to_crm' => self::webhook($rule, (string) ($cfg['url'] ?? $widget->webhook_url ?? ''), $data),
                    default => throw new \RuntimeException('Unknown action.'),
                };
            }
        } catch (\Throwable $e) {
            $status = 'failed';
            $error = mb_substr($e->getMessage(), 0, 500);
        }
        if (! $dryRun) {
            AutomationLog::create(['automation_id' => $rule->id, 'triggerable_type' => $lead ? Lead::class : ($conv ? Conversation::class : Widget::class), 'triggerable_id' => $lead?->id ?? $conv?->id ?? $widget->id,
                'status' => $status, 'error_message' => $error, 'payload' => $data]);
            $rule->increment('trigger_count');
            if ($status === 'success') $rule->increment('success_count');
            $rule->update(['last_triggered_at' => now()]);
        }

        return ['status' => $status, 'error' => $error, 'payload' => $data];
    }

    private static function text(array $d): string
    {
        $l = $d['lead'] ?? [];

        return "{$d['automation']} ({$d['event']}) on {$d['widget']}\n"
            . ($l ? "Lead: " . trim(($l['name'] ?? '') . ' ' . ($l['email'] ?? '') . ' ' . ($l['phone'] ?? '')) . " · score " . ($l['score'] ?? '-') . "\n" : '')
            . (! empty($d['message']) ? "Message: {$d['message']}\n" : '')
            . (! empty($d['conversation']['url']) ? "Open: {$d['conversation']['url']}\n" : '');
    }

    private static function telegram(Automation $rule, array $cfg, string $text): void
    {
        $token = $cfg['bot_token'] ?? config('services.telegram.bot_token');
        $chat = $cfg['chat_id'] ?? $rule->user?->telegram_chat_id;
        if (blank($token) || blank($chat)) throw new \RuntimeException('Set a Telegram bot token and chat ID.');
        Http::timeout(8)->post("https://api.telegram.org/bot{$token}/sendMessage", ['chat_id' => $chat, 'text' => $text])->throw();
    }

    private static function webhook(Automation $rule, string $url, array $data): void
    {
        if (! SafeUrl::isPublic($url)) throw new \RuntimeException('Webhook must be a public URL.');
        $body = json_encode($data);
        Http::timeout(8)->withHeaders(['X-PoriFyx-Signature' => hash_hmac('sha256', $body, (string) config('app.key')), 'Content-Type' => 'application/json'])
            ->withBody($body, 'application/json')->post($url)->throw();
    }
}
