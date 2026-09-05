<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.categories.index', [
            'categories' => Category::with('parent')->withCount('products')->orderBy('parent_id')->orderBy('sort_order')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.categories.form', ['category' => new Category, 'categories' => Category::whereNull('parent_id')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        Category::create($data);
        cache()->forget('nav.categories');

        return redirect()->route('admin.categories.index')->with('status', 'Category created.');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.form', ['category' => $category, 'categories' => Category::whereNull('parent_id')->where('id', '!=', $category->id)->get()]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $category->update($this->validated($request));
        cache()->forget('nav.categories');

        return back()->with('status', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        abort_if($category->products()->exists(), 422, 'Category has products.');
        AuditLog::record('category.deleted', $category);
        $category->delete();
        cache()->forget('nav.categories');

        return redirect()->route('admin.categories.index')->with('status', 'Category deleted.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:categories,slug,'.($request->route('category')?->id ?? ''),
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string|max:2000',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'intro_content' => 'nullable|string',
        ]);

        if (! empty($data['parent_id']) && (int) $data['parent_id'] === (int) ($request->route('category')?->id ?? 0)) {
            abort(422, 'A category cannot be its own parent.');
        }

        // Auto-generate slug from name if empty (defensive - validation ensures it's present)
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        return $data;
    }
}
