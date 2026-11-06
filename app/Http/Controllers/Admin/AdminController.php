<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChannelConfiguration;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\Message;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Widget;
use App\Services\EnvWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public function dashboard()
    {
        $activeSubscriptions = Subscription::where('status', 'active')->with('plan')->get();

        $mrr = $activeSubscriptions->sum(fn ($s) => $s->plan
            ? ($s->billing_cycle === 'yearly' ? $s->plan->price_yearly / 12 : $s->plan->price_monthly)
            : 0);

        $stats = [
            'total_users'            => User::count(),
            'active_users'           => User::where('is_active', true)->count(),
            'total_widgets'          => Widget::count(),
            'active_widgets'         => Widget::where('is_active', true)->count(),
            'total_conversations'    => Conversation::count(),
            'total_leads'            => Lead::count(),
            'total_messages'         => \App\Models\Message::count(),
            'revenue_monthly'        => $mrr,
            'new_users_30d'          => User::where('created_at', '>=', now()->subDays(30))->count(),
            'new_users_7d'           => User::where('created_at', '>=', now()->subDays(7))->count(),
            'new_conversations_30d'  => Conversation::where('created_at', '>=', now()->subDays(30))->count(),
            'paying_users'           => $activeSubscriptions->count(),
            'churn_last_30d'         => Subscription::where('status', 'cancelled')
                ->where('ends_at', '>=', now()->subDays(30))->count(),
        ];

        // MRR trend (last 30 days estimated from subscription starts)
        $mrrChart = ['labels' => [], 'data' => []];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $mrrChart['labels'][] = $date->format('M d');
            // Simplified: count cumulative active subs at that date × avg price
            $count = Subscription::where('status', 'active')
                ->whereDate('created_at', '<=', $date)->count();
            $mrrChart['data'][] = round($count * ($mrr / max($stats['paying_users'], 1)), 2);
        }

        // User growth chart (last 30 days)
        $userChart = ['labels' => [], 'data' => []];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $userChart['labels'][] = $date->format('M d');
            $userChart['data'][]   = User::whereDate('created_at', $date)->count();
        }

        // Plan distribution for pie chart
        $planDistribution = Subscription::where('status', 'active')
            ->selectRaw('plan_id, count(*) as count')
            ->groupBy('plan_id')
            ->with('plan')
            ->get()
            ->map(fn ($s) => ['label' => $s->plan?->name ?? 'Unknown', 'count' => $s->count]);

        // Top customers by MRR
        $topCustomers = User::with(['subscription.plan', 'widgets'])
            ->whereHas('subscription', fn ($q) => $q->where('status', 'active'))
            ->withCount('widgets', 'leads')
            ->get()
            ->map(fn ($u) => [
                'user'   => $u,
                'mrr'    => $u->subscription?->billing_cycle === 'yearly'
                    ? ($u->subscription->plan?->price_yearly / 12)
                    : ($u->subscription->plan?->price_monthly ?? 0),
                'plan'   => $u->subscription?->plan?->name ?? 'Free',
            ])
            ->sortByDesc('mrr')
            ->take(10)
            ->values();

        // System health indicators
        $health = [
            'db_size_mb'    => $this->getDatabaseSizeMb(),
            'queue_pending' => \DB::table('jobs')->count(),
            'queue_failed'  => \DB::table('failed_jobs')->count(),
            'cache_driver'  => config('cache.default'),
            'queue_driver'  => config('queue.default'),
            'app_env'       => config('app.env'),
            'php_version'   => PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION,
        ];

        // Recent users
        $recentUsers = User::with('subscription.plan')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        // Subscription breakdown (legacy)
        $subscriptionBreakdown = $planDistribution;

        return view('admin.dashboard', compact(
            'stats', 'userChart', 'mrrChart', 'subscriptionBreakdown',
            'planDistribution', 'topCustomers', 'health', 'recentUsers'
        ));
    }

    private function getDatabaseSizeMb(): float
    {
        try {
            $conn = config('database.default');
            if ($conn === 'sqlite') {
                $path = database_path('database.sqlite');
                return file_exists($path) ? round(filesize($path) / 1048576, 2) : 0;
            }
            $db = config("database.connections.{$conn}.database");
            $row = \DB::selectOne("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size FROM information_schema.tables WHERE table_schema = ?", [$db]);
            return (float) ($row->size ?? 0);
        } catch (\Throwable) {
            return 0;
        }
    }

    // Users Management
    public function users(Request $request)
    {
        $query = User::with(['subscription.plan'])->withCount(['widgets', 'leads']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        if ($role = $request->get('role')) {
            $query->where('role', $role);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(25);

        return view('admin.users', compact('users'));
    }

    public function showUser(User $user)
    {
        $user->load(['subscription.plan', 'widgets', 'leads']);

        $widgetStats = $user->widgets()->withCount(['conversations', 'leads'])->get();

        return view('admin.user-show', compact('user', 'widgetStats'));
    }

    public function updateUser(Request $request, User $user)
    {
        \App\Support\Audit::log('user.updated', $user, $request->except(['_token', '_method', 'password']));
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email,' . $user->id,
            'role'      => 'required|in:admin,user',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $user->update($validated);

        return back()->with('success', 'User updated.');
    }

    public function deleteUser(User $user)
    {
        \App\Support\Audit::log('user.deleted', $user, ['email' => $user->email]);
        abort_if($user->id === request()->user()->id, 403, 'Cannot delete yourself.');

        $user->delete();

        return redirect()->route('admin.users')->with('success', 'User deleted.');
    }

    public function impersonateUser(User $user)
    {
        \App\Support\Audit::log('user.impersonated', $user, ['email' => $user->email]);
        session(['impersonating' => request()->user()->id]);
        auth()->login($user);

        return redirect()->route('dashboard')->with('info', 'You are now impersonating ' . $user->name);
    }

    public function stopImpersonating()
    {
        $adminId = session('impersonating');
        session()->forget('impersonating');

        if ($adminId) {
            auth()->loginUsingId($adminId);
        }

        return redirect()->route('admin.dashboard');
    }

    public function profile(Request $request)
    {
        return view('admin.profile', ['user' => $request->user()]);
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'email'           => 'required|email|unique:users,email,' . $request->user()->id,
            'company_name'    => 'nullable|string|max:255',
            'company_website' => 'nullable|url|max:255',
            'phone'           => 'nullable|string|max:20',
            'timezone'        => 'nullable|string|max:100',
        ]);

        $request->user()->update($validated);

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $request->user()->update(['avatar' => $path]);
        }

        return back()->with('success', 'Profile updated.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password'         => ['required', 'confirmed', 
                \Illuminate\Validation\Rules\Password::defaults()
            ],
        ]);

        $request->user()->update(['password' => Hash::make($request->password)]);

        return back()->with('success', 'Password updated.');
    }

    // Plans Management
    public function plans()
    {
        $plans = Plan::withCount('subscriptions')->orderBy('sort_order')->get();

        return view('admin.plans', compact('plans'));
    }

    public function storePlan(Request $request)
    {
        \App\Support\Audit::log('plan.created', null, $request->only(['name', 'price_monthly', 'price_yearly']));
        $validated = $request->validate([
            'name'                       => 'required|string|max:255',
            'slug'                       => 'required|string|max:100|unique:plans,slug|regex:/^[a-z0-9-]+$/',
            'description'                => 'nullable|string|max:500',
            'color'                      => 'nullable|string|max:30',
            'sort_order'                 => 'required|integer|min:0',
            'price_monthly'              => 'required|numeric|min:0',
            'price_yearly'               => 'required|numeric|min:0',
            'widgets_limit'              => 'required|integer|min:-1',
            'messages_per_month'         => 'required|integer|min:-1',
            'training_sources_limit'     => 'required|integer|min:-1',
            'leads_per_month'            => 'required|integer|min:-1',
            'bookings_per_month'         => 'required|integer|min:-1',
            'team_members_limit'         => 'required|integer|min:-1',
            'workflow_automations_limit' => 'required|integer|min:-1',
            'channels_per_widget'        => 'required|integer|min:-1',
            'channel_messages_per_month' => 'required|integer|min:-1',
            'stripe_monthly_id'          => 'nullable|string|max:255',
            'stripe_yearly_id'           => 'nullable|string|max:255',
            'has_live_chat'              => 'nullable|boolean',
            'has_ai_insights'            => 'nullable|boolean',
            'has_custom_branding'        => 'nullable|boolean',
            'has_api_access'             => 'nullable|boolean',
            'has_priority_support'       => 'nullable|boolean',
            'is_active'                  => 'nullable|boolean',
            'is_popular'                 => 'nullable|boolean',
        ]);

        $boolFields = ['has_live_chat', 'has_ai_insights', 'has_custom_branding', 'has_api_access', 'has_priority_support', 'is_active', 'is_popular'];
        foreach ($boolFields as $field) {
            $validated[$field] = $request->boolean($field);
        }

        $validated['features'] = [
            'channels_per_widget'        => (int) $validated['channels_per_widget'],
            'channel_messages_per_month' => (int) $validated['channel_messages_per_month'],
        ];
        unset($validated['channels_per_widget'], $validated['channel_messages_per_month']);

        Plan::create($validated);

        return redirect()->route('admin.plans')->with('success', "Plan \"{$validated['name']}\" created.");
    }

    public function updatePlan(Request $request, Plan $plan)
    {
        \App\Support\Audit::log('plan.updated', $plan, $request->only(['name', 'price_monthly', 'price_yearly', 'messages_per_month', 'bookings_per_month']));
        $validated = $request->validate([
            'name'                       => 'required|string|max:255',
            'slug'                       => 'required|string|max:100|unique:plans,slug,' . $plan->id . '|regex:/^[a-z0-9-]+$/',
            'description'                => 'nullable|string|max:500',
            'color'                      => 'nullable|string|max:30',
            'sort_order'                 => 'required|integer|min:0',
            'price_monthly'              => 'required|numeric|min:0',
            'price_yearly'               => 'required|numeric|min:0',
            'widgets_limit'              => 'required|integer|min:-1',
            'messages_per_month'         => 'required|integer|min:-1',
            'training_sources_limit'     => 'required|integer|min:-1',
            'leads_per_month'            => 'required|integer|min:-1',
            'bookings_per_month'         => 'required|integer|min:-1',
            'team_members_limit'         => 'required|integer|min:-1',
            'workflow_automations_limit' => 'required|integer|min:-1',
            'channels_per_widget'        => 'required|integer|min:-1',
            'channel_messages_per_month' => 'required|integer|min:-1',
            'stripe_monthly_id'          => 'nullable|string|max:255',
            'stripe_yearly_id'           => 'nullable|string|max:255',
            'has_live_chat'              => 'nullable|boolean',
            'has_ai_insights'            => 'nullable|boolean',
            'has_custom_branding'        => 'nullable|boolean',
            'has_api_access'             => 'nullable|boolean',
            'has_priority_support'       => 'nullable|boolean',
            'is_active'                  => 'nullable|boolean',
            'is_popular'                 => 'nullable|boolean',
        ]);

        $boolFields = ['has_live_chat', 'has_ai_insights', 'has_custom_branding', 'has_api_access', 'has_priority_support', 'is_active', 'is_popular'];
        foreach ($boolFields as $field) {
            $validated[$field] = $request->boolean($field);
        }

        $features = $plan->features ?? [];
        $features['channels_per_widget']        = (int) $validated['channels_per_widget'];
        $features['channel_messages_per_month'] = (int) $validated['channel_messages_per_month'];
        unset($validated['channels_per_widget'], $validated['channel_messages_per_month']);
        $validated['features'] = $features;

        $plan->update($validated);

        return redirect()->route('admin.plans')->with('success', "Plan \"{$plan->name}\" updated.");
    }

    public function deletePlan(Plan $plan)
    {
        \App\Support\Audit::log('plan.deleted', $plan, ['name' => $plan->name]);
        $active = $plan->subscriptions()->whereIn('status', ['active', 'trialing'])->count();

        if ($active > 0) {
            return back()->with('error', "Cannot delete \"{$plan->name}\" — it has {$active} active subscriber(s). Deactivate it instead.");
        }

        $name = $plan->name;
        $plan->delete();

        return redirect()->route('admin.plans')->with('success', "Plan \"{$name}\" deleted.");
    }

    // Channels Management
    public function channels(Request $request)
    {
        $query = ChannelConfiguration::with(['widget.user'])
            ->withCount('messages');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('account_name', 'like', "%{$search}%")
                  ->orWhereHas('widget', fn ($w) => $w->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('widget.user', fn ($u) => $u->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        if ($type = $request->get('type')) {
            $query->where('channel_type', $type);
        }

        if ($status = $request->get('status')) {
            $query->where('is_enabled', $status === 'active');
        }

        $channels = $query->orderBy('created_at', 'desc')->paginate(30)->withQueryString();

        $stats = [
            'total'    => ChannelConfiguration::count(),
            'active'   => ChannelConfiguration::where('is_enabled', true)->count(),
            'inactive' => ChannelConfiguration::where('is_enabled', false)->count(),
            'by_type'  => ChannelConfiguration::selectRaw('channel_type, count(*) as count')
                ->groupBy('channel_type')->pluck('count', 'channel_type'),
        ];

        $types = ['web', 'whatsapp', 'telegram', 'facebook', 'instagram', 'slack', 'discord', 'sms', 'twitter', 'email'];

        return view('admin.channels', compact('channels', 'stats', 'types'));
    }

    public function toggleChannel(ChannelConfiguration $channel)
    {
        $channel->update(['is_enabled' => ! $channel->is_enabled]);

        return back()->with('success', 'Channel ' . ($channel->is_enabled ? 'enabled' : 'disabled') . '.');
    }

    public function deleteChannel(ChannelConfiguration $channel)
    {
        $channel->delete();

        return back()->with('success', 'Channel configuration deleted.');
    }

    // System Settings
    public function settings()
    {
        // Ensure all default settings exist (idempotent)
        $this->ensureSettingsSeeded();

        $settings = SystemSetting::orderBy('group')->orderBy('key')
            ->get()
            ->groupBy('group');

        // Tab order
        $tabOrder = ['app', 'branding', 'ai', 'mail', 'payment', 'security', 'features'];
        $settings = collect($tabOrder)
            ->mapWithKeys(fn ($g) => [$g => $settings->get($g, collect())])
            ->filter(fn ($g) => $g->isNotEmpty());

        return view('admin.settings', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        \App\Support\Audit::log('settings.updated', null, ['keys' => array_keys((array) $request->input('settings', []))]);
        $incoming  = $request->input('settings', []);
        $envBatch  = []; // collected [envKey, envValue] pairs written once at the end

        foreach ($incoming as $key => $value) {
            $setting = SystemSetting::where('key', $key)->first();

            if (! $setting) {
                continue;
            }

            // Preserve existing value for sensitive fields when submitted blank
            if ($setting->is_sensitive && blank($value)) {
                continue;
            }

            $setting->value = match ($setting->type) {
                'boolean' => $request->boolean("settings.{$key}") ? '1' : '0',
                default   => (string) $value,
            };
            $setting->save();

            if ($setting->config_key) {
                $typedValue = match ($setting->type) {
                    'boolean' => (bool) $setting->value,
                    'integer' => (int)  $setting->value,
                    default   => $setting->value,
                };

                // Update runtime config for this request
                config([$setting->config_key => $typedValue]);

                // Keep branding.name in sync with app.name
                if ($setting->config_key === 'app.name') {
                    config(['branding.name' => $setting->value]);
                }

                // Queue .env write
                $envKey = EnvWriter::fromConfigKey($setting->config_key);
                if ($envKey) {
                    $envValue    = $setting->type === 'boolean'
                        ? ($setting->value === '1' ? 'true' : 'false')
                        : $setting->value;
                    $envBatch[] = [$envKey, $envValue];
                }
            }
        }

        // Handle boolean checkboxes that are absent from POST when unchecked
        SystemSetting::where('type', 'boolean')->get()->each(function ($s) use ($incoming, &$envBatch) {
            if (! array_key_exists($s->key, $incoming)) {
                $s->update(['value' => '0']);
                if ($s->config_key) {
                    config([$s->config_key => false]);
                    $envKey = EnvWriter::fromConfigKey($s->config_key);
                    if ($envKey) {
                        $envBatch[] = [$envKey, 'false'];
                    }
                }
            }
        });

        // Write all .env changes in a single file operation, then invalidate
        // the config cache by deleting the compiled file directly — calling
        // Artisan::call('config:clear') in a web context boots a second kernel
        // instance that can disrupt the current request's session.
        if (! empty($envBatch)) {
            EnvWriter::writeBatch($envBatch);
            EnvWriter::clearConfigCache();
        }

        cache()->forget('pfx_mail_config');

        return back()->with('success', 'Settings saved successfully.');
    }

    public function testEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        try {
            Mail::raw(
                'This is a test email from ' . config('app.name') . ' admin panel. If you received this, your SMTP configuration is working correctly. Sent at: ' . now()->toDateTimeString(),
                function ($msg) use ($request) {
                    $msg->to($request->email)
                        ->subject('[' . config('app.name') . '] SMTP Test — ' . now()->format('Y-m-d H:i'));
                }
            );

            return response()->json(['ok' => true, 'message' => "Test email sent to {$request->email}. Check your inbox."]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'Failed: ' . $e->getMessage()]);
        }
    }

    public function testAiProvider(Request $request)
    {
        $provider = $request->input('provider');

        $apiKey = match ($provider) {
            'openai'    => config('ai.openai.key'),
            'anthropic' => config('ai.anthropic.key'),
            'gemini'    => config('ai.gemini.key'),
            'groq'      => config('ai.groq.key'),
            'openrouter' => config('ai.openrouter.key'),
            default     => null,
        };

        if (blank($apiKey)) {
            return response()->json(['ok' => false, 'message' => "No API key configured for {$provider}."]);
        }

        try {
            $result = match ($provider) {
                'openai' => $this->testOpenAi($apiKey),
                'anthropic' => $this->testAnthropic($apiKey),
                'gemini' => $this->testGemini($apiKey),
                'groq'   => $this->testGroq($apiKey),
                'openrouter' => $this->testOpenRouter($apiKey),
                default  => ['ok' => false, 'message' => 'Unknown provider.'],
            };

            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()]);
        }
    }

    private function testOpenAi(string $key): array
    {
        $resp = \Illuminate\Support\Facades\Http::withToken($key)
            ->timeout(10)
            ->get('https://api.openai.com/v1/models');

        return $resp->successful()
            ? ['ok' => true,  'message' => 'OpenAI connection successful.']
            : ['ok' => false, 'message' => 'OpenAI error: ' . ($resp->json('error.message') ?? $resp->status())];
    }

    private function testAnthropic(string $key): array
    {
        $resp = \Illuminate\Support\Facades\Http::withHeaders([
            'x-api-key'         => $key,
            'anthropic-version' => '2023-06-01',
        ])->timeout(10)->post('https://api.anthropic.com/v1/messages', [
            'model'      => 'claude-haiku-4-5-20251001',
            'max_tokens' => 1,
            'messages'   => [['role' => 'user', 'content' => 'ping']],
        ]);

        return ($resp->successful() || $resp->status() === 400)
            ? ['ok' => true,  'message' => 'Anthropic connection successful.']
            : ['ok' => false, 'message' => 'Anthropic error: ' . ($resp->json('error.message') ?? $resp->status())];
    }

    private function testGemini(string $key): array
    {
        $resp = \Illuminate\Support\Facades\Http::timeout(10)
            ->get("https://generativelanguage.googleapis.com/v1beta/models?key={$key}");

        return $resp->successful()
            ? ['ok' => true,  'message' => 'Gemini connection successful.']
            : ['ok' => false, 'message' => 'Gemini error: ' . ($resp->json('error.message') ?? $resp->status())];
    }

    private function testOpenRouter(string $key): array
    {
        $resp = \Illuminate\Support\Facades\Http::withToken($key)->timeout(10)->get('https://openrouter.ai/api/v1/key');

        return $resp->successful()
            ? ['ok' => true,  'message' => 'OpenRouter connection successful.']
            : ['ok' => false, 'message' => 'OpenRouter error: ' . ($resp->json('error.message') ?? $resp->status())];
    }

    private function testGroq(string $key): array
    {
        $resp = \Illuminate\Support\Facades\Http::withToken($key)
            ->timeout(10)
            ->get('https://api.groq.com/openai/v1/models');

        return $resp->successful()
            ? ['ok' => true,  'message' => 'Groq connection successful.']
            : ['ok' => false, 'message' => 'Groq error: ' . ($resp->json('error.message') ?? $resp->status())];
    }

    public function uploadBrandingAsset(Request $request)
    {
        $request->validate([
            'asset' => 'required|in:logo,favicon,og_image',
            'file'  => 'required|file|mimes:png,jpg,jpeg,gif,svg,ico,webp|max:2048',
        ]);

        $asset = $request->input('asset');
        $file  = $request->file('file');

        // Ensure storage is linked
        if (! file_exists(public_path('storage'))) {
            Artisan::call('storage:link');
        }

        // Delete previous file for this asset
        $existing = SystemSetting::where('key', "brand_{$asset}_url")->first();
        if ($existing && $existing->value) {
            $oldPath = str_replace(Storage::url(''), '', $existing->value);
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $ext      = $file->getClientOriginalExtension();
        $filename = "{$asset}.{$ext}";
        $path     = $file->storeAs('branding', $filename, 'public');
        $url      = Storage::url($path);

        SystemSetting::updateOrCreate(
            ['key' => "brand_{$asset}_url"],
            [
                'value'       => $url,
                'group'       => 'branding',
                'type'        => 'string',
                'label'       => match($asset) {
                    'logo'     => 'Logo URL',
                    'favicon'  => 'Favicon URL',
                    'og_image' => 'OG Image URL',
                },
                'description' => 'Auto-set by file upload.',
                'is_public'   => true,
            ]
        );

        // Update live config
        config(["branding.{$asset}_url" => $url]);

        return back()->with('success', ucfirst($asset) . ' uploaded successfully.');
    }

    public function deleteBrandingAsset(Request $request, string $asset)
    {
        abort_unless(in_array($asset, ['logo', 'favicon', 'og_image']), 422);

        $setting = SystemSetting::where('key', "brand_{$asset}_url")->first();
        if ($setting && $setting->value) {
            $oldPath = ltrim(str_replace('/storage', '', parse_url($setting->value, PHP_URL_PATH)), '/');
            Storage::disk('public')->delete($oldPath);
            $setting->update(['value' => '']);
        }

        return back()->with('success', ucfirst($asset) . ' removed.');
    }

    private function ensureSettingsSeeded(): void
    {
        try {
            Artisan::call('db:seed', ['--class' => 'SystemSettingsSeeder', '--force' => true]);
        } catch (\Throwable) {
            // Silent fail — DB not ready or seeder already ran
        }
    }

    // ── Widgets Management ────────────────────────────────────────────────────

    public function widgets(Request $request)
    {
        $query = Widget::with(['user', 'conversations' => fn ($q) => $q->selectRaw('widget_id, count(*) as cnt')->groupBy('widget_id')])
            ->withCount(['conversations', 'leads'])
            ->withSum('conversations', 'ai_message_count');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('widgets.name', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->get('status')) {
            $query->where('is_active', $status === 'active');
        }

        if ($userId = $request->get('user_id')) {
            $query->where('user_id', $userId);
        }

        $widgets = $query->orderBy('created_at', 'desc')->paginate(25)->withQueryString();

        $stats = [
            'total'    => Widget::count(),
            'active'   => Widget::where('is_active', true)->count(),
            'inactive' => Widget::where('is_active', false)->count(),
            'users'    => Widget::distinct('user_id')->count('user_id'),
        ];

        return view('admin.widgets', compact('widgets', 'stats'));
    }

    public function toggleWidget(Request $request, Widget $widget)
    {
        \App\Support\Audit::log('widget.toggled', $widget, ['active_before' => $widget->is_active]);
        $widget->update(['is_active' => ! $widget->is_active]);
        return back()->with('success', "Widget \"" . $widget->name . "\" " . ($widget->is_active ? 'activated' : 'deactivated') . '.');
    }

    public function deleteWidget(Widget $widget)
    {
        \App\Support\Audit::log('widget.deleted', $widget, ['name' => $widget->name]);
        $name = $widget->name;
        $widget->delete();
        return back()->with('success', "Widget \"{$name}\" deleted.");
    }

    // ── Conversations Management ──────────────────────────────────────────────

    public function conversations(Request $request)
    {
        $query = Conversation::with(['widget.user', 'lead'])
            ->withCount('messages');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('visitor_name', 'like', "%{$search}%")
                  ->orWhere('visitor_email', 'like', "%{$search}%")
                  ->orWhereHas('widget', fn ($w) => $w->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('widget.user', fn ($u) => $u->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($from = $request->get('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->get('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $conversations = $query->orderBy('created_at', 'desc')->paginate(25)->withQueryString();

        $stats = [
            'total'    => Conversation::count(),
            'open'     => Conversation::where('status', 'open')->count(),
            'resolved' => Conversation::where('status', 'resolved')->count(),
            'today'    => Conversation::whereDate('created_at', today())->count(),
        ];

        return view('admin.conversations', compact('conversations', 'stats'));
    }

    public function deleteConversation(Conversation $conversation)
    {
        $conversation->delete();
        return back()->with('success', 'Conversation deleted.');
    }

    // ── Reports ───────────────────────────────────────────────────────────────

    public function reports(Request $request)
    {
        $range = (int) $request->get('range', 30);
        $range = in_array($range, [7, 30, 90, 365]) ? $range : 30;
        $from  = now()->subDays($range);

        // MRR & ARR
        $activeSubscriptions = Subscription::where('status', 'active')->with('plan')->get();
        $mrr = $activeSubscriptions->sum(fn ($s) => $s->plan
            ? ($s->billing_cycle === 'yearly' ? $s->plan->price_yearly / 12 : $s->plan->price_monthly)
            : 0);
        $arr = $mrr * 12;

        // Daily revenue chart
        $revenueChart = ['labels' => [], 'data' => []];
        for ($i = $range - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $revenueChart['labels'][] = $date->format($range <= 30 ? 'M d' : 'M d');
            $count = Subscription::where('status', 'active')
                ->whereDate('created_at', '<=', $date)->count();
            $revenueChart['data'][] = round($count * ($mrr / max($activeSubscriptions->count(), 1)), 2);
        }

        // User growth chart
        $userGrowth = ['labels' => [], 'new' => [], 'cumulative' => []];
        $cumulative = User::whereDate('created_at', '<', $from)->count();
        for ($i = $range - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $new = User::whereDate('created_at', $date)->count();
            $cumulative += $new;
            $userGrowth['labels'][]     = $date->format('M d');
            $userGrowth['new'][]        = $new;
            $userGrowth['cumulative'][] = $cumulative;
        }

        // Conversation volume
        $convChart = ['labels' => [], 'data' => []];
        for ($i = $range - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $convChart['labels'][] = $date->format('M d');
            $convChart['data'][]   = Conversation::whereDate('created_at', $date)->count();
        }

        // Lead funnel
        $funnel = [
            'conversations' => Conversation::where('created_at', '>=', $from)->count(),
            'leads'         => Lead::where('created_at', '>=', $from)->count(),
            'qualified'     => Lead::where('created_at', '>=', $from)->where('score', '>=', 70)->count(),
        ];

        // Top plans by revenue
        $planRevenue = Subscription::where('status', 'active')->with('plan')
            ->get()
            ->groupBy('plan_id')
            ->map(fn ($subs) => [
                'plan'  => $subs->first()?->plan,
                'count' => $subs->count(),
                'mrr'   => $subs->sum(fn ($s) => $s->plan
                    ? ($s->billing_cycle === 'yearly' ? $s->plan->price_yearly / 12 : $s->plan->price_monthly)
                    : 0),
            ])
            ->sortByDesc('mrr')
            ->values();

        // Cohort data — monthly signups + their current status
        $cohorts = collect();
        for ($m = 5; $m >= 0; $m--) {
            $start = now()->subMonths($m)->startOfMonth();
            $end   = now()->subMonths($m)->endOfMonth();
            $users = User::whereBetween('created_at', [$start, $end]);
            $paying = (clone $users)->whereHas('subscription', fn ($q) => $q->where('status', 'active'))->count();
            $total  = $users->count();
            $cohorts->push([
                'month'        => $start->format('M Y'),
                'total'        => $total,
                'paying'       => $paying,
                'conversion'   => $total > 0 ? round($paying / $total * 100, 1) : 0,
            ]);
        }

        // Key metrics
        $metrics = [
            'mrr'              => $mrr,
            'arr'              => $arr,
            'total_users'      => User::count(),
            'paying_users'     => $activeSubscriptions->count(),
            'trial_users'      => Subscription::where('status', 'trialing')->count(),
            'churn_30d'        => Subscription::where('status', 'canceled')
                                    ->where('canceled_at', '>=', now()->subDays(30))->count(),
            'arpu'             => $activeSubscriptions->count() > 0 ? round($mrr / $activeSubscriptions->count(), 2) : 0,
            'new_users_period' => User::where('created_at', '>=', $from)->count(),
            'new_convs_period' => Conversation::where('created_at', '>=', $from)->count(),
            'new_leads_period' => Lead::where('created_at', '>=', $from)->count(),
        ];

        return view('admin.reports', compact(
            'range', 'metrics', 'revenueChart', 'userGrowth', 'convChart',
            'funnel', 'planRevenue', 'cohorts'
        ));
    }

    // ── Broadcasts ────────────────────────────────────────────────────────────

    public function broadcasts()
    {
        $broadcasts = DB::table('admin_broadcasts')->orderBy('created_at', 'desc')->paginate(20);
        $userCount  = [
            'all'     => User::where('is_active', true)->count(),
            'trial'   => Subscription::where('status', 'trialing')->distinct('user_id')->count('user_id'),
            'paying'  => Subscription::where('status', 'active')->distinct('user_id')->count('user_id'),
            'free'    => User::where('is_active', true)
                ->whereDoesntHave('subscription', fn ($q) => $q->whereIn('status', ['active', 'trialing']))->count(),
        ];
        return view('admin.broadcasts', compact('broadcasts', 'userCount'));
    }

    public function sendBroadcast(Request $request)
    {
        $validated = $request->validate([
            'subject'  => 'required|string|max:255',
            'body'     => 'required|string|min:20',
            'segment'  => 'required|in:all,trial,paying,free',
        ]);

        $users = match ($validated['segment']) {
            'trial'  => User::whereHas('subscription', fn ($q) => $q->where('status', 'trialing'))->where('is_active', true)->get(),
            'paying' => User::whereHas('subscription', fn ($q) => $q->where('status', 'active'))->where('is_active', true)->get(),
            'free'   => User::whereDoesntHave('subscription', fn ($q) => $q->whereIn('status', ['active', 'trialing']))->where('is_active', true)->get(),
            default  => User::where('is_active', true)->get(),
        };

        $sent = 0;
        foreach ($users as $user) {
            try {
                Mail::raw($validated['body'], function ($msg) use ($user, $validated) {
                    $msg->to($user->email, $user->name)
                        ->subject($validated['subject'])
                        ->from(config('mail.from.address'), config('mail.from.name'));
                });
                $sent++;
            } catch (\Throwable $e) {
                Log::error('Broadcast mail failed for ' . $user->email . ': ' . $e->getMessage());
            }
        }

        // Log broadcast
        if (DB::getSchemaBuilder()->hasTable('admin_broadcasts')) {
            DB::table('admin_broadcasts')->insert([
                'admin_id'   => auth()->id(),
                'subject'    => $validated['subject'],
                'body'       => $validated['body'],
                'segment'    => $validated['segment'],
                'sent_count' => $sent,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return back()->with('success', "Broadcast sent to {$sent} users.");
    }

    // ── System Health ─────────────────────────────────────────────────────────

    public function system()
    {
        $queuePending = 0;
        $queueFailed  = 0;
        $recentFailed = collect();

        try {
            $queuePending = DB::table('jobs')->count();
            $queueFailed  = DB::table('failed_jobs')->count();
            $recentFailed = DB::table('failed_jobs')->orderBy('failed_at', 'desc')->limit(10)->get();
        } catch (\Throwable) {}

        $health = [
            'db_size_mb'     => $this->getDatabaseSizeMb(),
            'queue_pending'  => $queuePending,
            'queue_failed'   => $queueFailed,
            'cache_driver'   => config('cache.default'),
            'queue_driver'   => config('queue.default'),
            'app_env'        => config('app.env'),
            'php_version'    => PHP_VERSION,
            'laravel_version'=> app()->version(),
            'memory_limit'   => ini_get('memory_limit'),
            'max_exec_time'  => ini_get('max_execution_time') . 's',
            'upload_max'     => ini_get('upload_max_filesize'),
            'storage_free'   => $this->getStorageFreeGb(),
            'opcache'        => function_exists('opcache_get_status') && opcache_get_status() !== false,
            'debug_mode'     => config('app.debug'),
        ];

        $extensions = ['pdo', 'mbstring', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath', 'openssl', 'curl', 'gd', 'zip'];
        $extStatus  = array_combine($extensions, array_map('extension_loaded', $extensions));

        $tableStats = $this->getTableRowCounts();

        $checks = \App\Support\SiteHealth::checks();

        return view('admin.system', compact('health', 'extStatus', 'tableStats', 'recentFailed', 'checks'));
    }

    public function clearCache(Request $request)
    {
        $type = $request->input('type', 'all');
        try {
            match ($type) {
                'config'    => Artisan::call('config:clear'),
                'routes'    => Artisan::call('route:clear'),
                'views'     => Artisan::call('view:clear'),
                'app'       => Cache::flush(),
                default     => $this->clearAllCaches(),
            };
            return back()->with('success', ucfirst($type) . ' cache cleared successfully.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Cache clear failed: ' . $e->getMessage());
        }
    }

    public function retryFailedJobs()
    {
        try {
            Artisan::call('queue:retry', ['id' => ['all']]);
            return back()->with('success', 'All failed jobs queued for retry.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Retry failed: ' . $e->getMessage());
        }
    }

    public function flushFailedJobs()
    {
        try {
            Artisan::call('queue:flush');
            return back()->with('success', 'All failed jobs flushed.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Flush failed: ' . $e->getMessage());
        }
    }

    public function runArtisan(Request $request)
    {
        $allowed = [
            'cache:clear'    => [],
            'config:clear'   => [],
            'config:cache'   => [],
            'route:clear'    => [],
            'route:cache'    => [],
            'view:clear'     => [],
            'view:cache'     => [],
            'queue:restart'  => [],
            'storage:link'   => [],
            'optimize:clear' => [],
            'optimize'       => [],
        ];

        $cmd = $request->input('command');
        if (! array_key_exists($cmd, $allowed)) {
            return response()->json(['ok' => false, 'output' => 'Command not allowed.']);
        }

        try {
            Artisan::call($cmd);
            return response()->json(['ok' => true, 'output' => Artisan::output() ?: 'Command executed successfully.']);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'output' => $e->getMessage()]);
        }
    }

    public function viewLogs(Request $request)
    {
        $lines = (int) $request->get('lines', 100);
        $lines = min(max($lines, 50), 500);
        $log   = '';

        try {
            $logPath = storage_path('logs/laravel.log');
            if (file_exists($logPath)) {
                $file    = new \SplFileObject($logPath, 'r');
                $file->seek(PHP_INT_MAX);
                $total   = $file->key();
                $start   = max(0, $total - $lines);
                $file->seek($start);
                while (! $file->eof()) {
                    $log .= $file->fgets();
                }
            } else {
                $log = 'No log file found.';
            }
        } catch (\Throwable $e) {
            $log = 'Error reading logs: ' . $e->getMessage();
        }

        return response()->json(['log' => $log]);
    }

    private function clearAllCaches(): void
    {
        Cache::flush();
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
    }

    private function getStorageFreeGb(): string
    {
        try {
            $free = disk_free_space(storage_path());
            return round($free / 1073741824, 1) . ' GB';
        } catch (\Throwable) {
            return 'N/A';
        }
    }

    private function getTableRowCounts(): array
    {
        $tables = ['users', 'widgets', 'conversations', 'messages', 'leads', 'subscriptions', 'knowledge_bases', 'system_settings'];
        $counts = [];
        foreach ($tables as $table) {
            try {
                $counts[$table] = DB::table($table)->count();
            } catch (\Throwable) {
                $counts[$table] = 0;
            }
        }
        return $counts;
    }

    // ── Activity Log ─────────────────────────────────────────────────────────

    public function activity(Request $request)
    {
        $query = DB::table('activity_log')
            ->leftJoin('users', 'activity_log.causer_id', '=', 'users.id')
            ->select('activity_log.*', 'users.name as causer_name', 'users.email as causer_email');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('activity_log.description', 'like', "%{$search}%")
                  ->orWhere('users.email', 'like', "%{$search}%")
                  ->orWhere('users.name', 'like', "%{$search}%");
            });
        }

        if ($event = $request->get('event')) {
            $query->where('activity_log.event', $event);
        }

        if ($from = $request->get('from')) {
            $query->whereDate('activity_log.created_at', '>=', $from);
        }

        $activities = $query->orderBy('activity_log.created_at', 'desc')->paginate(50)->withQueryString();

        $events = DB::table('activity_log')->distinct()->pluck('event')->filter()->values();

        $todayCount = DB::table('activity_log')->whereDate('created_at', today())->count();
        $weekCount  = DB::table('activity_log')->where('created_at', '>=', now()->subDays(7))->count();

        return view('admin.activity', compact('activities', 'events', 'todayCount', 'weekCount'));
    }
}
