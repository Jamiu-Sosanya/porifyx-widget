<?php

namespace App\Jobs;

use App\Support\I18n;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/** Translate every missing interface string into one language, in batches, with the admin-chosen AI. */
class TranslateLocale implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 1800;
    public int $tries = 1;

    public function __construct(public string $locale) {}

    public function handle(): void
    {
        if (! isset(I18n::LOCALES[$this->locale]) || $this->locale === 'en') return;
        $done = DB::table('translations')->where('locale', $this->locale)->pluck('hash')->flip();
        $missing = array_values(array_filter(I18n::sources(), fn ($s) => ! isset($done[md5($s)])));
        $language = I18n::LOCALES[$this->locale] . ' (' . $this->locale . ')';

        foreach (array_chunk($missing, 60) as $batch) {
            try {
                $res = \App\Support\Ai::create([
                    'temperature' => 0.1, 'max_tokens' => 4000,
                    'messages' => [
                        ['role' => 'system', 'content' => "Translate user-interface strings of a business SaaS dashboard and chat widget from English into {$language}. "
                            . 'Keep them short and natural, keep punctuation, symbols, emoji, placeholders and brand names (PoriFyx, WordPress, Stripe, PayPal) unchanged. '
                            . 'Reply with ONLY a JSON object mapping each English string to its translation.'],
                        ['role' => 'user', 'content' => json_encode($batch, JSON_UNESCAPED_UNICODE)],
                    ],
                ]);
                $raw = trim(preg_replace('/^```(?:json)?|```$/m', '', $res->choices[0]->message->content));
                $map = json_decode($raw, true);
                if (! is_array($map)) continue;
                $rows = [];
                foreach ($batch as $src) {
                    if (isset($map[$src]) && is_string($map[$src]) && trim($map[$src]) !== '') {
                        $rows[] = ['locale' => $this->locale, 'hash' => md5($src), 'source' => $src, 'value' => mb_substr($map[$src], 0, 2000), 'created_at' => now(), 'updated_at' => now()];
                    }
                }
                if ($rows) DB::table('translations')->insertOrIgnore($rows);
            } catch (\Throwable $e) {
                Log::warning("Translation batch for {$this->locale} failed: " . $e->getMessage());
            }
        }
        I18n::forget($this->locale);
    }
}
