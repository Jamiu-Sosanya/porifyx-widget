<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Widget;
use App\Services\MultiProviderAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Public landing page and its live demo assistant.
 * The assistant uses the first configured AI provider; with no key set it
 * answers from the built-in FAQ so the demo never goes silent.
 */
class LandingController extends Controller
{
    /** Shared by the FAQ section and the offline demo assistant. [keywords, question, answer] */
    public const FAQ = [
        [['price', 'pricing', 'cost', 'plan', 'pay', 'cheap', 'expensive', 'trial', 'free'], 'How much does it cost?', 'Every plan starts with a free trial and no card is required. Pick monthly or yearly billing in the pricing section below. Yearly billing is cheaper per month.'],
        [['model', 'gpt', 'claude', 'gemini', 'openai', 'llm', 'groq', 'openrouter', 'provider'], 'Which AI models can I use?', 'OpenAI, Anthropic Claude, Google Gemini, Groq and OpenRouter. Each assistant can use a different model. If one provider fails, a backup provider takes over automatically.'],
        [['install', 'setup', 'set up', 'embed', 'code', 'script', 'wordpress', 'shopify', 'add', 'website'], 'How do I add it to my website?', 'Paste one script tag before </body>. It works on WordPress, Shopify, Webflow, Wix and any custom site, and it loads asynchronously so your pages stay fast.'],
        [['train', 'knowledge', 'pdf', 'document', 'learn', 'data', 'content', 'url'], 'How do I train it?', 'Add your website URLs, upload PDFs and documents, or paste question-and-answer pairs. Training runs in the background, and every version is kept so you can roll back.'],
        [['lead', 'email', 'capture', 'crm', 'sales', 'score', 'qualify'], 'How does lead capture work?', 'The assistant collects contact details during the conversation and scores each lead cold, warm or hot. Leads can go to your CRM through webhooks or automations, or be exported to Excel and PDF.'],
        [['human', 'agent', 'handoff', 'hand off', 'takeover', 'team', 'support', 'live'], 'Can my team take over a chat?', 'Yes. Agents can take over any conversation from the shared inbox. They see an AI summary, the visitor\'s sentiment and suggested replies.'],
        [['whatsapp', 'telegram', 'instagram', 'facebook', 'messenger', 'slack', 'discord', 'sms', 'channel'], 'Which channels are supported?', 'Your website plus WhatsApp, Telegram, Facebook Messenger, Instagram, X, Slack, Discord, SMS and email. One trained assistant answers on all of them.'],
        [['hallucinat', 'wrong', 'accurate', 'make up', 'made up', 'trust', 'strict'], 'Will it make things up?', 'Strict mode keeps answers inside your knowledge base. When the assistant does not know an answer, it says so and offers a human.'],
        [['secure', 'security', 'gdpr', 'privacy', 'data', 'safe', '2fa'], 'Is visitor data safe?', 'Data stays on your own server. Webhooks are HMAC-signed, logins support two-factor authentication, and admin access can be limited to approved IP addresses.'],
        [['language', 'spanish', 'french', 'german', 'arabic', 'multilingual', 'translate'], 'Does it speak other languages?', 'Yes. It replies in the visitor\'s language, and you can set welcome messages and placeholders for each language.'],
    ];

    public function index()
    {
        if (! config('branding.landing_enabled')) {
            return redirect()->route('login');
        }

        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();

        return view('welcome', ['plans' => $plans, 'faq' => self::FAQ]);
    }

    public function chat(Request $request, MultiProviderAiService $ai)
    {
        $data = $request->validate([
            'message'           => 'required|string|max:500',
            'history'           => 'nullable|array|max:10',
            'history.*.role'    => 'required|in:user,assistant',
            'history.*.content' => 'required|string|max:1000',
        ]);

        $provider = collect(['openrouter', 'openai', 'anthropic', 'gemini', 'groq'])
            ->first(fn ($p) => filled(config("ai.{$p}.key")));

        if ($provider) {
            try {
                $messages = array_merge(
                    [['role' => 'system', 'content' => $this->systemPrompt()]],
                    $data['history'] ?? [],
                    [['role' => 'user', 'content' => $data['message']]],
                );
                $widget = new Widget(['ai_provider' => $provider, 'temperature' => 0.4]);
                $reply  = trim($ai->chat($widget, $messages, 300)['content'] ?? '');
                if ($reply !== '') {
                    return response()->json(['reply' => $reply, 'ai' => true]);
                }
            } catch (\Throwable $e) {
                Log::warning('Landing demo chat failed: ' . $e->getMessage());
            }
        }

        return response()->json(['reply' => self::faqAnswer($data['message']), 'ai' => false]);
    }

    /** Best keyword match from the FAQ, or a helpful default. */
    public static function faqAnswer(string $message): string
    {
        $text = Str::lower($message);

        if (preg_match('/^\s*(hi|hello|hey|good (morning|afternoon|evening))\b/', $text)) {
            return 'Hello! I can explain pricing, setup, training, lead capture, channels or the AI models. What would you like to know?';
        }

        $best = null;
        $bestScore = 0;
        foreach (self::FAQ as [$keywords, , $answer]) {
            $score = collect($keywords)->filter(fn ($k) => str_contains($text, $k))->count();
            if ($score > $bestScore) {
                [$best, $bestScore] = [$answer, $score];
            }
        }

        return $best ?? 'Good question. I can help with pricing, installation, training, lead capture, live handoff, channels and AI models. You can also start a free trial and try it on your own site in a few minutes.';
    }

    private function systemPrompt(): string
    {
        $facts = collect(self::FAQ)->map(fn ($f) => "Q: {$f[1]}\nA: {$f[2]}")->implode("\n\n");
        $name  = config('branding.name');

        return "You are the assistant on the {$name} website. {$name} is an AI chat assistant that businesses embed on their websites to answer questions, capture and score leads, and hand conversations to human agents.\n"
            . "Reply in 1 to 3 short sentences, in plain text with no markdown. Be friendly and specific. Use only these facts. If you are unsure, suggest starting the free trial or emailing " . config('branding.support_email') . ".\n\n{$facts}";
    }
}
