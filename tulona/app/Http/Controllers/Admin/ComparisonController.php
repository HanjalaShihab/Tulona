<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Comparison;
use App\Models\Merchant;
use App\Models\Offer;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Comparison editor (§29–§37): build reusable product-vs-merchant comparisons. */
class ComparisonController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.comparisons.index', [
            'comparisons' => Comparison::withCount('products')
                ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
                ->latest('updated_at')->paginate(20)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('admin.comparisons.form', [
            'comparison' => new Comparison(['status' => 'draft']),
            'merchants' => Merchant::where('status', 'active')->orderBy('name')->get(),
            'products' => Product::published()->with('brand:id,name')->limit(500)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $comparison = Comparison::create($this->validated($request));
        AuditLog::record('comparison.created', $comparison);

        return redirect()->route('admin.comparisons.edit', $comparison)->with('status', 'Comparison created. Add products & refine the table.');
    }

    public function edit(Comparison $comparison): View
    {
        $comparison->load(['products.brand', 'products.offers.merchant']);
        $selectedProductIds = $comparison->products->pluck('id');
        $merchantIds = $comparison->offers()->pluck('offers.merchant_id')->unique()->values();

        return view('admin.comparisons.form', [
            'comparison' => $comparison,
            'merchants' => Merchant::where('status', 'active')->orderBy('name')->get(),
            'products' => Product::published()->with(['brand:id,name', 'offers.merchant'])->limit(500)->get(),
            'selectedProductIds' => $selectedProductIds,
            'merchantIds' => $merchantIds,
        ]);
    }

    public function update(Request $request, Comparison $comparison): RedirectResponse
    {
        $comparison->update($this->validated($request));
        AuditLog::record('comparison.updated', $comparison);

        return back()->with('status', 'Comparison saved.');
    }

    /** Persist the ordered product lineup + per-product picks/notes (§34). */
    public function syncProducts(Request $request, Comparison $comparison): RedirectResponse
    {
        $rows = collect($request->input('products', []))
            ->filter(fn ($p) => ! empty($p['product_id']))
            ->mapWithKeys(fn ($p, $i) => [
                $p['product_id'] => [
                    'sort_order' => (int) ($p['sort_order'] ?? $i),
                    'editorial_notes' => $p['editorial_notes'] ?? null,
                    'pick_label' => $p['pick_label'] ?? null,
                ],
            ]);
        $comparison->products()->sync($rows);
        AuditLog::record('comparison.products_synced', $comparison, ['count' => $rows->count()]);

        return back()->with('status', 'Product lineup saved.');
    }

    /** Persist merchant offer visibility + overrides (§34/§35). */
    public function syncOfferOverrides(Request $request, Comparison $comparison): RedirectResponse
    {
        $offers = $request->input('offers', []);

        if (is_array($offers)) {
            foreach ($offers as $offerId => $attrs) {
                if (! $comparison->offers()->wherePivot('offer_id', $offerId)->exists()) {
                    continue;
                }

                $comparison->offers()->updateExistingPivot($offerId, [
                    'is_hidden' => (bool) ($attrs['is_hidden'] ?? false),
                    'override_price' => ($attrs['override_price'] ?? '') !== '' ? $attrs['override_price'] : null,
                    'override_availability' => ($attrs['override_availability'] ?? '') !== '' ? $attrs['override_availability'] : null,
                    'override_warranty' => ($attrs['override_warranty'] ?? '') !== '' ? $attrs['override_warranty'] : null,
                    'override_shipping' => ($attrs['override_shipping'] ?? '') !== '' ? $attrs['override_shipping'] : null,
                    'sort_order' => (int) ($attrs['sort_order'] ?? 0),
                ]);
            }
        }

        AuditLog::record('comparison.offers_synced', $comparison);

        return back()->with('status', 'Offer overrides saved.');
    }

    public function destroy(Comparison $comparison): RedirectResponse
    {
        AuditLog::record('comparison.deleted', $comparison);
        $comparison->delete();

        return redirect()->route('admin.comparisons.index')->with('status', 'Comparison deleted.');
    }

    /** Add offers to the comparison for a product from the selected merchants (§31). */
    public function addOffer(Request $request, Comparison $comparison): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'offer_ids' => 'required|array|min:1',
            'offer_ids.*' => 'exists:offers,id',
        ]);

        $rows = collect($request->input('offer_ids', []))->mapWithKeys(fn ($id, $i) => [
            $id => ['product_id' => $data['product_id'], 'sort_order' => $i, 'is_hidden' => false],
        ]);
        $comparison->offers()->syncWithoutDetaching($rows);

        return back()->with('status', 'Offers added to comparison.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:comparisons,slug,'.($request->route('comparison')?->id ?? ''),
            'introduction' => 'nullable|string|max:2000',
            'description' => 'nullable|string',
            'verdict' => 'nullable|string|max:5000',
            'notes' => 'nullable|string',
            'cta_text' => 'nullable|string|max:120',
            'status' => 'required|in:draft,published,archived',
            'featured' => 'boolean',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'canonical_url' => 'nullable|url|max:2048',
        ]);

        $comparison = $request->route('comparison');
        if (($data['status'] ?? '') === 'published' && ($comparison?->published_at === null)) {
            $data['published_at'] = now();
        }

        return $data;
    }
}
