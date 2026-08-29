<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Merchant;
use App\Models\ProductDraft;
use App\Services\CsvDraftService;
use App\Services\ProductPublishService;
use App\Services\UrlDraftService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * "Upload CSV → review and post" (Product Generator). Uploading a products CSV
 * creates one editable draft per row. Each draft is opened, edited and posted
 * individually by the admin — nothing is auto-published. Posting reuses the
 * same ProductPublishService as "Scrape and Post".
 */
class CsvDraftController extends Controller
{
    public function __construct(
        protected CsvDraftService $service,
    ) {}

    public function index(): View
    {
        return view('admin.csv-drafts.index', [
            'drafts' => ProductDraft::latest()->paginate(50),
            'merchants' => Merchant::orderBy('name')->get(['id', 'name', 'slug']),
            'pendingCount' => ProductDraft::where('status', '!=', 'posted')->count(),
        ]);
    }

    /** Step 1 — upload a CSV; every row becomes an editable draft. */
    public function upload(Request $request): RedirectResponse
    {
        $this->authorize('manage-products');

        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $file = $request->file('file');
        $path = $file->storeAs('private', now()->format('Ymd_His').'_'.$file->getClientOriginalName());

        try {
            $rows = $this->service->parse(Storage::path($path));
        } catch (\Throwable $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        $created = 0;
        foreach ($rows as $row) {
            $payload = $this->service->toDraftPayload($row);
            ProductDraft::create([
                'data' => $payload,
                'merchant_id' => $payload['merchant_id'] ?? null,
                'created_by' => auth()->id(),
                'status' => 'draft',
            ]);
            $created++;
        }

        return redirect()->route('admin.csv-drafts.index')
            ->with('status', "Imported {$created} product row(s) as drafts — open each one to review, edit and post.");
    }

    /** Step 2 — live generate: scrape a product-list URL into editable drafts (like Scrape & Post, but for many products). */
    public function generateFromUrl(Request $request, UrlDraftService $service): RedirectResponse
    {
        $this->authorize('manage-products');

        $data = $request->validate([
            'source_url' => 'required|url|max:2048',
            'category_id' => 'nullable|integer|exists:categories,id',
            'merchant_id' => 'nullable|integer|exists:merchants,id',
        ]);

        try {
            $result = $service->scrapeToDrafts($data['source_url'], $data['category_id'] ?? null, $data['merchant_id'] ?? null);
        } catch (\Throwable $e) {
            return back()->withErrors(['generate' => $e->getMessage()]);
        }

        if ($result['created'] === 0) {
            return back()->withErrors(['generate' => 'No products could be parsed from that URL. Check the link or the merchant connection.'.($result['errors'] ? " ({$result['errors']} row(s) errored)." : '')]);
        }

        $status = "Generated {$result['created']} product draft(s) from that URL — review and edit each one, then post.";
        if ($result['errors'] > 0) {
            $status .= " {$result['errors']} row(s) could not be parsed.";
        }

        return redirect()->route('admin.csv-drafts.index')->with('status', $status);
    }

    /** Step 4 (bulk) — post every not-yet-posted draft in one go. */
    public function postAll(ProductPublishService $publisher): RedirectResponse
    {
        $this->authorize('manage-products');

        $drafts = ProductDraft::where('status', '!=', 'posted')->get();

        if ($drafts->isEmpty()) {
            return back()->with('status', 'Nothing to post — no drafts awaiting publication.');
        }

        $posted = 0;
        $failed = 0;

        foreach ($drafts as $draft) {
            $payload = $this->validateDraftPayload($draft->data ?? []);
            if ($payload === null) {
                $draft->update(['status' => 'error', 'error' => 'Missing required fields (name, merchant, category, affiliate link).']);
                $failed++;

                continue;
            }

            try {
                $publisher->publish($payload, $draft->data ?? []);
                $draft->update(['status' => 'posted']);
                $posted++;
            } catch (\Throwable $e) {
                $draft->update(['status' => 'error', 'error' => $e->getMessage()]);
                $failed++;
            }
        }

        $message = "Posted {$posted} product(s).";
        if ($failed) {
            $message .= " {$failed} draft(s) failed — open each one to fix and post individually.";
        }

        return redirect()->route('admin.csv-drafts.index')->with('status', $message);
    }

    /** Validate a draft payload as if it came from the edit form; null when a required field is missing. */
    protected function validateDraftPayload(array $data): ?array
    {
        if (empty($data['name']) || empty($data['merchant_id']) || empty($data['affiliate_url'])) {
            return null;
        }

        $categoryId = $data['category_id'] ?? null;
        $categoryName = $data['category_slug'] ?? ($data['category'] ?? null);

        if (empty($categoryId) && empty($categoryName)) {
            return null; // category required
        }

        return [
            'name' => $data['name'],
            'merchant_id' => $data['merchant_id'],
            'category_id' => $categoryId ?: null,
            'subcategory_id' => $data['subcategory_id'] ?? null,
            'category' => empty($categoryId) ? $categoryName : null,
            'subcategory' => $data['subcategory'] ?? null,
            'affiliate_url' => $data['affiliate_url'],
            'current_price' => $data['current_price'] ?? null,
            'original_price' => $data['original_price'] ?? null,
            'currency' => $data['currency'] ?? 'BDT',
            'description' => $data['description'] ?? null,
            'image' => $data['image'] ?? null,
            'availability' => in_array($data['availability'] ?? null, ['in_stock', 'out_of_stock', 'preorder', 'unknown'], true) ? $data['availability'] : 'unknown',
            'sku' => $data['sku'] ?? null,
            'is_trending' => (bool) ($data['is_trending'] ?? false),
            'is_featured' => (bool) ($data['is_featured'] ?? false),
            'is_top_selling' => (bool) ($data['is_top_selling'] ?? false),
        ];
    }

    /** Step 2 — open a single draft for editing. */
    public function edit(ProductDraft $draft): View
    {
        $this->authorize('manage-products');

        $data = $draft->data ?? [];
        $category = ! empty($data['category_slug'])
            ? Category::where('slug', $data['category_slug'])->first()
            : (($data['category_id'] ?? null) ? Category::find($data['category_id']) : null);

        return view('admin.csv-drafts.edit', [
            'draft' => $draft,
            'prefill' => $data,
            'prefillCategoryId' => $category?->id,
            'merchants' => Merchant::where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'categories' => Category::cascadeData(),
        ]);
    }

    /** Step 3 — publish the edited draft into the category section. */
    public function post(Request $request, ProductDraft $draft, ProductPublishService $publisher): RedirectResponse
    {
        $this->authorize('manage-products');

        if ($draft->status === 'posted') {
            return back()->withErrors(['draft' => 'This draft has already been posted.']);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'merchant_id' => 'required|exists:merchants,id',
            'category_id' => 'nullable|integer|exists:categories,id',
            'subcategory_id' => 'nullable|integer|exists:categories,id',
            'category' => 'nullable|string|max:255|required_without:category_id',
            'subcategory' => 'nullable|string|max:255',
            'affiliate_url' => 'required|url|max:2048',
            'current_price' => 'nullable|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'currency' => 'required|size:3',
            'description' => 'nullable|string|max:5000',
            'image' => 'nullable|url|max:2048',
            'availability' => 'required|in:in_stock,out_of_stock,preorder,unknown',
            'sku' => 'nullable|string|max:100',
            'is_trending' => 'boolean',
            'is_featured' => 'boolean',
            'is_top_selling' => 'boolean',
        ], [
            'category.required_without' => 'Choose a category or type a new one below.',
        ]);

        $draftData = $draft->data ?? [];

        try {
            $result = $publisher->publish($data, $draftData);
        } catch (\Throwable $e) {
            return back()->withErrors(['post' => 'Posting failed: '.$e->getMessage()]);
        }

        $draft->update(['status' => 'posted']);

        $offerCount = $result['product']->offers()->count();
        $status = $offerCount > 1
            ? 'Posted — "'.$result['product']->name.'" sold by '.$offerCount.' stores (Compare Stores updated).'
            : 'Product posted to "'.$result['categoryName'].'" and is live — '.$result['product']->name.' (id #'.$result['product']->id.')';

        return redirect()->route('admin.csv-drafts.index')->with('status', $status);
    }

    /** Remove a draft without posting. */
    public function destroy(ProductDraft $draft): RedirectResponse
    {
        $this->authorize('manage-products');
        $draft->delete();

        return back()->with('status', 'Draft removed.');
    }
}
