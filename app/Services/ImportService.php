<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\ImportBatch;
use App\Models\ImportError;
use App\Models\Merchant;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * CSV product+offer import pipeline (§25, §67):
 * upload → validate (dry-run) → show errors/warnings → confirm → background job.
 */
class ImportService
{
    public const REQUIRED = ['name', 'category_slug', 'merchant_slug', 'price', 'currency', 'affiliate_url'];

    /** Dry-run: parse + validate, persist errors on the batch. Returns true when processable. */
    public function validate(ImportBatch $batch, string $absolutePath): bool
    {
        $rows = $this->readCsv($absolutePath);
        $headers = array_shift($rows) ?? [];

        if (! in_array('name', $headers)) {
            ImportError::create(['import_batch_id' => $batch->id, 'message' => 'CSV must include a header row containing at least: '.implode(', ', self::REQUIRED).'.', 'severity' => 'error']);
            $batch->update(['status' => 'failed', 'total_rows' => 0]);

            return false;
        }

        $errors = [];
        foreach ($rows as $i => $row) {
            $rowNumber = $i + 2;
            $data = array_combine($headers, array_pad(array_slice($row, 0, count($headers)), count($headers), null));
            $data = array_map(fn ($v) => $v === null ? null : trim((string) $v), $data);
            $errors = [...$errors, ...$this->validateRow($data, $rowNumber)];
        }

        foreach ($errors as $e) {
            ImportError::create($e + ['import_batch_id' => $batch->id]);
        }

        $blocking = collect($errors)->where('severity', 'error')->count();
        $batch->update(['status' => $blocking > 0 ? 'failed' : 'validated', 'total_rows' => count($rows)]);

        return $blocking === 0;
    }

    protected function validateRow(array $data, int $rowNumber): array
    {
        $errors = [];

        foreach (self::REQUIRED as $field) {
            if (($data[$field] ?? '') === '') {
                $errors[] = ['row_number' => $rowNumber, 'field' => $field, 'message' => "Missing required field '{$field}'.", 'severity' => 'error'];
            }
        }

        if (isset($data['price']) && $data['price'] !== '' && ! is_numeric(str_replace([',', '৳'], '', $data['price']))) {
            $errors[] = ['row_number' => $rowNumber, 'field' => 'price', 'message' => 'Invalid price value.', 'severity' => 'error'];
        }

        if (! empty($data['affiliate_url']) && ! filter_var($data['affiliate_url'], FILTER_VALIDATE_URL)) {
            $errors[] = ['row_number' => $rowNumber, 'field' => 'affiliate_url', 'message' => 'Invalid URL.', 'severity' => 'error'];
        }

        if (! empty($data['currency']) && ! in_array(strtoupper($data['currency']), ['BDT', 'USD', 'INR', 'EUR', 'GBP'])) {
            $errors[] = ['row_number' => $rowNumber, 'field' => 'currency', 'message' => 'Unsupported currency.', 'severity' => 'error'];
        }

        if (! empty($data['category_slug']) && ! Category::where('slug', $data['category_slug'])->exists()) {
            $errors[] = ['row_number' => $rowNumber, 'field' => 'category_slug', 'message' => "Unknown category '{$data['category_slug']}'.", 'severity' => 'error'];
        }

        if (! empty($data['merchant_slug']) && ! Merchant::where('slug', $data['merchant_slug'])->exists()) {
            $errors[] = ['row_number' => $rowNumber, 'field' => 'merchant_slug', 'message' => "Unknown merchant '{$data['merchant_slug']}'.", 'severity' => 'error'];
        }

        if (! empty($data['brand_slug']) && ! Brand::where('slug', $data['brand_slug'])->exists()) {
            $errors[] = ['row_number' => $rowNumber, 'field' => 'brand_slug', 'message' => "Unknown brand '{$data['brand_slug']}' — row will be skipped.", 'severity' => 'warning'];
        }

        // Duplicate products are matched to the existing record → updated with a new offer (§24)
        if (! empty($data['name'])) {
            $existing = Product::where('slug', Str::slug($data['name']))->first();
            if ($existing && empty($data['merchant_slug'])) {
                $errors[] = ['row_number' => $rowNumber, 'field' => 'name', 'message' => 'Duplicate product without merchant — cannot attach an offer.', 'severity' => 'warning'];
            }
        }

        return $errors;
    }

