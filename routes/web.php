<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\AutomationController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KnowledgeBaseController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\WidgetController;
use Illuminate\Support\Facades\Route;

// Installer routes
require __DIR__.'/installer.php';

// Offline page (no auth required)
Route::get('/offline', function () {
    return view('offline');
})->name('offline');

// Landing page
Route::get('/', function () {
    if (!config('branding.landing_enabled')) {
        return redirect()->route('login');
    }
    $plans = \App\Models\Plan::where('is_active', true)->orderBy('sort_order')->get();
    return view('welcome', compact('plans'));
})->name('home');

$staticPages = [
    'about' => [
        'name' => 'about',
        'title' => 'About',
        'heading' => 'About ' . config('branding.name'),
        'subheading' => config('branding.name') . ' makes AI chat widgets that help support and sales teams capture more leads, answer questions instantly, and convert visitors automatically.',
        'sections' => [
            ['title' => 'Our mission', 'body' => 'We are building intelligent chat tools that empower businesses to deliver better customer experiences while reducing manual workload and response time.'],
            ['title' => 'What we build', 'body' => 'From smart lead capture to knowledge-based answers, our platform combines AI, analytics, and customizable widgets so conversations stay fast, accurate, and human-friendly.'],
        ],
    ],
    'careers' => [
        'name' => 'careers',
        'title' => 'Careers',
        'heading' => 'Join our team',
        'subheading' => 'We are a small, remote-first team building the next generation of AI customer experience tools.',
        'sections' => [
            ['title' => 'Open opportunities', 'body' => 'We hire people who love solving product problems, shipping polished software, and working with modern AI stacks. If that sounds like you, let us know.'],
            ['title' => 'Why work with us', 'body' => 'Flexible hours, strong ownership, and a focus on quality over hype. We value collaboration, learning, and helping customers succeed.'],
            ['title' => 'Apply today', 'body' => 'Send your resume and a short note to ' . config('branding.support_email', 'support@example.com') . ' — we would love to hear what you want to build next.'],
        ],
    ],
    'privacy' => [
        'name' => 'privacy',
        'title' => 'Privacy Policy',
        'heading' => 'Privacy Policy',
        'subheading' => 'We respect your privacy and protect customer data with strong security controls.',
        'sections' => [
            ['title' => 'Data handling', 'body' => 'We collect the minimum data required to run your account, process conversations, and deliver reliable AI responses. We never sell personal data to third parties.'],
            ['title' => 'Security', 'body' => 'All data is encrypted in transit and at rest. Access is restricted by authenticated user sessions, role controls, and audit logging.'],
        ],
    ],
    'terms' => [
        'name' => 'terms',
        'title' => 'Terms of Service',
        'heading' => 'Terms of Service',
        'subheading' => 'These terms describe how you may use our software and services.',
        'sections' => [
            ['title' => 'Use of service', 'body' => 'You agree to use the platform in a lawful way, to maintain control of your account credentials, and to provide accurate information during registration.'],
            ['title' => 'Billing and cancellation', 'body' => 'Subscriptions are billed monthly or yearly according to your selected plan. You may cancel at any time, and any unused prepaid portion will be handled according to the plan terms.'],
        ],
    ],
    'contact' => [
        'name' => 'contact',
        'title' => 'Contact',
        'heading' => 'Contact us',
        'subheading' => 'Need help? We are here to answer questions about setup, billing, and AI workflows.',
        'sections' => [
            ['title' => 'Sales and support', 'body' => 'Email our team at ' . config('branding.support_email', 'support@example.com') . ' for quotes, onboarding questions, or product support.'],
            ['title' => 'General inquiries', 'body' => 'If you need a custom integration or want to discuss enterprise plans, our inbox is open and we reply quickly.'],
        ],
    ],
    'documentation' => [
        'name' => 'documentation',
        'title' => 'Documentation',
        'heading' => 'Documentation',
        'subheading' => 'Everything you need to embed, configure, and extend your AI chat widget.',
        'sections' => [
            ['title' => 'Getting started', 'body' => 'Learn how to install the widget script, connect your AI provider, and launch a chat experience on your site.'],
            ['title' => 'Advanced guides', 'body' => 'Explore knowledge base setup, webhook integration, custom branding, and developer tools for deeper customization.'],
        ],
    ],
    'api-reference' => [
        'name' => 'api.reference',
        'title' => 'API Reference',
        'heading' => 'API Reference',
        'subheading' => 'Use our API to manage widgets, leads, and automation programmatically.',
        'sections' => [
            ['title' => 'Authentication', 'body' => 'Authenticate using API keys and follow our secure request patterns to interact with widget and lead data.'],
            ['title' => 'Endpoints', 'body' => 'Call endpoints for widget creation, conversation export, lead retrieval, and webhook events. Use strong error handling for reliable integrations.'],
        ],
    ],
    'blog' => [
        'name' => 'blog',
        'title' => 'Blog',
        'heading' => 'Blog',
        'subheading' => 'Stories and updates about AI chat, customer experience automation, and product development.',
        'sections' => [
            ['title' => 'Latest news', 'body' => 'Read about feature launches, product updates, and best practices for building smarter customer experiences.'],
            ['title' => 'Community insights', 'body' => 'We publish guides that help teams reduce support load, increase conversions, and launch AI experiences with confidence.'],
        ],
    ],
    'case-studies' => [
        'name' => 'case-studies',
        'title' => 'Case Studies',
        'heading' => 'Case Studies',
        'subheading' => 'See how real customers use AI chat to capture leads, boost conversions, and scale support.',
        'sections' => [
            ['title' => 'Success stories', 'body' => 'Explore example deployments, business outcomes, and the measurable ROI teams achieve with our platform.'],
            ['title' => 'Customer value', 'body' => 'From faster response times to higher lead quality, our customers gain more predictable growth with AI-assisted conversations.'],
        ],
    ],
    'changelog' => [
        'name' => 'changelog',
        'title' => 'Changelog',
        'heading' => 'Changelog',
        'subheading' => 'Track product improvements, bug fixes, and new feature releases.',
        'sections' => [
            ['title' => 'Release cadence', 'body' => 'We ship updates frequently, with an emphasis on reliability, performance, and the features our customers ask for most.'],
            ['title' => 'What to expect', 'body' => 'New releases include improvements to AI accuracy, widget customization, analytics, and developer tools.'],
        ],
    ],
    'roadmap' => [
        'name' => 'roadmap',
        'title' => 'Roadmap',
        'heading' => 'Roadmap',
        'subheading' => 'Our product roadmap is centered on AI experiences, integrations, and easier deployment.',
        'sections' => [
            ['title' => 'Upcoming work', 'body' => 'Expect improvements to analytics, deeper knowledge base capabilities, and more ways to personalize chat behavior.'],
            ['title' => 'Customer-driven', 'body' => 'We prioritize feedback from customers who want better support automation, higher conversion, and simplified AI management.'],
        ],
    ],
    'status' => [
        'name' => 'status',
        'title' => 'Status',
        'heading' => 'System status',
        'subheading' => 'Our platform is monitored continuously for uptime, reliability, and performance.',
        'sections' => [
            ['title' => 'Operational status', 'body' => 'All systems are currently operational. We maintain active monitoring and alerts for chat delivery, authentication, and analytics processing.'],
            ['title' => 'Incident response', 'body' => 'In the rare event of degraded service, our team responds immediately and communicates updates through our support channels.'],
        ],
    ],
];

