<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Comparison;
use App\Models\LandingPage;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Landing page CMS (§38, §47). Dynamic content pages built from ordered
 * JSON "sections" that reference canonical products/comparisons (§69).
 */
class LandingPageController extends Controller
{
    /** Ordered list of supported section types (kept minimal, §65). */
    public const SECTION_TYPES = ['hero', 'text', 'products', 'comparisons', 'faq', 'cta'];

    public function index(): View
    {
        return view('admin.landing-pages.index', [
            'pages' => LandingPage::withCount('products', 'comparisons')->orderBy('created_at', 'desc')->paginate(30),
        ]);
    }

    public function create(): View
    {
        return view('admin.landing-pages.form', [
            'page' => new LandingPage(['status' => 'draft']),
            'products' => $this->catalog(),
            'comparisons' => Comparison::orderBy('title')->limit(500)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['sections'] = $this->sections($request);

        $page = LandingPage::create($data);
        $this->syncRelated($request, $page);
        AuditLog::record('landing_page.created', $page);

        return redirect()->route('admin.landing-pages.edit', $page)->with('status', 'Landing page saved.');
    }

    public function edit(LandingPage $landingPage): View
    {
        return view('admin.landing-pages.form', [
            'page' => $landingPage,
            'products' => $this->catalog(),
            'comparisons' => Comparison::orderBy('title')->limit(500)->get(),
        ]);
    }

    public function update(Request $request, LandingPage $landingPage): RedirectResponse
    {
        $wasDraft = $landingPage->status === 'draft';
        $data = $this->validated($request);
        $data['sections'] = $this->sections($request);

        $landingPage->update($data);
        $this->syncRelated($request, $landingPage);
        AuditLog::record($wasDraft && $landingPage->status === 'published' ? 'landing_page.published' : 'landing_page.edited', $landingPage);

        return back()->with('status', 'Landing page saved.');
    }

    public function destroy(LandingPage $landingPage): RedirectResponse
    {
        AuditLog::record('landing_page.deleted', $landingPage);
        $landingPage->delete();

        return redirect()->route('admin.landing-pages.index')->with('status', 'Landing page deleted.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/|unique:landing_pages,slug,'.($request->route('landingPage')?->id ?? ''),
            'excerpt' => 'nullable|string|max:1000',
            'status' => 'required|in:draft,published',
            'published_at' => 'nullable|date',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'canonical_url' => 'nullable|url|max:2048',
        ]);

        if (($data['status'] ?? '') === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        return $data;
    }

    /** Rebuild the ordered sections array from the submitted blocks. */
    protected function sections(Request $request): array
    {
        $blocks = $request->input('sections', []);

        return collect(is_array($blocks) ? $blocks : [])
            ->map(fn ($block) => $this->normalizeSection($block))
            ->filter()
            ->values()
            ->all();
    }

    protected function normalizeSection(array $block): ?array
    {
        $type = $block['type'] ?? '';
        if (! in_array($type, self::SECTION_TYPES, true)) {
            return null;
        }

        $section = ['type' => $type];

        switch ($type) {
            case 'hero':
                $section['heading'] = trim((string) ($block['heading'] ?? ''));
                $section['subheading'] = trim((string) ($block['subheading'] ?? ''));
                $section['cta_text'] = trim((string) ($block['cta_text'] ?? ''));
                $section['cta_url'] = trim((string) ($block['cta_url'] ?? ''));
                $section['image_url'] = trim((string) ($block['image_url'] ?? ''));
                break;
            case 'text':
                $section['heading'] = trim((string) ($block['heading'] ?? ''));
                $section['body'] = trim((string) ($block['body'] ?? ''));
                break;
            case 'products':
                $section['title'] = trim((string) ($block['title'] ?? ''));
                $section['description'] = trim((string) ($block['description'] ?? ''));
                break;
            case 'comparisons':
                $section['title'] = trim((string) ($block['title'] ?? ''));
                $section['description'] = trim((string) ($block['description'] ?? ''));
                break;
            case 'faq':
                $section['heading'] = trim((string) ($block['heading'] ?? ''));
                $faq = json_decode((string) ($block['faq_json'] ?? '[]'), true);
                $section['items'] = is_array($faq)
                    ? collect($faq)->filter(fn ($i) => is_array($i) && trim((string) ($i['question'] ?? '')) !== '')
                        ->values()->all()
                    : [];
                break;
            case 'cta':
                $section['heading'] = trim((string) ($block['heading'] ?? ''));
                $section['text'] = trim((string) ($block['text'] ?? ''));
                $section['button_text'] = trim((string) ($block['button_text'] ?? ''));
                $section['button_url'] = trim((string) ($block['button_url'] ?? ''));
                break;
        }

        // A block with no content at all is considered empty and dropped.
        $meaningful = array_filter($section, fn ($v) => $v !== null && $v !== '' && $v !== []);
        if (count($meaningful) <= 1) {
            return null;
        }

        return $section;
    }

    protected function syncRelated(Request $request, LandingPage $page): void
    {
        $products = collect($request->input('products', []))->filter()->values()->all();
        $page->products()->sync(array_fill_keys($products, ['sort_order' => 0]));

        $comparisons = collect($request->input('comparisons', []))->filter()->values()->all();
        $page->comparisons()->sync(array_fill_keys($comparisons, ['sort_order' => 0]));
    }

    protected function catalog()
    {
        return Product::where('status', 'published')->with('brand:id,name')->limit(500)->get();
    }
}