    /** Called from ProcessImportBatch job. */
    public function process(ImportBatch $batch): void
    {
        $batch->update(['status' => 'processing']);
        $c = ['imported_count' => 0, 'created_count' => 0, 'updated_count' => 0, 'skipped_count' => 0, 'failed_count' => 0];

        try {
            $handle = fopen(storage_path("app/private/{$batch->filename}"), 'r');
            if ($handle === false) {
                throw new \RuntimeException('Cannot reopen import file.');
            }
            $headers = fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== false) {
                try {
                    $result = $this->importRow($row, $headers);
                    if ($result !== null) {
                        $c[$result]++;
                        $c['imported_count']++;
                    } else {
                        $c['skipped_count']++;
                    }
                } catch (\Throwable $e) {
                    $c['failed_count']++;
                    Log::warning('Import row failed', ['batch' => $batch->id, 'error' => $e->getMessage()]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('Import batch failed hard', ['batch' => $batch->id, 'error' => $e->getMessage()]);
        } finally {
            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }
        }

        $batch->update([...$c, 'status' => 'completed', 'completed_at' => now()]);
    }

    /** @return string|null created_count|updated_count|skipped_count|null */
    protected function importRow(array $row, array $headers): ?string
    {
        $data = array_map(
            fn ($v) => $v === null ? null : trim((string) $v),
            array_combine($headers, array_pad(array_slice($row, 0, count($headers)), count($headers), null))
        );

        if (empty($data['name'])) {
            return 'skipped_count';
        }

        $category = Category::where('slug', $data['category_slug'] ?? '')->first();
        $brand = Brand::where('slug', $data['brand_slug'] ?? '')->first();
        $merchant = Merchant::where('slug', $data['merchant_slug'] ?? '')->first();

        if (! $category || ! $merchant) {
            return 'skipped_count';
        }

        $slug = $this->generateUniqueSlug(Str::slug($data['name']), $category->id ?? null);

        $product = Product::withTrashed()->firstOrNew(['slug' => $slug]);

        $wasCreated = ! $product->exists;

        if ($wasCreated) {
            // New product: seed from scraped/raw data and default to published.
            $product->category_id = $category->id;
            $product->brand_id = $brand?->id;
            $product->name = $data['name'];
            $product->short_description = $data['description'] ?? null;
            $product->gtin = $data['gtin'] ?? null;
            $product->status = 'published';
        } else {
            // Existing product: preserve admin-curated content (name, description,
            // gtin, publication status). Only refresh relational mappings.
            $product->category_id = $category->id;
            $product->brand_id = $brand?->id;
        }

        $product->save();

        $price = is_numeric($p = str_replace([',', '৳'], '', $data['price'] ?? '')) ? (float) $p : null;

        $offer = Offer::updateOrCreate(
            ['product_id' => $product->id, 'merchant_id' => $merchant->id],
            [
                'affiliate_url' => $data['affiliate_url'],
                'current_price' => $price,
                'original_price' => isset($data['original_price']) && is_numeric($op = str_replace([',', '৳'], '', $data['original_price'])) ? (float) $op : null,
                'currency' => strtoupper($data['currency'] ?? 'BDT'),
                'availability' => in_array($data['availability'] ?? '', ['in_stock', 'out_of_stock', 'preorder', 'unknown'], true) ? $data['availability'] : 'in_stock',
                'source' => 'import',
                'status' => 'active',
                'last_synced_at' => now(),
            ]
        );

        app(PriceTrackingService::class)->recordPrice($offer, $offer->current_price);

return $wasCreated ? 'created_count' : 'updated_count';
    }

    protected function generateUniqueSlug(string $slug, ?int $parentId): string
    {
        $uniqueSlug = $slug;
        $counter = 1;
        while (Product::where('slug', $uniqueSlug)->where('category_id', $parentId)->exists()) {
            $uniqueSlug = $slug.'-'.(++$counter);
        }

        return $uniqueSlug;
    }

// __REST2__
}
