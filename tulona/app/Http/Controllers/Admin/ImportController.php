<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessImportBatch;
use App\Jobs\ProcessImportItems;
use App\Models\Category;
use App\Models\ImportBatch;
use App\Models\Merchant;
use App\Services\ImportService;
use App\Services\Scraping\UrlScrapeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/** Import flow (§67): upload → validate → preview → confirm → background job → results. */
class ImportController extends Controller
{
    public function index(): View
    {
        return view('admin.imports.index', [
            'batches' => ImportBatch::latest()->paginate(15),
            'merchants' => Merchant::orderBy('name')->get(['id', 'name', 'slug', 'connector_type', 'product_import_method']),
            'categories' => Category::orderBy('name')->get(['id', 'name', 'slug']),
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
            'category_id' => 'nullable|exists:categories,id',
        ]);

        $category = $request->category_id
            ? Category::find($request->category_id)
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

    /** §16 cancel a previewed URL import. */
    public function cancel(ImportBatch $batch): RedirectResponse
    {
        $this->authorize('run-imports');

        abort_unless($batch->status === 'preview', 422, 'Only previewed URL imports can be cancelled.');

        $batch->update(['status' => 'cancelled']);
        $batch->items()->update(['status' => 'skipped', 'error' => 'Cancelled by admin.']);

        return redirect()->route('admin.imports.show', $batch)->with('status', 'Import cancelled.');
    }

    public function show(Request $request, ImportBatch $batch): View
    {
        $isUrl = $batch->source_type === 'url';

        $batch->load($isUrl ? ['items.product', 'merchant'] : 'errors');

        $items = $isUrl
            ? $batch->items()->orderByDesc('status')->paginate(perPage: 50, pageName: 'items')
            : null;

        return view('admin.imports.show', [
            'batch' => $batch,
            'items' => $items,
            'preview' => $batch->errors()->where('severity', 'error')->count() === 0 && ! in_array($batch->status, ['processing', 'completed']),
            'status' => session('status'),
        ]);
    }
}
