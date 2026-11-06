<?php

namespace App\Http\Controllers;

use App\Models\ContactRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

/** Public marketing pages, the live status page and the contact / sales form. */
class SiteController extends Controller
{
    /** Blog posts: slug => [title, summary, date, read minutes]. Bodies live in pages/blog/{slug}.blade.php. */
    public const POSTS = [
        'what-a-good-ai-assistant-actually-does' => ['What a good website assistant actually does all day', 'Most chat widgets answer questions. The useful ones notice intent, collect context and know when to step aside. A look at the jobs worth giving an assistant.', '2026-10-28', 7],
        'training-your-assistant-on-real-content' => ['Training your assistant on content you already have', 'You do not need a new knowledge base project. Your pricing page, help docs and the emails your team keeps rewriting are enough to start.', '2026-10-14', 6],
        'when-to-hand-a-chat-to-a-human' => ['When to hand a chat to a human, and how to do it well', 'Handoff is where most automated support falls apart. Clear triggers, full context and an honest message to the visitor fix most of it.', '2026-09-30', 6],
    ];

    public function page(string $view)
    {
        if ($view === 'privacy' && \App\Support\PrivacyPolicy::get()['enabled']) $view = 'privacy-custom';

        return view("pages.{$view}");
    }

    public function blog()
    {
        return view('pages.blog', ['posts' => self::POSTS]);
    }

    public function post(string $slug)
    {
        abort_unless(isset(self::POSTS[$slug]), 404);

        return view("pages.blog.{$slug}", ['post' => self::POSTS[$slug], 'slug' => $slug, 'posts' => self::POSTS]);
    }

    public function status()
    {
        $check = function (callable $probe) {
            $start = microtime(true);
            try {
                $ok = $probe() !== false;
            } catch (\Throwable $e) {
                $ok = false;
            }

            return ['ok' => $ok, 'ms' => (int) round((microtime(true) - $start) * 1000)];
        };

        $components = [
            'Website and dashboard' => ['Sign-in, dashboard, settings and billing pages.', ['ok' => true, 'ms' => 0]],
            'Widget API'            => ['Loads the chat widget and delivers visitor messages.', $check(fn () => DB::table('widgets')->limit(1)->count() >= 0)],
            'Database'              => ['Stores conversations, leads and account data.', $check(fn () => DB::select('select 1'))],
            'Cache'                 => ['Speeds up widget configuration and rate limiting.', $check(function () {
                Cache::put('status-probe', 1, 10);

                return Cache::get('status-probe') === 1;
            })],
            'AI responses'          => ['Generates replies through the connected AI providers.', ['ok' => collect(['openrouter', 'openai', 'anthropic', 'gemini', 'groq'])->contains(fn ($p) => filled(config("ai.{$p}.key"))), 'ms' => 0]],
        ];

        return view('pages.status', ['components' => $components, 'checkedAt' => now()]);
    }

    public function contact(Request $request)
    {
        $topic = array_key_exists($request->query('topic'), ContactRequest::TOPICS) ? $request->query('topic') : 'sales';

        return view('pages.contact', ['topic' => $topic]);
    }

    public function sendContact(Request $request)
    {
        // Honeypot: real people never see or fill this field.
        if (filled($request->input('fax'))) {
            return redirect()->route('contact')->with('contact_sent', true);
        }

        $data = $request->validate([
            'topic'     => ['required', Rule::in(array_keys(ContactRequest::TOPICS))],
            'name'      => 'required|string|max:120',
            'email'     => 'required|email:rfc|max:190',
            'company'   => 'nullable|string|max:150',
            'website'   => 'nullable|url|max:255',
            'team_size' => ['nullable', Rule::in(ContactRequest::TEAM_SIZES)],
            'volume'    => ['nullable', Rule::in(ContactRequest::VOLUMES)],
            'message'   => 'required|string|min:10|max:4000',
            'consent'   => 'accepted',
        ], ['consent.accepted' => 'Please confirm we can reply to you by email.']);

        unset($data['consent']);
        $lead = ContactRequest::create($data + ['ip' => $request->ip()]);

        try {
            $body = collect($lead->only(['name', 'email', 'company', 'website', 'team_size', 'volume']))
                ->filter()->map(fn ($v, $k) => ucfirst(str_replace('_', ' ', $k)) . ": {$v}")->implode("\n")
                . "\n\n" . $lead->message;
            Mail::raw($body, fn ($m) => $m->to(config('branding.support_email'))->replyTo($lead->email, $lead->name)
                ->subject('[' . ContactRequest::TOPICS[$lead->topic] . '] ' . $lead->name . ($lead->company ? " ({$lead->company})" : '')));
        } catch (\Throwable $e) {
            // Saved in the database either way; the team can still see it in the admin inbox.
            Log::warning('Contact email failed: ' . $e->getMessage());
        }

        return redirect()->route('contact')->with('contact_sent', $lead->name);
    }

    /** Admin: messages from the contact and sales form. */
    public function inbox(Request $request)
    {
        $requests = ContactRequest::query()
            ->when(array_key_exists($request->query('topic'), ContactRequest::TOPICS), fn ($q) => $q->where('topic', $request->query('topic')))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.inbox', ['requests' => $requests, 'open' => ContactRequest::whereNull('handled_at')->count()]);
    }

    public function toggleHandled(ContactRequest $contactRequest)
    {
        $contactRequest->update(['handled_at' => $contactRequest->handled_at ? null : now()]);

        return back();
    }
}
