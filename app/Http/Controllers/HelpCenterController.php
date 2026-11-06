<?php

namespace App\Http\Controllers;

use App\Models\HelpArticle;
use App\Models\HelpCategory;
use App\Models\Widget;
use Illuminate\Http\Request;

/** Owner side of the public help centre (one per widget/brand). */
class HelpCenterController extends Controller
{
    public function index(Request $request, Widget $widget)
    {
        $this->authorize('update', $widget);

        return view('widgets.help-center', [
            'widget'     => $widget,
            'categories' => HelpCategory::where('widget_id', $widget->id)->withCount('articles')->orderBy('sort')->orderBy('name')->get(),
            'articles'   => HelpArticle::where('widget_id', $widget->id)->with('category')->latest('updated_at')->get(),
            'editing'    => $request->filled('edit') ? HelpArticle::where('widget_id', $widget->id)->find($request->integer('edit')) : null,
        ]);
    }

    public function storeCategory(Request $request, Widget $widget)
    {
        $this->authorize('update', $widget);
        $d = $request->validate(['name' => 'required|string|max:80', 'icon' => 'nullable|string|max:16', 'description' => 'nullable|string|max:300']);
        HelpCategory::create($d + ['widget_id' => $widget->id, 'slug' => HelpArticle::uniqueSlug($widget->id, $d['name'], null, HelpCategory::class)]);

        return back()->with('success', 'Category added.');
    }

    public function destroyCategory(Widget $widget, HelpCategory $category)
    {
        $this->authorize('update', $widget);
        abort_unless($category->widget_id === $widget->id, 404);
        $category->delete(); // its articles stay, uncategorised

        return back()->with('success', 'Category deleted.');
    }

    public function saveArticle(Request $request, Widget $widget)
    {
        $this->authorize('update', $widget);
        $d = $request->validate([
            'id'               => 'nullable|integer',
            'title'            => 'required|string|max:200',
            'body'             => 'required|string|max:50000',
            'help_category_id' => 'nullable|integer',
        ]);
        $article = filled($d['id'] ?? null) ? HelpArticle::where('widget_id', $widget->id)->findOrFail($d['id']) : new HelpArticle(['widget_id' => $widget->id]);
        $article->fill([
            'title' => $d['title'], 'body' => $d['body'], 'is_published' => $request->boolean('is_published'),
            'help_category_id' => HelpCategory::where('widget_id', $widget->id)->whereKey($d['help_category_id'] ?? 0)->value('id'),
            'slug' => HelpArticle::uniqueSlug($widget->id, $d['title'], $article->id),
        ])->save();

        return redirect()->route('widgets.help', $widget)->with('success', 'Article saved.');
    }

    public function destroyArticle(Widget $widget, HelpArticle $article)
    {
        $this->authorize('update', $widget);
        abort_unless($article->widget_id === $widget->id, 404);
        $article->delete();

        return back()->with('success', 'Article deleted.');
    }
}
