<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\OfflineMessage;
use App\Models\Widget;
use App\Services\NotificationChannelService;
use App\Services\WidgetWebhookService;
use App\Support\LeadForm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Visitor submits the widget's lead form. One lead per widget + email (later submissions update it). */
class LeadCaptureController extends Controller
{
    public function store(Request $request, string $widgetUuid, NotificationChannelService $notify, WidgetWebhookService $webhooks): JsonResponse
    {
        $widget = Widget::forPublicApi($widgetUuid);
        $form = LeadForm::settings($widget);
        abort_if($form['mode'] === 'off', 404);

        $data = $request->validate(LeadForm::rules($form) + ['conversation_uuid' => 'nullable|string|max:64', 'fields' => 'array']);
        $fields = array_intersect_key((array) ($data['fields'] ?? []), array_flip(array_column($form['fields'], 'key')));
        $fields = array_map(fn ($v) => is_string($v) ? trim($v) : $v, $fields);

        $conversation = filled($data['conversation_uuid'] ?? null)
            ? Conversation::where('widget_id', $widget->id)->where('uuid', $data['conversation_uuid'])->first()
            : null;
        $email = filled($fields['email'] ?? null) ? mb_strtolower($fields['email']) : null;
        $owner = $widget->user;

        $lead = ($email ? Lead::where('widget_id', $widget->id)->where('email', $email)->first() : null)
            ?? ($conversation ? Lead::where('widget_id', $widget->id)->where('conversation_id', $conversation->id)->first() : null);

        // Monthly lead allowance used: keep the contact as a message so nothing is lost.
        if (! $lead && ! $owner->canUse('leads')) {
            OfflineMessage::create([
                'widget_id' => $widget->id, 'conversation_id' => $conversation?->id,
                'name' => $fields['name'] ?? null, 'email' => $email ?? '',
                'message' => collect($fields)->map(fn ($v, $k) => "$k: " . (is_bool($v) ? ($v ? 'yes' : 'no') : $v))->implode("\n"),
            ]);

            return response()->json(['ok' => true]);
        }

        $known = ['name' => $fields['name'] ?? null, 'email' => $email, 'phone' => $fields['phone'] ?? null, 'company' => $fields['company'] ?? null];
        $isNew = ! $lead;
        $lead ??= new Lead(['user_id' => $owner->id, 'widget_id' => $widget->id, 'status' => 'new', 'temperature' => 'warm', 'score' => 50]);
        $lead->fill(array_filter($known, fn ($v) => filled($v)));
        $lead->form_data = array_merge((array) $lead->form_data, $fields);
        $lead->conversation_id ??= $conversation?->id;
        $lead->visitor_id ??= $conversation?->visitor_id;
        $lead->last_engagement_at = now();
        $lead->save();

        if ($conversation?->visitor) {
            $conversation->visitor->update(array_filter(['name' => $known['name'], 'email' => $email, 'phone' => $known['phone']], fn ($v) => filled($v)));
        }

        if ($isNew) {
            if (! $widget->isPlaygroundRequest()) $owner->incrementUsage('leads');
            \App\Services\AutomationRunner::fire('new_lead', $widget, ['lead' => $lead, 'conversation' => $conversation]);
            try {
                $notify->notifyLeadQualified($widget, $lead);
                $webhooks->dispatch($widget, 'lead.captured', ['lead' => $lead->only(['id', 'name', 'email', 'phone', 'company', 'form_data', 'created_at'])]);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response()->json(['ok' => true]);
    }
}
