<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Support\HtmlSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.articles.index', [
            'articles' => Article::when($request->query('type'), fn ($q, $t) => $q->where('type', $t))
                ->latest('updated_at')->paginate(20)->withQueryString(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.articles.form', [
            'article' => new Article(['type' => $request->query('type', 'guide'), 'status' => 'draft']),
            'categories' => Category::whereNull('parent_id')->get(),
            'products' => Product::where('status', 'published')->with('brand:id,name')->limit(500)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $article = Article::create($this->validated($request));
        $this->syncPicks($request, $article);
        AuditLog::record('article.created', $article);

        return redirect()->route('admin.articles.edit', $article)->with('status', 'Article saved.');
    }

    public function edit(Article $article): View
    {
        return view('admin.articles.form', [
            'article' => $article,
            'categories' => Category::whereNull('parent_id')->get(),
            'products' => Product::where('status', 'published')->with('brand:id,name')->limit(500)->get(),
        ]);
    }

    public function update(Request $request, Article $article): RedirectResponse
    {
        $wasDraft = $article->status === 'draft';
        $article->update($this->validated($request));
        $this->syncPicks($request, $article);
        AuditLog::record($wasDraft && $article->status === 'published' ? 'article.published' : 'article.edited', $article);

        return back()->with('status', 'Article saved.');
    }

    public function destroy(Article $article): RedirectResponse
    {
        AuditLog::record('article.deleted', $article);
        $article->delete();

        return redirect()->route('admin.articles.index')->with('status', 'Article deleted.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:articles,slug,'.($request->route('article')?->id ?? ''),
            'type' => 'required|in:guide,review',
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'featured_image' => 'nullable|url|max:2048',
            'category_id' => 'nullable|exists:categories,id',
            'author' => 'nullable|string|max:120',
            'status' => 'required|in:draft,published',
            'published_at' => 'nullable|date',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'canonical_url' => 'nullable|url|max:2048',
            'og_image' => 'nullable|url|max:2048',
            'selection_criteria' => 'nullable|array',
        ]);

        if (($data['status'] ?? '') === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        if (isset($data['content'])) {
            $data['content'] = HtmlSanitizer::sanitize($data['content']);
        }

        return $data;
    }

    protected function syncPicks(Request $request, Article $article): void
    {
        $picks = $request->input('picks', []);
        $rows = collect($picks)
            ->filter(fn ($p) => ! empty($p['product_id']))
            ->mapWithKeys(fn ($p, $i) => [$p['product_id'] => ['blurb' => $p['blurb'] ?? null, 'pick_label' => $p['pick_label'] ?? null, 'sort_order' => (int) $i]]);
        $article->products()->sync($rows);
    }
}