foreach ($staticPages as $slug => $pageData) {
    Route::get('/' . $slug, function () use ($pageData) {
        return view('pages.page', $pageData);
    })->name($pageData['name']);
}

// Admin login (separate from user login, IP-restricted)
Route::middleware('admin.ip')->group(function () {
    Route::get('/admin/login', [AuthController::class, 'showAdminLogin'])->name('admin.login');
    Route::post('/admin/login', [AuthController::class, 'adminLogin'])->name('admin.login.submit');
});

// Two-Factor Challenge (not behind guest — user is partially authenticated)
Route::get('/two-factor-challenge', [AuthController::class, 'showTwoFactorChallenge'])->name('two-factor.challenge');
Route::post('/two-factor-challenge', [AuthController::class, 'twoFactorChallenge'])->name('two-factor.challenge.verify');

// Auth Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Team invite acceptance — public (invited user may not be logged in yet)
Route::get('/team/accept/{token}', [\App\Http\Controllers\SettingsController::class, 'acceptInvite'])->name('team.accept');

// Authenticated Routes
Route::middleware(['auth'])->group(function () {

    // ── Email Verification ────────────────────────────────────────────────────
    // These routes must NOT have the 'verified' middleware so unverified users can reach them.
    Route::get('/email/verify', fn () => view('auth.verify-email'))->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [AuthController::class, 'resendVerification'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    // Dashboard (accessible without active subscription, but requires verified email)
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('verified')->name('dashboard');

    // ── Subscription-gated features ───────────────────────────────────────────
    Route::middleware(['verified', 'subscription'])->group(function () {

        // Widgets
        Route::resource('widgets', WidgetController::class);
        Route::post('/widgets/{widget}/duplicate', [WidgetController::class, 'duplicate'])->name('widgets.duplicate');
        Route::post('/widgets/{widget}/toggle', [WidgetController::class, 'toggleActive'])->name('widgets.toggle');
        Route::post('/widgets/{widget}/regenerate-token', [WidgetController::class, 'regenerateToken'])->name('widgets.regenerateToken');
        Route::get('/widgets/{widget}/embed', [WidgetController::class, 'embedCode'])->name('widgets.embed');
        Route::post('/widgets/{widget}/test-webhook', [WidgetController::class, 'testWebhook'])->name('widgets.testWebhook');
        Route::get('/widgets/{widget}/webhook-logs', [WidgetController::class, 'webhookLogs'])->name('widgets.webhookLogs');
        Route::delete('/widgets/{widget}/webhook-logs', [WidgetController::class, 'clearWebhookLogs'])->name('widgets.webhookLogs.clear');

        // A/B Testing
        Route::get('/widgets/{widget}/ab-tests/create', [WidgetController::class, 'createAbTest'])->name('widgets.abTests.create');
        Route::post('/widgets/{widget}/ab-tests', [WidgetController::class, 'storeAbTest'])->name('widgets.abTests.store');
        Route::get('/widgets/{widget}/ab-tests/{abTest}/edit', [WidgetController::class, 'editAbTest'])->name('widgets.abTests.edit');
        Route::patch('/widgets/{widget}/ab-tests/{abTest}', [WidgetController::class, 'updateAbTest'])->name('widgets.abTests.update');
        Route::patch('/widgets/{widget}/ab-tests/{abTest}/toggle', [WidgetController::class, 'toggleAbTest'])->name('widgets.abTests.toggle');
        Route::delete('/widgets/{widget}/ab-tests/{abTest}', [WidgetController::class, 'deleteAbTest'])->name('widgets.abTests.delete');

        // Knowledge Bases
        Route::get('/widgets/{widget}/knowledge', [KnowledgeBaseController::class, 'index'])->name('widgets.knowledge.index');
        Route::post('/widgets/{widget}/knowledge', [KnowledgeBaseController::class, 'store'])->name('widgets.knowledge.store');
        Route::get('/widgets/{widget}/knowledge/{knowledgeBase}', [KnowledgeBaseController::class, 'show'])->name('widgets.knowledge.show');
        Route::post('/widgets/{widget}/knowledge/{knowledgeBase}/sources', [KnowledgeBaseController::class, 'addSource'])->name('widgets.knowledge.addSource');
        Route::delete('/widgets/{widget}/knowledge/{knowledgeBase}/sources/{source}', [KnowledgeBaseController::class, 'deleteSource'])->name('widgets.knowledge.deleteSource');
        Route::post('/widgets/{widget}/knowledge/{knowledgeBase}/retrain', [KnowledgeBaseController::class, 'retrain'])->name('widgets.knowledge.retrain');
        Route::post('/widgets/{widget}/knowledge/{knowledgeBase}/activate', [KnowledgeBaseController::class, 'activate'])->name('widgets.knowledge.activate');
        Route::delete('/widgets/{widget}/knowledge/{knowledgeBase}', [KnowledgeBaseController::class, 'destroy'])->name('widgets.knowledge.destroy');

        // Conversations
        Route::get('/conversations', [ConversationController::class, 'index'])->name('conversations.index');
        Route::get('/conversations/export', [ConversationController::class, 'export'])->name('conversations.export');
        Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
        Route::post('/conversations/{conversation}/message', [ConversationController::class, 'sendMessage'])->name('conversations.sendMessage');
        Route::post('/conversations/{conversation}/star', [ConversationController::class, 'toggleStar'])->name('conversations.star');
        Route::post('/conversations/{conversation}/resolve', [ConversationController::class, 'resolve'])->name('conversations.resolve');
        Route::post('/conversations/{conversation}/take-over', [ConversationController::class, 'takeOver'])->name('conversations.takeOver');
        Route::post('/conversations/{conversation}/switch-ai', [ConversationController::class, 'switchToAi'])->name('conversations.switchToAi');
        Route::post('/conversations/{conversation}/spam', [ConversationController::class, 'markAsSpam'])->name('conversations.spam');
        Route::delete('/conversations/{conversation}', [ConversationController::class, 'destroy'])->name('conversations.destroy');
        Route::post('/conversations/{conversation}/agent-assist', [ConversationController::class, 'agentAssist'])->name('conversations.agentAssist');
        Route::post('/conversations/{conversation}/labels', [ConversationController::class, 'addLabel'])->name('conversations.addLabel');
        Route::delete('/conversations/{conversation}/labels', [ConversationController::class, 'removeLabel'])->name('conversations.removeLabel');

        // Leads
        Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');
        Route::get('/leads/export', [LeadController::class, 'export'])->name('leads.export');
        Route::get('/leads/{lead}', [LeadController::class, 'show'])->name('leads.show');
        Route::put('/leads/{lead}', [LeadController::class, 'update'])->name('leads.update');
        Route::post('/leads/{lead}/notes', [LeadController::class, 'addNote'])->name('leads.addNote');
        Route::delete('/leads/{lead}', [LeadController::class, 'destroy'])->name('leads.destroy');

        // Analytics
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

        // Automations
        Route::resource('automations', AutomationController::class)->except(['show']);
        Route::post('/automations/{automation}/toggle', [AutomationController::class, 'toggleActive'])->name('automations.toggle');

    }); // end subscription group
    // ─────────────────────────────────────────────────────────────────────────

    // Settings & account management (always accessible so users can manage billing)
    Route::get('/settings/profile', [SettingsController::class, 'profile'])->name('settings.profile');
    Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile.update');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password.update');
    Route::get('/settings/notifications', [SettingsController::class, 'notifications'])->name('settings.notifications');
    Route::put('/settings/notifications', [SettingsController::class, 'updateNotifications'])->name('settings.notifications.update');
    Route::get('/settings/team', [SettingsController::class, 'team'])->name('settings.team');
    Route::post('/settings/team/invite', [SettingsController::class, 'inviteTeamMember'])->name('settings.team.invite');
    Route::delete('/settings/team/{member}', [SettingsController::class, 'removeTeamMember'])->name('settings.team.remove');
    Route::get('/settings/billing', [SettingsController::class, 'billing'])->name('settings.billing');
    Route::post('/settings/billing/change-plan', [SettingsController::class, 'changePlan'])->name('settings.billing.changePlan');
    Route::post('/settings/billing/cancel', [SettingsController::class, 'cancelSubscription'])->name('settings.billing.cancel');
    Route::get('/settings/developer', [SettingsController::class, 'developer'])->name('settings.developer');
    Route::get('/settings/api-keys', [SettingsController::class, 'apiKeys'])->name('settings.apiKeys');
    Route::post('/settings/api-keys', [SettingsController::class, 'createApiKey'])->name('settings.apiKeys.create');
    Route::delete('/settings/api-keys/{apiKey}', [SettingsController::class, 'deleteApiKey'])->name('settings.apiKeys.delete');
    Route::post('/settings/team/{member}/resend', [SettingsController::class, 'resendInvite'])->name('settings.team.resend');
    Route::patch('/settings/team/{member}/role', [SettingsController::class, 'updateTeamRole'])->name('settings.team.updateRole');
    Route::get('/settings/two-factor', [SettingsController::class, 'twoFactor'])->name('settings.twoFactor');
    Route::post('/settings/two-factor/enable', [SettingsController::class, 'enableTwoFactor'])->name('settings.twoFactor.enable');
    Route::post('/settings/two-factor/disable', [SettingsController::class, 'disableTwoFactor'])->name('settings.twoFactor.disable');
    Route::post('/settings/two-factor/recovery-codes', [SettingsController::class, 'regenerateRecoveryCodes'])->name('settings.twoFactor.recoveryCodes');

    // Canned Responses
    Route::get('/settings/canned-responses', [SettingsController::class, 'cannedResponses'])->name('settings.cannedResponses');
    Route::post('/settings/canned-responses', [SettingsController::class, 'storeCannedResponse'])->name('settings.cannedResponses.store');
    Route::put('/settings/canned-responses/{cannedResponse}', [SettingsController::class, 'updateCannedResponse'])->name('settings.cannedResponses.update');
    Route::delete('/settings/canned-responses/{cannedResponse}', [SettingsController::class, 'deleteCannedResponse'])->name('settings.cannedResponses.delete');

    // Billing plans page (destination for subscription check redirect)
    Route::get('/billing/plans', [SettingsController::class, 'billing'])->name('billing.plans');

    // Notifications
    Route::get('/notifications', [DashboardController::class, 'notifications'])->name('notifications.index');
    Route::post('/notifications/{notification}/mark-read', [DashboardController::class, 'markNotificationRead'])->name('notifications.mark-read');
    Route::post('/notifications/mark-all-read', [DashboardController::class, 'markAllNotificationsRead'])->name('notifications.mark-all-read');

    // Admin Routes
    Route::middleware([\App\Http\Middleware\AdminOnly::class])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::get('/users/{user}', [AdminController::class, 'showUser'])->name('users.show');
        Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
        Route::delete('/users/{user}', [AdminController::class, 'deleteUser'])->name('users.delete');
        Route::post('/users/{user}/impersonate', [AdminController::class, 'impersonateUser'])->name('users.impersonate');
        Route::get('/stop-impersonating', [AdminController::class, 'stopImpersonating'])->name('stopImpersonating');
        Route::get('/plans', [AdminController::class, 'plans'])->name('plans');
        Route::post('/plans', [AdminController::class, 'storePlan'])->name('plans.store');
        Route::put('/plans/{plan}', [AdminController::class, 'updatePlan'])->name('plans.update');
        Route::delete('/plans/{plan}', [AdminController::class, 'deletePlan'])->name('plans.delete');
        Route::get('/channels', [AdminController::class, 'channels'])->name('channels');
        Route::patch('/channels/{channel}/toggle', [AdminController::class, 'toggleChannel'])->name('channels.toggle');
        Route::delete('/channels/{channel}', [AdminController::class, 'deleteChannel'])->name('channels.delete');
        Route::get('/profile', [AdminController::class, 'profile'])->name('profile');
        Route::put('/profile', [AdminController::class, 'updateProfile'])->name('profile.update');
        Route::put('/profile/password', [AdminController::class, 'updatePassword'])->name('profile.password.update');
        Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
        Route::post('/settings', [AdminController::class, 'updateSettings'])->name('settings.update');
        Route::post('/settings/test-email', [AdminController::class, 'testEmail'])->name('settings.testEmail');
        Route::post('/settings/test-ai', [AdminController::class, 'testAiProvider'])->name('settings.testAi');
        Route::post('/settings/upload-branding', [AdminController::class, 'uploadBrandingAsset'])->name('settings.uploadBranding');
        Route::delete('/settings/branding/{asset}', [AdminController::class, 'deleteBrandingAsset'])->name('settings.deleteBranding');

        // Widgets management
        Route::get('/widgets', [AdminController::class, 'widgets'])->name('widgets');
        Route::post('/widgets/{widget}/toggle', [AdminController::class, 'toggleWidget'])->name('widgets.toggle');
        Route::delete('/widgets/{widget}', [AdminController::class, 'deleteWidget'])->name('widgets.delete');

        // Conversations management
        Route::get('/conversations', [AdminController::class, 'conversations'])->name('conversations');
        Route::delete('/conversations/{conversation}', [AdminController::class, 'deleteConversation'])->name('conversations.delete');

        // Reports / Analytics
        Route::get('/reports', [AdminController::class, 'reports'])->name('reports');

        // Broadcasts
        Route::get('/broadcasts', [AdminController::class, 'broadcasts'])->name('broadcasts');
        Route::post('/broadcasts', [AdminController::class, 'sendBroadcast'])->name('broadcasts.send');

        // System health
        Route::get('/system', [AdminController::class, 'system'])->name('system');
        Route::post('/system/cache-clear', [AdminController::class, 'clearCache'])->name('system.clearCache');
        Route::post('/system/queue-retry', [AdminController::class, 'retryFailedJobs'])->name('system.retryJobs');
        Route::post('/system/queue-flush', [AdminController::class, 'flushFailedJobs'])->name('system.flushJobs');
        Route::post('/system/artisan', [AdminController::class, 'runArtisan'])->name('system.artisan');
        Route::get('/system/logs', [AdminController::class, 'viewLogs'])->name('system.logs');

        // Activity log
        Route::get('/activity', [AdminController::class, 'activity'])->name('activity');
    });
});
