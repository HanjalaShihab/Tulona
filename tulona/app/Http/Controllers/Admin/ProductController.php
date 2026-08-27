<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttributeDefinition;
use App\Models\AuditLog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Merchant;
use App\Models\Offer;
use App\Models\Product;
use App\Services\PriceTrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::with(['brand:id,name', 'category:id,name'])
            ->withCount('offers')
            ->when($request->query('q'), fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.products.index', compact('products'));
    }

    public function create(): View
    {
        return view('admin.products.form', [
            'product' => new Product,
            'categories' => Category::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
            'merchants' => Merchant::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = ($data['slug'] ?? '') ?: str()->slug($data['name']);
        $product = Product::create($data);
        AuditLog::record('product.created', $product);

        return redirect()->route('admin.products.edit', $product)->with('status', 'Product created. Add offers next.');
    }

    public function edit(Product $product): View
    {
        $product->load(['attributes.definition', 'offers.merchant']);

        return view('admin.products.form', [
            'product' => $product,
            'categories' => Category::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
            'merchants' => Merchant::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->update($this->validated($request));
        AuditLog::record('product.edited', $product);

        return back()->with('status', 'Product updated.');
    }

    /** Archive keeps offer history intact (§31). */
    public function destroy(Product $product): RedirectResponse
    {
        AuditLog::record('product.archived', $product);
        $product->update(['status' => 'archived']);
        $product->delete();

        return redirect()->route('admin.products.index')->with('status', 'Product archived.');
    }

    protected function validated(Request $request): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:products,slug,'.($request->route('product')?->id ?? ''),
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'sku' => 'nullable|string|max:100',
            'model_number' => 'nullable|string|max:100',
            'gtin' => 'nullable|string|max:50',
            'product_type' => 'required|in:physical,digital',
            'short_description' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'summary_editorial' => 'nullable|string|max:2000',
            'pros' => 'nullable|array', 'cons' => 'nullable|array',
            'rating' => 'nullable|numeric|min:0|max:5',
            'pricing_model' => 'nullable|in:,free,freemium,subscription,one_time',
            'has_free_plan' => 'boolean',
            'is_featured' => 'boolean', 'is_trending' => 'boolean', 'is_editors_pick' => 'boolean',
            'is_best_value' => 'boolean', 'is_budget_pick' => 'boolean', 'is_premium_pick' => 'boolean',
            'status' => 'nullable|in:draft,pending_review,published,archived',
        ];

        return $request->validate($rules);
    }

    // ── Offers ──────────────────────────────────────────────────────────────

    public function storeOffer(Request $request, Product $product): RedirectResponse
    {
        $data = $this->offerValidated($request);

        // Price changes always flow through price tracking (§27) + audit (§93)
        $offer = DB::transaction(function () use ($data, $product) {
            $offer = Offer::updateOrCreate(
                ['product_id' => $product->id, 'merchant_id' => $data['merchant_id']],
                [...$data, 'source' => 'manual', 'last_synced_at' => now()]
            );

            $this->syncAffiliateOffer($offer, $data);

            app(PriceTrackingService::class)->recordPrice($offer, $offer->current_price);

            return $offer;
        });

        AuditLog::record('offer.modified', $offer, ['price' => $data['current_price']]);

        return back()->with('status', 'Offer saved.');
    }

    /** Keep the affiliate offer in sync with the merchant listing (§19). */
    protected function syncAffiliateOffer(Offer $offer, array $data): void
    {
        $offer->affiliateOffer()->updateOrCreate([], [
            'normal_product_url' => $data['external_url'] ?? $offer->external_url,
            'affiliate_url' => $data['affiliate_url'] ?? $offer->affiliate_url,
            'status' => 'manual',
            'generation_method' => 'manual',
            'generated_at' => now(),
        ]);
    }

    public function updateOffer(Request $request, Offer $offer): RedirectResponse
    {
        $data = $request->validate([
            'affiliate_url' => 'sometimes|required|url|max:2048',
            'current_price' => 'sometimes|nullable|numeric|min:0',
            'availability' => 'sometimes|required|in:in_stock,out_of_stock,preorder,unknown',
            'status' => 'sometimes|required|in:active,inactive',
        ]);

        DB::transaction(function () use ($offer, $data) {
            $oldPrice = (float) ($offer->current_price ?? 0);
            $offer->update([...$data, 'last_synced_at' => now()]);

            if (isset($data['affiliate_url'])) {
                $this->syncAffiliateOffer($offer, $data);
            }

            if (isset($data['current_price']) && (float) $data['current_price'] !== $oldPrice) {
                app(PriceTrackingService::class)->recordPrice($offer->fresh(), $offer->fresh()->current_price);
                AuditLog::record('price.changed', $offer, ['from' => $oldPrice, 'to' => $data['current_price']]);
            }
        });

        return back()->with('status', 'Offer updated.');
    }

    public function destroyOffer(Offer $offer): RedirectResponse
    {
        AuditLog::record('offer.removed', $offer);
        $offer->delete();

        return back()->with('status', 'Offer removed.');
    }

    /** Category-aware spec editor feeding the comparison tool (§17). */
    public function updateAttributes(Request $request, Product $product): RedirectResponse
    {
        $attrs = $request->validate(['attributes' => 'array', 'attributes.*' => 'nullable|string|max:255'])['attributes'] ?? [];

        foreach ($attrs as $definitionId => $value) {
            if (($value ?? '') === '') {
                $product->attributes()->where('attribute_definition_id', $definitionId)->delete();

                continue;
            }
            $def = AttributeDefinition::findOrFail($definitionId);
            $product->attributes()->updateOrCreate(
                ['attribute_definition_id' => $definitionId],
                match ($def->data_type) {
                    'number' => ['value_number' => is_numeric($value) ? (float) $value : null, 'value_text' => (string) $value],
                    'boolean' => ['value_boolean' => (bool) $value, 'value_text' => $value ? 'Yes' : 'No'],
                    default => ['value_text' => (string) $value],
                }
            );
        }

        return back()->with('status', 'Specifications saved.');
    }

    protected function offerValidated(Request $request): array
    {
        return $request->validate([
            'merchant_id' => 'required|exists:merchants,id',
            'affiliate_url' => 'required|url|max:2048',
            'external_url' => 'nullable|url|max:2048',
            'current_price' => 'nullable|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'currency' => 'required|size:3',
            'availability' => 'required|in:in_stock,out_of_stock,preorder,unknown',
            'shipping_info' => 'nullable|string|max:255',
            'deal_expires_at' => 'nullable|date|after:now',
        ]);
    }
}
