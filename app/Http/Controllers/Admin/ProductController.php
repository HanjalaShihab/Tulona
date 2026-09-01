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
use App\Models\ProductImage;
use App\Services\PriceTrackingService;
use App\Services\ProductMatchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $query = $request->query();

        $products = Product::with(['brand:id,name', 'category:id,name'])
            ->withCount('offers')
            ->withTrashed()
            ->when($query['q'] ?? null, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when($query['category_id'] ?? null, fn ($q, $id) => $q->where('category_id', $id))
            ->when($query['brand_id'] ?? null, fn ($q, $id) => $q->where('brand_id', $id))
            ->when($query['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when(isset($query['merchant_id']) && $query['merchant_id'] !== '', fn ($q) => $q->whereHas('offers', fn ($o) => $o->where('merchant_id', $query['merchant_id'])))
            ->when(($query['price_min'] ?? '') !== '', fn ($q) => $q->whereHas('offers', fn ($o) => $o->where('current_price', '>=', $query['price_min'])))
            ->when(($query['price_max'] ?? '') !== '', fn ($q) => $q->whereHas('offers', fn ($o) => $o->where('current_price', '<=', $query['price_max'])))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'categories' => Category::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
            'merchants' => Merchant::orderBy('name')->get(),
        ]);
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
        // Detect the same product before the unique-slug rule can reject the
        // form: reuse the existing product (all merchant offers then line up in
        // its Compare Stores section) instead of creating a duplicate.
        $candidate = new Product([
            'name' => trim((string) $request->input('name')),
            'category_id' => filled($request->input('category_id')) ? (int) $request->input('category_id') : null,
            'brand_id' => filled($request->input('brand_id')) ? (int) $request->input('brand_id') : null,
            'sku' => $request->input('sku') ?: null,
            'model_number' => $request->input('model_number') ?: null,
            'gtin' => $request->input('gtin') ?: null,
        ]);

        if (($match = app(ProductMatchService::class)->find($candidate)) !== null) {
            AuditLog::record('product.merged', $match, ['skipped' => $request->input('name')]);

            return redirect()
                ->route('admin.products.edit', $match)
                ->with('status', '“'.$request->input('name').'” already exists (id #'.$match->id.') — no duplicate created. Add the merchant offer there and it appears in Compare Stores.');
        }

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
            'has_free_plan' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean', 'is_trending' => 'nullable|boolean', 'is_top_selling' => 'nullable|boolean', 'is_editors_pick' => 'nullable|boolean',
            'is_best_value' => 'nullable|boolean', 'is_budget_pick' => 'nullable|boolean', 'is_premium_pick' => 'nullable|boolean',
            'status' => 'nullable|in:draft,pending_review,published,archived',
        ];

        $data = $request->validate($rules);

        // Unchecked checkboxes are absent from request — coerce to false so update clears them.
        foreach (['has_free_plan','is_featured','is_trending','is_top_selling','is_editors_pick','is_best_value','is_budget_pick','is_premium_pick'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }

        return $data;
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
            'offer_id' => $offer->id,
            'product_id' => $offer->product_id,
            'merchant_id' => $offer->merchant_id,
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
                    'boolean' => ['value_boolean' => in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'y', 'checked', 'on'], true), 'value_text' => in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'y', 'checked', 'on'], true) ? 'Yes' : 'No'],
                    default => ['value_text' => (string) $value],
                }
            );
        }

        return back()->with('status', 'Specifications saved.');
    }

    // ── Images ──────────────────────────────────────────────────────────────

    /** Add an image by URL or storage path (§9). First image becomes main. */
    public function storeImage(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'path' => 'required|string|max:2048',
            'alt_text' => 'nullable|string|max:255',
        ]);

        $isMain = $product->images()->where('is_main', true)->doesntExist();
        $sortOrder = (int) ($product->images()->max('sort_order') ?? 0) + 1;

        $image = $product->images()->create([
            'path' => $data['path'],
            'alt_text' => $data['alt_text'] ?? null,
            'is_main' => $isMain,
            'sort_order' => $sortOrder,
        ]);

        AuditLog::record('product.image_added', $product, ['image_id' => $image->id]);

        return back()->with('status', 'Image added.');
    }

    /** Update alt text or main-flag on an existing image. */
    public function updateImage(Request $request, ProductImage $image): RedirectResponse
    {
        $data = $request->validate([
            'alt_text' => 'nullable|string|max:255',
            'is_main' => 'nullable|boolean',
        ]);

        $image->update([
            'alt_text' => $data['alt_text'] ?? null,
            'is_main' => isset($data['is_main']) ? (bool) $data['is_main'] : $image->is_main,
        ]);

        if ($image->is_main) {
            $image->product->images()->whereKeyNot($image->id)->update(['is_main' => false]);
        }

        AuditLog::record('product.image_updated', $image->product, ['image_id' => $image->id]);

        return back()->with('status', 'Image updated.');
    }

    public function makeMainImage(ProductImage $image): RedirectResponse
    {
        $image->product->images()->update(['is_main' => false]);
        $image->update(['is_main' => true]);

        AuditLog::record('product.image_main', $image->product, ['image_id' => $image->id]);

        return back()->with('status', 'Primary image set.');
    }

    /** Reorder an image up/down within its product. */
    public function moveImage(Request $request, ProductImage $image): RedirectResponse
    {
        $direction = $request->query('dir') === 'up' ? -1 : 1;
        $product = $image->product;
        $siblings = $product->images()->orderBy('sort_order')->get();

        $index = $siblings->search(fn ($i) => $i->id === $image->id);
        $swap = $index + $direction;

        if ($swap < 0 || $swap >= $siblings->count()) {
            return back();
        }

        $other = $siblings[$swap];
        $tmp = $image->sort_order;
        $image->update(['sort_order' => $other->sort_order]);
        $other->update(['sort_order' => $tmp]);

        return back();
    }

    /** Remove an image; reassign main to the next available if needed. */
    public function destroyImage(ProductImage $image): RedirectResponse
    {
        $product = $image->product;
        $wasMain = $image->is_main;
        $image->delete();

        if ($wasMain) {
            $product->images()->orderBy('sort_order')->first()?->update(['is_main' => true]);
        }

        AuditLog::record('product.image_removed', $product, ['image_id' => $image->id]);

        return back()->with('status', 'Image removed.');
    }

    /** Bulk catalogue actions (§48): publish / unpublish / archive / delete / category. */
    public function bulkAction(Request $request): RedirectResponse
    {
        if ($request->method() !== 'POST') {
            abort(405);
        }

        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:products,id',
            'action' => 'required|in:publish,unpublish,archive,delete,category',
            'category_id' => 'required_if:action,category|exists:categories,id',
        ]);

        $ids = $data['ids'];
        $count = 0;

        DB::transaction(function () use ($data, $ids, &$count) {
            match ($data['action']) {
                'publish' => $count = Product::withTrashed()->whereIn('id', $ids)->get()
                    ->each(fn ($p) => $p->restore())
                    ->each(fn ($p) => $p->update(['status' => 'published']))
                    ->count(),
                'unpublish' => $count = Product::whereIn('id', $ids)->update(['status' => 'draft']),
                'archive' => $count = Product::withoutTrashed()->whereIn('id', $ids)->get()
                    ->each(fn ($p) => $p->update(['status' => 'archived']))
                    ->each->delete()
                    ->count(),
                'delete' => $count = Product::withTrashed()->whereIn('id', $ids)->forceDelete(),
                'category' => $count = Product::whereIn('id', $ids)->update(['category_id' => $data['category_id']]),
            };
        });

        AuditLog::record('product.bulk_'.$data['action'], null, ['ids' => $ids, 'count' => $count, 'category_id' => $data['category_id'] ?? null]);

        return back()->with('status', "Bulk action '{$data['action']}' applied to {$count} product(s).");
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
