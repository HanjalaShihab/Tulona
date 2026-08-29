<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessImportBatch;
use App\Jobs\ProcessImportItems;
use App\Models\Category;
use App\Models\ImportBatch;
use App\Models\ImportItem;
use App\Models\Merchant;
use App\Models\ProductDraft;
use App\Services\ImportService;
use App\Services\Scraping\UrlScrapeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

/** Import flow (§67): upload → validate → preview → confirm → background job → results. */
class ImportController extends Controller
{
    public function index(): View
    {
        return view('admin.imports.index', [
            'batches' => ImportBatch::latest()->paginate(15),
            'merchants' => Merchant::orderBy('name')->get(['id', 'name', 'slug', 'connector_type', 'product_import_method']),
            'categories' => Category::whereNull('parent_id')->where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'slug']),
            'draftCount' => ProductDraft::where('status', '!=', 'posted')->count(),
        ]);
    }

    public function upload(Request $request, ImportService $service): RedirectResponse
    {
        $this->authorize('run-imports');
        $request->validate(['file' => 'required|file|mimes:csv,txt,json|max:20480']);

        $file = $request->file('file');
        $filename = now()->format('Ymd_His').'_'.$file->getClientOriginalName();
        Storage::putFileAs('private', $file, $filename);

        $batch = ImportBatch::create([
            'filename' => $filename,
            'type' => $file->getClientOriginalExtension() === 'json' ? 'json' : 'csv',
            'status' => 'validating',
            'created_by' => auth()->id(),
        ]);

        // Dry-run validation → errors visible in preview before import runs
        $service->validate($batch, Storage::path("private/{$filename}"));

        return redirect()->route('admin.imports.show', $batch);
    }

    public function confirm(Request $request, ImportBatch $batch): RedirectResponse
    {
        $this->authorize('run-imports');

        if ($batch->source_type === 'url') {
            abort_unless($batch->status === 'preview', 422, 'This batch is not ready to process.');
            ProcessImportItems::dispatch($batch);

            return redirect()->route('admin.imports.show', $batch)->with('status', 'Import of all staged items queued.');
        }

        abort_unless($batch->status === 'validated', 422, 'This batch is not ready to process.');

        ProcessImportBatch::dispatch($batch);

        return redirect()->route('admin.imports.show', $batch)->with('status', 'Import queued for processing.');
    }

    /** §16 live URL import — runs synchronously so the preview is ready immediately. */
    public function scrape(Request $request, UrlScrapeService $service): RedirectResponse
    {
        $this->authorize('run-imports');

        $request->validate([
            'merchant_id' => 'required|exists:merchants,id',
            'source_url' => 'required|url|max:2048',
            'category' => 'nullable|string|max:255',
        ]);

        $category = $request->filled('category')
            ? $this->resolveOrCreateCategory($request->input('category'))
            : null;

        $batch = ImportBatch::create([
            'filename' => 'url',
            'type' => 'url',
            'source_type' => 'url',
            'source_url' => $request->source_url,
            'category_slug' => $category?->slug,
            'merchant_id' => $request->merchant_id,
            'status' => 'queued',
            'total_rows' => 0,
            'created_by' => auth()->id(),
        ]);

        try {
            // Fetches, parses, normalizes and matches synchronously → staged preview.
            $service->scrape($batch);

            return redirect()->route('admin.imports.show', $batch)->with('status', 'Preview ready for review.');
        } catch (\Throwable $e) {
            $batch->update(['status' => 'failed', 'completed_at' => now()]);

            return back()->withErrors(['scrape' => $e->getMessage()]);
        }
    }

    /** Resolve a category by slug/name, merging with an existing one or creating it. */
    protected function resolveOrCreateCategory(string $input): ?Category
    {
        $name = trim($input);
        if ($name === '') {
            return null;
        }

        $slug = Str::slug($name);

        $category = Category::where('slug', $slug)
            ->orWhereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        return $category ?? Category::create([
            'name' => $name,
            'slug' => $slug,
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }

    /** §16 import only selected staged items. */
    public function selected(Request $request, ImportBatch $batch): RedirectResponse
    {
        $this->authorize('run-imports');

        abort_unless($batch->source_type === 'url' && $batch->status === 'preview', 422, 'This batch is not ready.');

        $items = $batch->items()->whereIn('id', $request->input('items', []))
            ->whereNotIn('status', ['error', 'skipped'])
            ->pluck('id');

        abort_if($items->isEmpty(), 422, 'Select at least one importable item.');

        ProcessImportItems::dispatch($batch, $items->all());

        return redirect()->route('admin.imports.show', $batch)->with('status', 'Import of '.$items->count().' selected items queued.');
    }

    /** §16 remove a single staged product from the preview list before import. */
    public function destroyItem(ImportBatch $batch, ImportItem $item): RedirectResponse
    {
        $this->authorize('run-imports');

        abort_unless($batch->id === $item->import_batch_id && $batch->status === 'preview', 422, 'Only staged items from a preview can be removed.');

        $item->delete();
        $this->refreshCounts($batch);

        return back()->with('status', 'Removed from list.');
    }

    /** §16 remove the selected staged products from the preview list before import. */
    public function removeSelected(Request $request, ImportBatch $batch): RedirectResponse
    {
        $this->authorize('run-imports');

        abort_unless($batch->status === 'preview' && $batch->source_type === 'url', 422, 'Only URL scrape previews support removing items.');

        $ids = $request->input('items', []);
        abort_if(empty($ids), 422, 'Select at least one item to remove.');

        $deleted = $batch->items()->whereIn('id', $ids)
            ->whereNotIn('status', ['error', 'skipped'])
            ->delete();

        abort_if($deleted === 0, 422, 'No removable items selected.');
        $this->refreshCounts($batch);

        return back()->with('status', "Removed {$deleted} product(s) from the list.");
    }

    protected function refreshCounts(ImportBatch $batch): void
    {
        $items = $batch->items();
        $batch->updateQuietly([
            'total_rows' => $items->count(),
            'created_count' => (clone $items)->where('status', 'new')->count(),
            'updated_count' => (clone $items)->where('status', 'matched')->count(),
            'skipped_count' => (clone $items)->where('status', 'duplicate')->count(),
            'failed_count' => (clone $items)->where('status', 'error')->count(),
        ]);
    }

    /** §16 cancel a previewed URL import. */
    public function cancel(ImportBatch $batch): RedirectResponse
    {
        $this->authorize('run-imports');

        abort_unless($batch->status === 'preview', 422, 'Only previewed URL imports can be cancelled.');

        $batch->update(['status' => 'cancelled']);
        $batch->items()->update(['status' => 'skipped', 'error' => 'Cancelled by admin.']);

        return redirect()->route('admin.imports.show', $batch)->with('status', 'Import cancelled.');
    }

    /** §15 retry a failed batch from the beginning. */
    public function retry(Request $request, ImportBatch $batch): RedirectResponse
    {
        $this->authorize('run-imports');

        abort_unless($batch->status === 'failed', 422, 'Only failed imports can be retried.');

        $batch->update(['status' => 'queued']);

        if ($batch->source_type === 'url') {
            $items = $batch->items()
                ->whereIn('status', ['new', 'matched'])
                ->whereNull('processed_at')
                ->pluck('id');

            ProcessImportItems::dispatch($batch, $items->isEmpty() ? null : $items->all());
        } else {
            ProcessImportBatch::dispatch($batch);
        }

        return redirect()->route('admin.imports.show', $batch)->with('status', 'Import retry queued.');
    }

    /** §16 resume a completed URL import for the items that failed to import. */
    public function retryFailedItems(ImportBatch $batch): RedirectResponse
    {
        $this->authorize('run-imports');

        abort_unless($batch->source_type === 'url' && $batch->status === 'completed', 422, 'Retry is only available for completed URL imports.');

        $items = $batch->items()
            ->whereIn('status', ['failed', 'skipped'])
            ->whereNotNull('error')
            ->get();

        abort_if($items->isEmpty(), 422, 'No failed items to retry.');

        $ids = $items->map(function ($item) {
            $item->update([
                'status' => $item->product_id ? 'matched' : 'new',
                'error' => null,
                'processed_at' => null,
            ]);

            return $item->id;
        })->all();

        ProcessImportItems::dispatch($batch, $ids);

        return back()->with('status', 'Retrying '.count($ids).' failed item(s).');
    }

    public function show(Request $request, ImportBatch $batch): View
    {
        $isUrl = $batch->source_type === 'url';

        $batch->load($isUrl ? ['items.product', 'merchant'] : 'errors');

        $items = $isUrl
            ? $batch->items()->orderBy('id')->get()
            : null;

        return view('admin.imports.show', [
            'batch' => $batch,
            'items' => $items,
            'preview' => $batch->errors()->where('severity', 'error')->count() === 0 && ! in_array($batch->status, ['processing', 'completed']),
            'status' => session('status'),
        ]);
    }
}
