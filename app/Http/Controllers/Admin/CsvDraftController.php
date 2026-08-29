<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Merchant;
use App\Models\ProductDraft;
use App\Services\CsvDraftService;
use App\Services\ProductPublishService;
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

    /** Step 2 — edit a single draft and post it. */
    public function edit(ProductDraft $draft): View
    {
        $this->authorize('manage-products');

        $data = $draft->data ?? [];
        $category = ! empty($data['category_slug'])
            ? Category::where('slug', $data['category_slug'])->first()
            : null;

        return view('admin.csv-drafts.edit', [
            'draft' => $draft,
            'prefill' => $data,
            'prefillCategoryId' => $category?->id,
            'merchants' => Merchant::where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'categories' => Category::whereNull('parent_id')->where('is_active', true)
                ->orderBy('sort_order')->with('children')->get(),
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
            'category.required_without' => 'Choose a landing-page category or type a new one below.',
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
