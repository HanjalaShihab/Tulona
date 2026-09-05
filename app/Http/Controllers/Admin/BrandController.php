<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function index(): View
    {
        return view('admin.brands.index', ['brands' => Brand::withCount('products')->orderBy('name')->paginate(30)]);
    }

    public function create(): View
    {
        return view('admin.brands.form', ['brand' => new Brand]);
    }

    public function store(Request $request): RedirectResponse
    {
        Brand::create($this->validated($request));

        return redirect()->route('admin.brands.index')->with('status', 'Brand created.');
    }

    public function edit(Brand $brand): View
    {
        return view('admin.brands.form', compact('brand'));
    }

    public function update(Request $request, Brand $brand): RedirectResponse
    {
        $brand->update($this->validated($request));

        return back()->with('status', 'Brand updated.');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        abort_unless($brand->products()->count() === 0, 422, 'Brand has products.');
        $brand->delete();

        return redirect()->route('admin.brands.index')->with('status', 'Brand deleted.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:brands,slug,'.($request->route('brand')?->id ?? ''),
            'logo_path' => 'nullable|url|max:2048',
            'description' => 'nullable|string|max:5000',
            'website_url' => 'nullable|url|max:2048',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
        ]);

        // Auto-generate slug from name if empty (defensive)
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        return $data;
    }
}
