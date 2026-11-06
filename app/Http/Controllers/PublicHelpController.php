<?php

namespace App\Http\Controllers;

use App\Models\HelpArticle;
use App\Models\HelpCategory;
use App\Models\Widget;
use Illuminate\Http\Request;

/** Public help centre at /help/{widget uuid}: search, categories, articles and related articles. */
class PublicHelpController extends Controller
{
    private function widget(string $uuid): Widget
    {
        return Widget::where('uuid', $uuid)->firstOrFail();
    }

    public function index(Request $request, string $uuid)
    {
        $w = $this->widget($uuid);
        $q = mb_substr(trim((string) $request->get('q')), 0, 100);
        $results = $q !== '' ? HelpArticle::where('widget_id', $w->id)->where('is_published', true)
            ->where(fn ($x) => $x->where('title', 'like', "%{$q}%")->orWhere('body', 'like', "%{$q}%"))->limit(30)->get() : null;

        return view('help.index', [
            'widget' => $w, 'q' => $q, 'results' => $results,
            'categories' => HelpCategory::where('widget_id', $w->id)->withCount(['articles' => fn ($x) => $x->where('is_published', true)])->orderBy('sort')->orderBy('name')->get(),
            'popular' => HelpArticle::where('widget_id', $w->id)->where('is_published', true)->orderByDesc('views')->limit(6)->get(),
        ]);
    }

    public function category(string $uuid, string $slug)
    {
        $w = $this->widget($uuid);
        $c = HelpCategory::where('widget_id', $w->id)->where('slug', $slug)->firstOrFail();

        return view('help.category', ['widget' => $w, 'category' => $c, 'articles' => $c->articles()->where('is_published', true)->orderBy('title')->get()]);
    }

    public function article(string $uuid, string $slug)
    {
        $w = $this->widget($uuid);
        $a = HelpArticle::where('widget_id', $w->id)->where('slug', $slug)->where('is_published', true)->with('category')->firstOrFail();
        $a->increment('views');
        $related = HelpArticle::where('widget_id', $w->id)->where('is_published', true)->whereKeyNot($a->id)
            ->when($a->help_category_id, fn ($x) => $x->where('help_category_id', $a->help_category_id))->orderByDesc('views')->limit(5)->get();

        return view('help.article', ['widget' => $w, 'article' => $a, 'related' => $related]);
    }
}
