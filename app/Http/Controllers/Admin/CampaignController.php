<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function index(): View
    {
        return view('admin.campaigns.index', [
            'campaigns' => Campaign::withCount('products')->orderByDesc('starts_at')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.campaigns.form', [
            'campaign' => new Campaign,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $campaign = Campaign::create($data);

        if (! empty($data['product_ids'])) {
            $campaign->products()->sync($data['product_ids']);
        }

        return redirect()->route('admin.campaigns.index')->with('status', 'Campaign created.');
    }

    public function edit(Campaign $campaign): View
    {
        $campaign->load('products');

        return view('admin.campaigns.form', [
            'campaign' => $campaign,
            'products' => Product::where('status', 'published')->orderBy('name')->get(),
            'selectedProductIds' => $campaign->products->pluck('id')->toArray(),
        ]);
    }

    public function update(Request $request, Campaign $campaign): RedirectResponse
    {
        $data = $this->validated($request);
        $campaign->update($data);

        $campaign->products()->sync($data['product_ids'] ?? []);

        return back()->with('status', 'Campaign updated.');
    }

    public function destroy(Campaign $campaign): RedirectResponse
    {
        $campaign->products()->detach();
        $campaign->delete();

        return redirect()->route('admin.campaigns.index')->with('status', 'Campaign deleted.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:campaigns,slug,'.($request->route('campaign')?->id ?? ''),
            'description' => 'nullable|string|max:2000',
            'theme' => 'required|in:default,flash,seasonal,clearance',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after_or_equal:starts_at',
            'is_active' => 'nullable|boolean',
            'priority' => 'nullable|integer|min:0',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
        ]);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
