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
use App\Http\Controllers\SiteController;
use App\Http\Controllers\WidgetController;
use Illuminate\Support\Facades\Route;

// Installer routes
require __DIR__.'/installer.php';

// Offline page (no auth required)
Route::get('/offline', function () {
    return view('offline');
})->name('offline');

// Landing page
Route::get('/', [\App\Http\Controllers\LandingController::class, 'index'])->name('home');
Route::post('/demo-chat', [\App\Http\Controllers\LandingController::class, 'chat'])->middleware('throttle:20,1')->name('demo.chat');
Route::post('/demo-site', [\App\Http\Controllers\LandingController::class, 'site'])->middleware('throttle:6,1')->name('demo.site');
// Public site pages (footer links)

foreach (['about', 'careers', 'privacy', 'terms', 'documentation', 'api-reference' => 'api.reference', 'changelog', 'roadmap'] as $slug => $name) {
    $slug = is_int($slug) ? $name : $slug;
    Route::get('/' . $slug, [SiteController::class, 'page'])->defaults('view', $slug)->name($name);
}
Route::get('/blog', [SiteController::class, 'blog'])->name('blog');
Route::get('/blog/{slug}', [SiteController::class, 'post'])->name('blog.post');
Route::get('/status', [SiteController::class, 'status'])->name('status');
Route::get('/contact', [SiteController::class, 'contact'])->name('contact');
Route::post('/contact', [SiteController::class, 'sendContact'])->middleware('throttle:5,1')->name('contact.send');
Route::get('/appointments/manage/{token}', [\App\Http\Controllers\AppointmentController::class, 'manage'])->name('appointments.manage');
Route::post('/appointments/manage/{token}/cancel', [\App\Http\Controllers\AppointmentController::class, 'cancel'])->middleware('throttle:10,1')->name('appointments.cancel');
Route::post('/appointments/manage/{token}/reschedule', [\App\Http\Controllers\AppointmentController::class, 'visitorReschedule'])->middleware('throttle:10,1')->name('appointments.reschedule.visitor');

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

        // Bookings
        Route::get('/appointments', [\App\Http\Controllers\AppointmentController::class, 'index'])->name('appointments.index');
        Route::patch('/appointments/{appointment}', [\App\Http\Controllers\AppointmentController::class, 'update'])->name('appointments.update');
        Route::get('/widgets/{widget}/booking', [\App\Http\Controllers\AppointmentController::class, 'settings'])->name('widgets.booking');
        Route::put('/widgets/{widget}/booking', [\App\Http\Controllers\AppointmentController::class, 'saveSettings'])->name('widgets.booking.update');
        Route::post('/appointments', [\App\Http\Controllers\AppointmentController::class, 'store'])->name('appointments.store');
        Route::patch('/appointments/{appointment}/reschedule', [\App\Http\Controllers\AppointmentController::class, 'reschedule'])->name('appointments.reschedule');
        Route::get('/widgets/{widget}/booking/calendar/{provider}', [\App\Http\Controllers\BookingIntegrationController::class, 'connectCalendar'])->name('widgets.booking.calendar.connect');
        Route::put('/widgets/{widget}/booking/calendar/{connection}', [\App\Http\Controllers\BookingIntegrationController::class, 'updateCalendar'])->name('widgets.booking.calendar.update');
        Route::delete('/widgets/{widget}/booking/calendar/{connection}', [\App\Http\Controllers\BookingIntegrationController::class, 'disconnectCalendar'])->name('widgets.booking.calendar.disconnect');
        Route::post('/widgets/{widget}/booking/crm', [\App\Http\Controllers\BookingIntegrationController::class, 'saveCrm'])->name('widgets.booking.crm.save');
        Route::patch('/widgets/{widget}/booking/crm/{connection}', [\App\Http\Controllers\BookingIntegrationController::class, 'toggleCrm'])->name('widgets.booking.crm.toggle');
        Route::delete('/widgets/{widget}/booking/crm/{connection}', [\App\Http\Controllers\BookingIntegrationController::class, 'deleteCrm'])->name('widgets.booking.crm.delete');
        Route::get('/calendar/callback/{provider}', [\App\Http\Controllers\BookingIntegrationController::class, 'calendarCallback'])->name('calendar.callback');
        Route::post('/widgets/{widget}/duplicate', [WidgetController::class, 'duplicate'])->name('widgets.duplicate');
        Route::get('/widgets/{widget}/wordpress-plugin', [WidgetController::class, 'wordpressPlugin'])->name('widgets.wordpress');
        Route::get('/widgets/{widget}/playground', [WidgetController::class, 'playground'])->name('widgets.playground');
        Route::get('/widgets/{widget}/engagement', [\App\Http\Controllers\EngagementController::class, 'show'])->name('widgets.engagement');
        Route::put('/widgets/{widget}/engagement', [\App\Http\Controllers\EngagementController::class, 'save'])->name('widgets.engagement.save');
        Route::post('/widgets/{widget}/triggers', [\App\Http\Controllers\EngagementController::class, 'storeTrigger'])->name('widgets.triggers.store');
        Route::put('/widgets/{widget}/triggers/{trigger}', [\App\Http\Controllers\EngagementController::class, 'updateTrigger'])->name('widgets.triggers.update');
        Route::delete('/widgets/{widget}/triggers/{trigger}', [\App\Http\Controllers\EngagementController::class, 'destroyTrigger'])->name('widgets.triggers.destroy');
        Route::get('/widgets/{widget}/lead-form', [\App\Http\Controllers\LeadFormController::class, 'show'])->name('widgets.leadform');
        Route::put('/widgets/{widget}/lead-form', [\App\Http\Controllers\LeadFormController::class, 'save'])->name('widgets.leadform.save');
        Route::get('/widgets/{widget}/help', [\App\Http\Controllers\HelpCenterController::class, 'index'])->name('widgets.help');
        Route::post('/widgets/{widget}/help/categories', [\App\Http\Controllers\HelpCenterController::class, 'storeCategory'])->name('widgets.help.categories.store');
        Route::delete('/widgets/{widget}/help/categories/{category}', [\App\Http\Controllers\HelpCenterController::class, 'destroyCategory'])->name('widgets.help.categories.destroy');
        Route::post('/widgets/{widget}/help/articles', [\App\Http\Controllers\HelpCenterController::class, 'saveArticle'])->name('widgets.help.articles.save');
        Route::delete('/widgets/{widget}/help/articles/{article}', [\App\Http\Controllers\HelpCenterController::class, 'destroyArticle'])->name('widgets.help.articles.destroy');
        Route::get('/widgets/{widget}/flows', [\App\Http\Controllers\FlowController::class, 'index'])->name('widgets.flows');
        Route::post('/widgets/{widget}/flows', [\App\Http\Controllers\FlowController::class, 'save'])->name('widgets.flows.save');
        Route::post('/widgets/{widget}/flows/test', [\App\Http\Controllers\FlowController::class, 'test'])->name('widgets.flows.test');
        Route::delete('/widgets/{widget}/flows/{flow}', [\App\Http\Controllers\FlowController::class, 'destroy'])->name('widgets.flows.destroy');
        Route::get('/widgets/{widget}/answers', [\App\Http\Controllers\AnswerController::class, 'index'])->name('widgets.answers');
        Route::post('/widgets/{widget}/answers', [\App\Http\Controllers\AnswerController::class, 'store'])->name('widgets.answers.store');
        Route::put('/widgets/{widget}/answers/{answer}', [\App\Http\Controllers\AnswerController::class, 'update'])->name('widgets.answers.update');
        Route::delete('/widgets/{widget}/answers/{answer}', [\App\Http\Controllers\AnswerController::class, 'destroy'])->name('widgets.answers.destroy');
        Route::post('/widgets/{widget}/gaps/{gap}/dismiss', [\App\Http\Controllers\AnswerController::class, 'dismissGap'])->name('widgets.gaps.dismiss');
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
        Route::get('/visitors/{visitor}/export', [\App\Http\Controllers\VisitorDataController::class, 'export'])->name('visitors.export');
        Route::delete('/visitors/{visitor}', [\App\Http\Controllers\VisitorDataController::class, 'erase'])->name('visitors.erase');
        Route::put('/conversations/{conversation}/tags', [ConversationController::class, 'tags'])->name('conversations.tags');
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
        Route::get('/tickets', [\App\Http\Controllers\TicketController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/create', [\App\Http\Controllers\TicketController::class, 'create'])->name('tickets.create');
        Route::post('/tickets', [\App\Http\Controllers\TicketController::class, 'store'])->name('tickets.store');
        Route::put('/tickets/settings', [\App\Http\Controllers\TicketController::class, 'saveSettings'])->name('tickets.settings');
        Route::get('/tickets/{ticket}', [\App\Http\Controllers\TicketController::class, 'show'])->name('tickets.show');
        Route::post('/tickets/{ticket}/reply', [\App\Http\Controllers\TicketController::class, 'reply'])->name('tickets.reply');
        Route::put('/tickets/{ticket}', [\App\Http\Controllers\TicketController::class, 'update'])->name('tickets.update');
        Route::post('/tickets/{ticket}/merge', [\App\Http\Controllers\TicketController::class, 'merge'])->name('tickets.merge');
        Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');
        Route::get('/leads/export', [LeadController::class, 'export'])->name('leads.export');
        Route::put('/leads/pipeline', [LeadController::class, 'pipeline'])->name('leads.pipeline');
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
    // Paid plans activate only after payment (Stripe Checkout or an approved bank transfer).
    Route::post('/settings/billing/change-plan', [\App\Http\Controllers\BillingController::class, 'change'])->name('settings.billing.changePlan');
    Route::get('/settings/billing/bank-transfer', [\App\Http\Controllers\BillingController::class, 'bank'])->name('billing.bank');
    Route::post('/settings/billing/bank-transfer', [\App\Http\Controllers\BillingController::class, 'bankSubmit'])->name('billing.bank.submit');
    Route::get('/settings/billing/portal', [\App\Http\Controllers\BillingController::class, 'portal'])->name('billing.portal');
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
        Route::get('/inbox', [SiteController::class, 'inbox'])->name('inbox');
        Route::get('/cookie-consent', [\App\Http\Controllers\Admin\ConsentController::class, 'edit'])->name('consent');
        Route::put('/cookie-consent', [\App\Http\Controllers\Admin\ConsentController::class, 'update'])->name('consent.update');
        Route::patch('/inbox/{contactRequest}', [SiteController::class, 'toggleHandled'])->name('inbox.toggle');
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
        Route::get('/monitor', [\App\Http\Controllers\Admin\ToolsController::class, 'monitor'])->name('monitor');
        Route::get('/search', [\App\Http\Controllers\Admin\ToolsController::class, 'search'])->name('search');
        Route::get('/changelog', [\App\Http\Controllers\Admin\ToolsController::class, 'changelog'])->name('changelog');
        Route::post('/changelog', [\App\Http\Controllers\Admin\ToolsController::class, 'storeChangelog'])->name('changelog.store');
        Route::post('/changelog/{id}/toggle', [\App\Http\Controllers\Admin\ToolsController::class, 'toggleChangelog'])->whereNumber('id')->name('changelog.toggle');
        Route::delete('/changelog/{id}', [\App\Http\Controllers\Admin\ToolsController::class, 'destroyChangelog'])->whereNumber('id')->name('changelog.destroy');
        Route::get('/payments', [\App\Http\Controllers\Admin\PaymentController::class, 'index'])->name('payments');
        Route::post('/payments/{payment}/approve', [\App\Http\Controllers\Admin\PaymentController::class, 'approve'])->name('payments.approve');
        Route::post('/payments/{payment}/reject', [\App\Http\Controllers\Admin\PaymentController::class, 'reject'])->name('payments.reject');
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

// Customer ticket page (signed link from the ticket emails)
Route::get('/t/{id}/{token}', [\App\Http\Controllers\PublicTicketController::class, 'show'])->whereNumber('id')->name('tickets.public');
Route::post('/t/{id}/{token}', [\App\Http\Controllers\PublicTicketController::class, 'reply'])->whereNumber('id')->middleware('throttle:10,1')->name('tickets.public.reply');

// Public help centre
Route::get('/help/{uuid}', [\App\Http\Controllers\PublicHelpController::class, 'index'])->name('help.index');
Route::get('/help/{uuid}/c/{slug}', [\App\Http\Controllers\PublicHelpController::class, 'category'])->name('help.category');
Route::get('/help/{uuid}/a/{slug}', [\App\Http\Controllers\PublicHelpController::class, 'article'])->name('help.article');

// Product changelog
Route::get('/changelog', fn () => view('changelog', ['entries' => \Illuminate\Support\Facades\DB::table('changelog_entries')->whereNotNull('published_at')->latest('published_at')->limit(50)->get()]))->name('changelog');
Route::post('/changelog/seen', function () {
    auth()->user()?->update(['changelog_seen_at' => now()]);
    return response()->noContent();
})->middleware('auth')->name('changelog.seen');

// SEO: sitemap of public pages, blog posts and published help articles
Route::get('/sitemap.xml', function () {
    $urls = collect(['/', '/about', '/blog', '/contact', '/documentation', '/api-reference', '/roadmap', '/status', '/careers', '/privacy', '/terms', '/changelog'])->map(fn ($p) => url($p));
    $urls = $urls->merge(collect(array_keys(\App\Http\Controllers\SiteController::POSTS))->map(fn ($slug) => url('/blog/' . $slug)));
    $urls = $urls->merge(\App\Models\HelpArticle::where('is_published', true)->with('widget:id,uuid,is_active')->limit(5000)->get()
        ->filter(fn ($a) => $a->widget?->is_active)->map(fn ($a) => route('help.article', [$a->widget->uuid, $a->slug])));
    $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
        . $urls->map(fn ($u) => '<url><loc>' . e($u) . '</loc></url>')->implode('') . '</urlset>';
    return response($xml, 200, ['Content-Type' => 'application/xml']);
})->name('sitemap');
