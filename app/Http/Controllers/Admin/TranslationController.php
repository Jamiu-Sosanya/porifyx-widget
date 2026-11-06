<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\TranslateLocale;
use App\Support\I18n;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/** Translation Manager: see coverage per language, edit any string, translate the rest with AI. */
class TranslationController extends Controller
{
    public function index(Request $request)
    {
        $locale = I18n::normalize($request->get('locale')) ?? 'fr';
        if ($locale === 'en') $locale = 'fr';
        $q = mb_strtolower(trim((string) $request->get('q')));
        $done = DB::table('translations')->where('locale', $locale)->get()->keyBy('source');
        $all = collect(I18n::sources())->filter(fn ($s) => $q === '' || str_contains(mb_strtolower($s), $q) || str_contains(mb_strtolower((string) ($done[$s]->value ?? '')), $q));
        if ($request->get('show') === 'missing') $all = $all->reject(fn ($s) => isset($done[$s]));
        $page = max(1, (int) $request->get('page', 1));

        return view('admin.translations', [
            'locale' => $locale, 'q' => $q, 'done' => $done,
            'total' => count(I18n::sources()), 'count' => $done->count(),
            'counts' => DB::table('translations')->selectRaw('locale, count(*) c')->groupBy('locale')->pluck('c', 'locale'),
            'rows' => new LengthAwarePaginator($all->slice(($page - 1) * 50, 50)->values(), $all->count(), 50, $page, ['path' => $request->url(), 'query' => $request->query()]),
        ]);
    }

    public function save(Request $request)
    {
        $d = $request->validate(['locale' => 'required|string|max:10', 'values' => 'array', 'values.*' => 'nullable|string|max:2000']);
        $locale = I18n::normalize($d['locale']);
        abort_unless($locale && $locale !== 'en', 422);
        $sources = array_flip(I18n::sources());
        foreach ((array) ($d['values'] ?? []) as $hash => $value) {
            $src = $request->input("sources.$hash");
            if (! is_string($src) || ! isset($sources[$src]) || md5($src) !== $hash) continue;
            if (blank($value)) {
                DB::table('translations')->where('locale', $locale)->where('hash', $hash)->delete();
                continue;
            }
            DB::table('translations')->updateOrInsert(['locale' => $locale, 'hash' => $hash], ['source' => $src, 'value' => $value, 'is_override' => true, 'updated_at' => now(), 'created_at' => now()]);
        }
        I18n::forget($locale);
        \App\Support\Audit::log('translations.saved', null, ['locale' => $locale]);

        return back()->with('success', 'Translations saved.');
    }

    public function generate(Request $request)
    {
        $locale = I18n::normalize($request->input('locale'));
        abort_unless($locale && $locale !== 'en', 422);
        TranslateLocale::dispatch($locale);

        return back()->with('success', 'Translating the missing strings into ' . I18n::LOCALES[$locale] . ' in the background. Refresh in a few minutes.');
    }
}
