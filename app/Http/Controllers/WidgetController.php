<?php

namespace App\Http\Controllers;

use App\Models\WebhookLog;
use App\Models\Widget;
use App\Services\WidgetWebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WidgetController extends Controller
{
    public function index(Request $request)
    {
        $widgets = $request->user()->widgets()
            ->withCount(['conversations', 'leads'])
            ->latest()
            ->paginate(12);

        return view('widgets.index', compact('widgets'));
    }

    public function create()
    {
        return view('widgets.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'ai_name'          => 'nullable|string|max:100',
            'tone'             => 'nullable|in:friendly,professional,sales,support',
            'primary_color'    => 'nullable|string|max:20',
            'welcome_message'  => 'nullable|string|max:500',
        ]);

        $user = $request->user();

        // Check widget limit
        if (! $user->canUse('widgets')) {
            return back()->with('error', 'You have reached your widget limit. Please upgrade your plan.');
        }

        $widget = $user->widgets()->create([
            'name'            => $validated['name'],
            'ai_name'         => $validated['ai_name'] ?? 'AI Assistant',
            'tone'            => $validated['tone'] ?? 'friendly',
            'primary_color'   => $validated['primary_color'] ?? '#ff6a2b',
            'welcome_message' => $validated['welcome_message'] ?? 'Hi! How can I help you today?',
        ]);

        return redirect()->route('widgets.edit', $widget)->with('success', 'Widget created successfully!');
    }

    public function show(Widget $widget)
    {
        $this->authorize('view', $widget);

        $widget->load([
            'knowledgeBases',
            'conversations' => fn ($q) => $q->with('visitor')->latest()->limit(12),
        ]);

        $stats = [
            'conversations_total'  => $widget->conversations()->count(),
            'conversations_active' => $widget->conversations()->whereIn('status', ['open', 'ai_active', 'human_active'])->count(),
            'knowledge_bases'      => $widget->knowledgeBases()->count(),
            'avg_csat'             => ($v = $widget->conversations()->whereNotNull('csat_score')->avg('csat_score')) !== null
                ? round((float) $v, 2)
                : null,
            'csat_count'           => $widget->conversations()->whereNotNull('csat_score')->count(),
            'leads_total'          => $widget->conversations()->has('lead')->count(),
            'resolved_total'       => $widget->conversations()->where('status', 'resolved')->count(),
        ];

        $trendRaw = $widget->conversations()
            ->where('created_at', '>=', now()->subDays(29))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        $trendLabels = [];
        $trendData   = [];
        for ($i = 29; $i >= 0; $i--) {
            $day = now()->subDays($i)->format('Y-m-d');
            $trendLabels[] = now()->subDays($i)->format('M d');
            $trendData[]   = (int) ($trendRaw[$day] ?? 0);
        }

        $convChart = ['labels' => $trendLabels, 'data' => $trendData];

        return view('widgets.show', compact('widget', 'stats', 'convChart'));
    }

    public function edit(Widget $widget)
    {
        $this->authorize('update', $widget);

        $widget->load(['knowledgeBases.trainingSources', 'activeKnowledgeBase']);

        $channelConfigurations = $widget->channelConfigurations()
            ->orderBy('channel_type')
            ->get();

        $plan = request()->user()->currentPlan();
        $channelLimit = (int) (($plan?->features['channels_per_widget'] ?? null) ?? 1);

        return view('widgets.edit', compact('widget', 'channelConfigurations', 'channelLimit'));
    }

    public function update(Request $request, Widget $widget)
    {
        $this->authorize('update', $widget);

        $validated = $request->validate([
            'name'                      => 'required|string|max:255',
            'ai_name'                   => 'nullable|string|max:100',
            'tone'                      => 'nullable|in:friendly,professional,sales,support',
            'primary_color'             => 'nullable|string|max:20',
            'secondary_color'           => 'nullable|string|max:20',
            'text_color'                => 'nullable|string|max:20',
            'bg_color'                  => 'nullable|string|max:20',
            'position'                  => 'nullable|in:bottom-right,bottom-left,top-right,top-left',
            'launcher_shape'            => 'nullable|in:circle,rounded,square,custom',
            'launcher'                  => 'nullable|array',
            'launcher.size'             => 'nullable|integer|between:40,96',
            'launcher.radius'           => 'nullable|integer|between:0,48',
            'launcher.bg_type'          => 'nullable|in:solid,gradient',
            'launcher.bg'               => 'nullable|regex:/^#[0-9a-fA-F]{6}$/',
            'launcher.bg2'              => 'nullable|regex:/^#[0-9a-fA-F]{6}$/',
            'launcher.angle'            => 'nullable|integer|between:0,360',
            'launcher.icon_color'       => 'nullable|regex:/^#[0-9a-fA-F]{6}$/',
            'launcher.border_width'     => 'nullable|integer|between:0,8',
            'launcher.border_color'     => 'nullable|regex:/^#[0-9a-fA-F]{6}$/',
            'launcher.shadow'           => 'nullable|in:none,soft,medium,strong',
            'launcher.offset_x'         => 'nullable|integer|between:0,120',
            'launcher.offset_y'         => 'nullable|integer|between:0,120',
            'launcher.label'            => 'nullable|string|max:40',
            'launcher.chat_w'           => 'nullable|integer|between:300,520',
            'launcher.chat_h'           => 'nullable|integer|between:400,760',
            'launcher.m_mode'           => 'nullable|in:full,custom',
            'launcher.m_w'              => 'nullable|integer|between:70,100',
            'launcher.m_h'              => 'nullable|integer|between:50,100',
            'launcher.m_size'           => 'nullable|integer|between:40,72',
            'launcher.bubble_shape'     => 'nullable|in:rounded,soft,square,pill',
            'launcher.bubble_tail'      => 'nullable|boolean',
            'launcher.ai_bg'            => 'nullable|regex:/^#[0-9a-fA-F]{6}$/',
            'launcher.ai_text'          => 'nullable|regex:/^#[0-9a-fA-F]{6}$/',
            'launcher.user_bg'          => 'nullable|regex:/^#[0-9a-fA-F]{6}$/',
            'launcher.user_text'        => 'nullable|regex:/^#[0-9a-fA-F]{6}$/',
            'launcher.bubble_size'      => 'nullable|integer|between:12,16',
            'launcher.typing_style'     => 'nullable|in:dots,wave,pulse,text',
            'launcher.animation'        => ['nullable', \Illuminate\Validation\Rule::in(array_keys(\App\Support\LauncherIcons::ANIMATIONS))],
            'launcher_icon'             => ['nullable', \Illuminate\Validation\Rule::in([...array_keys(\App\Support\LauncherIcons::PRESETS), 'custom'])],
            'launcher_icon_file'        => 'nullable|' . \App\Support\LauncherIcons::UPLOAD_RULE,
            'font_family'               => 'nullable|string|max:100',
            'welcome_message'           => 'nullable|string|max:500',
            'suggested_replies_input'   => 'nullable|string|max:2000',
            // Editor fields that previously were never saved
            'theme'                     => 'nullable|in:dark,light,minimal,bold,ocean,nature',
            'ai_avatar'                 => 'nullable|image|mimes:jpg,jpeg,png|max:1024',
            'conversation_starters_input' => 'nullable|string|max:1000',
            'auto_language_detection'   => 'nullable|boolean',
            'smart_suggestions_enabled' => 'nullable|boolean',
            'smart_suggestions_count'   => 'nullable|integer|min:2|max:5',
            'proactive_enabled'         => 'nullable|boolean',
            'proactive_message'         => 'nullable|string|max:500',
            'proactive_trigger_seconds' => 'nullable|integer|min:5|max:300',
            'proactive_trigger_pages'   => 'nullable|integer|min:1|max:20',
            'offline_form_message'      => 'nullable|string|max:500',
            'notification_email'        => 'nullable|email|max:255',
            'slack_webhook_url'         => 'nullable|url:https|max:500',
            'discord_webhook_url'       => 'nullable|url:https|max:500',
            'placeholder_text'          => 'nullable|string|max:200',
            'show_branding'             => 'nullable|boolean',
            'auto_open'                 => 'nullable|boolean',
            'auto_open_delay'           => 'nullable|integer|min:0|max:60',
            'show_on_mobile'            => 'nullable|boolean',
            'collect_email'             => 'nullable|boolean',
            'collect_phone'             => 'nullable|boolean',
            'file_upload_enabled'       => 'nullable|boolean',
            'voice_input_enabled'       => 'nullable|boolean',
            'emoji_enabled'             => 'nullable|boolean',
            'typing_animation'          => 'nullable|boolean',
            'message_streaming'         => 'nullable|boolean',
            'show_handoff_button'       => 'nullable|boolean',
            'handoff_button_label'      => 'nullable|string|max:80',
            'handoff_acknowledgment'    => 'nullable|string|max:500',
            'temperature'               => 'nullable|numeric|min:0|max:2',
            'response_length'           => 'nullable|in:short,medium,long',
            'strict_mode'               => 'nullable|boolean',
            'show_sources'              => 'nullable|boolean',
            'system_prompt'             => 'nullable|string|max:5000',
            'lead_qualification_enabled'=> 'nullable|boolean',
            'domain_restriction'        => 'nullable|boolean',
            'allowed_domains'           => 'nullable|string',
            'rate_limit'                => 'nullable|integer|min:1|max:200',
            'is_active'                 => 'nullable|boolean',
            'webhook_enabled'           => 'nullable|boolean',
            'webhook_url'               => 'nullable|url|max:2048',
            'webhook_secret'            => 'nullable|string|max:128',
            'webhook_lead_always'       => 'nullable|boolean',
            'sound_enabled'             => 'nullable|boolean',
            'offline_form_enabled'      => 'nullable|boolean',
            'csat_prompt_enabled'       => 'nullable|boolean',
            'welcome_messages'          => 'nullable|array',
            'welcome_messages.*'        => 'nullable|string|max:500',
            'placeholder_texts'         => 'nullable|array',
            'placeholder_texts.*'       => 'nullable|string|max:200',
            'conversation_starters_multi' => 'nullable|array',
            'conversation_starters_multi.*' => 'nullable|string',
            'offline_form_messages'     => 'nullable|array',
            'offline_form_messages.*'   => 'nullable|string|max:500',
            'ai_provider'               => 'nullable|in:' . implode(',', array_keys(\App\Services\MultiProviderAiService::providers())),
        ]);

        // Handle boolean fields
        $boolFields = [
            // Only fields with a checkbox in the editor: an unchecked box is absent from the request,
            // so listing a field without a checkbox would switch it off on every save.
            // (is_active has its own toggle; voice input lives on the Engagement page.)
            'show_branding', 'auto_open', 'show_on_mobile', 'collect_email',
            'collect_phone', 'emoji_enabled', 'typing_animation', 'message_streaming',
            'show_handoff_button', 'sound_enabled', 'offline_form_enabled',
            'auto_language_detection', 'smart_suggestions_enabled', 'proactive_enabled',
            'webhook_enabled', 'csat_prompt_enabled',
            'strict_mode', 'show_sources', 'lead_qualification_enabled', 'domain_restriction',
        ];

        foreach ($boolFields as $field) {
            $validated[$field] = $request->boolean($field);
        }

        // Model comes from the select belonging to the chosen provider.
        if (! empty($validated['ai_provider'])) {
            $validated['ai_model'] = substr((string) $request->input('ai_model_' . $validated['ai_provider']), 0, 80) ?: null;
        }

        // Handle allowed domains
        if (isset($validated['allowed_domains'])) {
            $validated['allowed_domains'] = array_values(array_filter(
                array_map(function ($d) {
                    $d = trim($d);
                    $d = preg_replace('#^https?://#', '', $d);
                    return rtrim(strtolower($d), '/');
                }, explode("\n", $validated['allowed_domains']))
            ));
        }

        // Conversation starters: one per line, max 4.
        if (array_key_exists('conversation_starters_input', $validated)) {
            $validated['conversation_starters'] = array_values(array_slice(array_filter(array_map('trim', preg_split('/\R/', (string) $validated['conversation_starters_input']))), 0, 4));
            unset($validated['conversation_starters_input']);
        }
        // Assistant avatar upload (removed from $validated: stored path saved instead).
        unset($validated['ai_avatar']);
        if ($request->hasFile('ai_avatar')) {
            $validated['ai_avatar'] = $request->file('ai_avatar')->store('avatars', 'public');
        }

        $lines = preg_split('/\r\n|\r|\n/', (string) ($validated['suggested_replies_input'] ?? ''));
        unset($validated['suggested_replies_input']);
        $validated['suggested_replies'] = array_values(array_slice(
            array_filter(array_map('trim', $lines), fn ($l) => $l !== ''),
            0,
            12
        ));

        // Handle multi-language conversation starters
        if (isset($validated['conversation_starters_multi'])) {
            foreach ($validated['conversation_starters_multi'] as $lang => $text) {
                if (is_string($text)) {
                    $lines = preg_split('/\r\n|\r|\n/', $text);
                    $validated['conversation_starters_multi'][$lang] = array_values(array_slice(
                        array_filter(array_map('trim', $lines), fn ($l) => $l !== ''),
                        0,
                        6
                    ));
                }
            }
        }

        // Launcher icon: a new upload switches to "custom"; choosing a preset or removing drops the old file.
        unset($validated['launcher_icon_file']);
        if (isset($validated['launcher'])) {
            $validated['launcher']['bubble_tail'] = $request->boolean('launcher.bubble_tail');
            foreach (['ai_bg', 'ai_text', 'user_bg'] as $k) {
                if ($request->boolean('launcher.' . $k . '_auto')) {
                    $validated['launcher'][$k] = '';
                }
            }
            $validated['launcher'] = array_replace(\App\Support\LauncherIcons::style($widget), array_filter($validated['launcher'], fn ($v, $k) => $v !== null || in_array($k, ['ai_bg', 'ai_text', 'user_bg'], true), ARRAY_FILTER_USE_BOTH), ['label' => (string) ($validated['launcher']['label'] ?? '')]);
        }
        if ($request->hasFile('launcher_icon_file')) {
            if ($widget->launcher_icon_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($widget->launcher_icon_path);
            }
            $validated['launcher_icon_path'] = $request->file('launcher_icon_file')->store('launcher-icons', 'public');
            $validated['launcher_icon'] = 'custom';
        } elseif (($validated['launcher_icon'] ?? null) === 'custom' && ! $widget->launcher_icon_path) {
            $validated['launcher_icon'] = 'default';
        } elseif (isset($validated['launcher_icon']) && $validated['launcher_icon'] !== 'custom' && $widget->launcher_icon_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($widget->launcher_icon_path);
            $validated['launcher_icon_path'] = null;
        }

        if (trim((string) ($validated['webhook_secret'] ?? '')) === '') {
            unset($validated['webhook_secret']);
        }

        $widget->update($validated);

        return back()->with('success', 'Widget updated successfully!');
    }

    public function destroy(Widget $widget)
    {
        $this->authorize('delete', $widget);

        $widget->delete();

        return redirect()->route('widgets.index')->with('success', 'Widget deleted.');
    }

    public function duplicate(Widget $widget)
    {
        $this->authorize('view', $widget);

        $newWidget = $widget->replicate();
        $newWidget->name = $widget->name . ' (Copy)';
        $newWidget->uuid = (string) Str::uuid();
        $newWidget->token = Str::random(48);
        $newWidget->total_conversations = 0;
        $newWidget->total_messages = 0;
        $newWidget->total_leads = 0;
        $newWidget->save();

        return redirect()->route('widgets.edit', $newWidget)->with('success', 'Widget duplicated!');
    }

    public function toggleActive(Widget $widget)
    {
        $this->authorize('update', $widget);

        $widget->update(['is_active' => !$widget->is_active]);

        return back()->with('success', $widget->is_active ? 'Widget activated.' : 'Widget deactivated.');
    }

    public function regenerateToken(Widget $widget)
    {
        $this->authorize('update', $widget);

        $widget->regenerateToken();

        return back()->with('success', 'Widget token regenerated.');
    }

    /** Test the widget for real before launch: works while inactive, messages are not counted. */
    public function playground(Widget $widget)
    {
        $this->authorize('update', $widget);

        return view('widgets.playground', ['widget' => $widget, 'plan' => $widget->planState()]);
    }

    /** WordPress plugin zip, built from wp-plugin/porifyx-widget. */
    public function wordpressPlugin(Widget $widget)
    {
        $this->authorize('view', $widget);
        $dir = base_path('wp-plugin/porifyx-widget');
        $zipPath = tempnam(sys_get_temp_dir(), 'pfxwp');
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::OVERWRITE);
        foreach (glob($dir . '/*') as $file) {
            $zip->addFile($file, 'porifyx-widget/' . basename($file));
        }
        $zip->close();

        return response()->download($zipPath, 'porifyx-widget-wordpress.zip')->deleteFileAfterSend();
    }

    public function embedCode(Widget $widget)
    {
        $this->authorize('view', $widget);

        return view('widgets.embed', compact('widget'));
    }

    public function testWebhook(Request $request, Widget $widget, WidgetWebhookService $webhooks)
    {
        $this->authorize('update', $widget);

        if (! $widget->webhook_enabled || ! $widget->webhook_url) {
            return back()->with('error', 'Enable webhooks and save a webhook URL first.');
        }

        $webhooks->dispatch($widget, 'webhook.test', [
            'message'      => 'Test payload from PoriFyx Widget',
            'triggered_by' => $request->user()->email,
        ]);

        return back()->with('success', 'Test webhook queued. Check your endpoint logs (queue driver: '.config('queue.default').').');
    }

    public function webhookLogs(Request $request, Widget $widget)
    {
        $this->authorize('view', $widget);

        $logs = WebhookLog::forWidget($widget->id)
            ->recent()
            ->paginate(30);

        $stats = [
            'total'   => WebhookLog::forWidget($widget->id)->count(),
            'success' => WebhookLog::forWidget($widget->id)->where('status', 'success')->count(),
            'failed'  => WebhookLog::forWidget($widget->id)->where('status', 'failed')->count(),
            'avg_ms'  => (int) WebhookLog::forWidget($widget->id)->whereNotNull('response_time_ms')->avg('response_time_ms'),
        ];

        return view('widgets.webhook-logs', compact('widget', 'logs', 'stats'));
    }

    public function clearWebhookLogs(Widget $widget)
    {
        $this->authorize('update', $widget);

        WebhookLog::forWidget($widget->id)->delete();

        return back()->with('success', 'Webhook logs cleared.');
    }

    // A/B Testing Methods
    public function createAbTest(Widget $widget)
    {
        $this->authorize('update', $widget);

        return view('widgets.ab-tests.create', compact('widget'));
    }

    public function storeAbTest(Request $request, Widget $widget)
    {
        $this->authorize('update', $widget);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'variant_a_welcome_message' => 'required|string|max:500',
            'variant_a_primary_color' => 'nullable|string|max:20',
            'variant_a_bg_color' => 'nullable|string|max:20',
            'variant_a_text_color' => 'nullable|string|max:20',
            'variant_a_position' => 'nullable|in:bottom-right,bottom-left,top-right,top-left',
            'variant_b_welcome_message' => 'required|string|max:500',
            'variant_b_primary_color' => 'nullable|string|max:20',
            'variant_b_bg_color' => 'nullable|string|max:20',
            'variant_b_text_color' => 'nullable|string|max:20',
            'variant_b_position' => 'nullable|in:bottom-right,bottom-left,top-right,top-left',
            'split_ratio' => 'required|numeric|min:0.1|max:0.9',
        ]);

        $abTest = $widget->abTests()->create([
            'name' => $validated['name'],
            'variant_a' => [
                'welcome_message' => $validated['variant_a_welcome_message'],
                'primary_color' => $validated['variant_a_primary_color'] ?? $widget->primary_color,
                'bg_color' => $validated['variant_a_bg_color'] ?? $widget->bg_color,
                'text_color' => $validated['variant_a_text_color'] ?? $widget->text_color,
                'position' => $validated['variant_a_position'] ?? $widget->position,
            ],
            'variant_b' => [
                'welcome_message' => $validated['variant_b_welcome_message'],
                'primary_color' => $validated['variant_b_primary_color'] ?? $widget->primary_color,
                'bg_color' => $validated['variant_b_bg_color'] ?? $widget->bg_color,
                'text_color' => $validated['variant_b_text_color'] ?? $widget->text_color,
                'position' => $validated['variant_b_position'] ?? $widget->position,
            ],
            'split_ratio' => $validated['split_ratio'],
            'is_active' => true,
            'started_at' => now(),
        ]);

        return redirect()->route('widgets.edit', $widget)->with('success', 'A/B test created and started!');
    }

    public function editAbTest(Widget $widget, \App\Models\WidgetAbTest $abTest)
    {
        $this->authorize('update', $widget);

        if ((int) $abTest->widget_id !== (int) $widget->id) {
            abort(404);
        }

        return view('widgets.ab-tests.edit', compact('widget', 'abTest'));
    }

    public function updateAbTest(Request $request, Widget $widget, \App\Models\WidgetAbTest $abTest)
    {
        $this->authorize('update', $widget);

        if ((int) $abTest->widget_id !== (int) $widget->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'variant_a_welcome_message' => 'required|string|max:500',
            'variant_a_primary_color' => 'nullable|string|max:20',
            'variant_a_bg_color' => 'nullable|string|max:20',
            'variant_a_text_color' => 'nullable|string|max:20',
            'variant_a_position' => 'nullable|in:bottom-right,bottom-left,top-right,top-left',
            'variant_b_welcome_message' => 'required|string|max:500',
            'variant_b_primary_color' => 'nullable|string|max:20',
            'variant_b_bg_color' => 'nullable|string|max:20',
            'variant_b_text_color' => 'nullable|string|max:20',
            'variant_b_position' => 'nullable|in:bottom-right,bottom-left,top-right,top-left',
            'split_ratio' => 'required|numeric|min:0.1|max:0.9',
        ]);

        $abTest->update([
            'name' => $validated['name'],
            'variant_a' => [
                'welcome_message' => $validated['variant_a_welcome_message'],
                'primary_color' => $validated['variant_a_primary_color'] ?? $widget->primary_color,
                'bg_color' => $validated['variant_a_bg_color'] ?? $widget->bg_color,
                'text_color' => $validated['variant_a_text_color'] ?? $widget->text_color,
                'position' => $validated['variant_a_position'] ?? $widget->position,
            ],
            'variant_b' => [
                'welcome_message' => $validated['variant_b_welcome_message'],
                'primary_color' => $validated['variant_b_primary_color'] ?? $widget->primary_color,
                'bg_color' => $validated['variant_b_bg_color'] ?? $widget->bg_color,
                'text_color' => $validated['variant_b_text_color'] ?? $widget->text_color,
                'position' => $validated['variant_b_position'] ?? $widget->position,
            ],
            'split_ratio' => $validated['split_ratio'],
        ]);

        return redirect()->route('widgets.edit', $widget)->with('success', 'A/B test updated!');
    }

    public function toggleAbTest(Widget $widget, \App\Models\WidgetAbTest $abTest)
    {
        $this->authorize('update', $widget);

        if ((int) $abTest->widget_id !== (int) $widget->id) {
            abort(404);
        }

        $abTest->update([
            'is_active' => !$abTest->is_active,
            'started_at' => $abTest->is_active ? null : now(),
            'ended_at' => $abTest->is_active ? now() : null,
        ]);

        return back()->with('success', $abTest->is_active ? 'A/B test resumed!' : 'A/B test paused!');
    }

    public function deleteAbTest(Widget $widget, \App\Models\WidgetAbTest $abTest)
    {
        $this->authorize('update', $widget);

        if ((int) $abTest->widget_id !== (int) $widget->id) {
            abort(404);
        }

        $abTest->delete();

        return redirect()->route('widgets.edit', $widget)->with('success', 'A/B test deleted!');
    }
}
