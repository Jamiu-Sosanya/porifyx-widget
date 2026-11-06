<?php

namespace App\Support;

use App\Models\Widget;

/**
 * Per-widget lead form (widgets.lead_form JSON).
 * mode: off | pre_chat (before chatting) | first_message (after the first answer)
 *       | engagement (after two answers) | keyword (when the visitor mentions a keyword)
 */
class LeadForm
{
    public const MODES = [
        'engagement'    => 'After a couple of answers (recommended)',
        'first_message' => 'After the first answer',
        'pre_chat'      => 'Before the chat starts',
        'keyword'       => 'Only when the visitor mentions a keyword',
        'off'           => 'Never ask',
    ];

    public const TYPES = ['text', 'email', 'tel', 'textarea', 'select', 'checkbox'];

    public static function settings(Widget $widget): array
    {
        $s = is_array($widget->lead_form) ? $widget->lead_form : [];
        $fields = array_values(array_filter((array) ($s['fields'] ?? []), fn ($f) => is_array($f) && filled($f['key'] ?? null)));

        if (! $fields) {
            // Defaults follow the old "Collect email / phone" switches.
            $fields = [['key' => 'name', 'label' => 'Your name', 'type' => 'text', 'required' => false, 'options' => '']];
            $fields[] = ['key' => 'email', 'label' => 'Email address', 'type' => 'email', 'required' => true, 'options' => ''];
            if ($widget->collect_phone) $fields[] = ['key' => 'phone', 'label' => 'Phone number', 'type' => 'tel', 'required' => false, 'options' => ''];
        }

        return [
            'mode'     => isset(self::MODES[$s['mode'] ?? '']) ? $s['mode'] : ($widget->collect_email || $widget->collect_phone ? 'engagement' : 'off'),
            'title'    => $s['title'] ?? 'Where can we reach you?',
            'intro'    => $s['intro'] ?? 'Leave your details and the team will follow up.',
            'button'   => $s['button'] ?? 'Send',
            'keywords' => $s['keywords'] ?? 'quote, price, pricing, demo, call me, contact',
            'fields'   => $fields,
        ];
    }

    /** Validation rules for a visitor submission, keyed by field key. */
    public static function rules(array $settings): array
    {
        $rules = [];
        foreach ($settings['fields'] as $f) {
            $r = [$f['required'] ? 'required' : 'nullable'];
            $r[] = match ($f['type']) {
                'email'    => 'email:filter',
                'checkbox' => 'boolean',
                'textarea' => 'string',
                default    => 'string',
            };
            $r[] = $f['type'] === 'textarea' ? 'max:2000' : ($f['type'] === 'checkbox' ? 'max:1' : 'max:255');
            if ($f['type'] === 'select' && filled($f['options'])) {
                $r[] = 'in:' . implode(',', array_map(fn ($o) => str_replace(',', '', trim($o)), explode(',', $f['options'])));
            }
            $rules['fields.' . $f['key']] = $r;
        }

        return $rules;
    }

    /** Field key from a label: lowercase, a-z0-9 and underscores. */
    public static function key(string $label): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', '_', strtolower($label)), '_') ?: 'field';
    }
}
