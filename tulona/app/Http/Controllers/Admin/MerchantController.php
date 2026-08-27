<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AffiliateNetwork;
use App\Models\AuditLog;
use App\Models\Merchant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Generic merchant system (§4) — add merchants without touching core code. */
class MerchantController extends Controller
{
    public function index(): View
    {
        return view('admin.merchants.index', [
            'merchants' => Merchant::withCount(['offers', 'offers as product_count' => fn ($q) => $q->selectRaw('COUNT(DISTINCT product_id)')])
                ->orderBy('name')->paginate(30),
            'networks' => AffiliateNetwork::all(),
        ]);
    }

    public function create(): View
    {
        return view('admin.merchants.form', ['merchant' => new Merchant, 'networks' => AffiliateNetwork::all()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Merchant::create($this->validated($request));

        return redirect()->route('admin.merchants.index')->with('status', 'Merchant created.');
    }

    public function edit(Merchant $merchant): View
    {
        return view('admin.merchants.form', ['merchant' => $merchant, 'networks' => AffiliateNetwork::all()]);
    }

    public function update(Request $request, Merchant $merchant): RedirectResponse
    {
        $merchant->update($this->validated($request));
        AuditLog::record('merchant.changed', $merchant);

        return back()->with('status', 'Merchant updated.');
    }

    /** Disabling a merchant hides its offers but keeps all data. */
    public function destroy(Merchant $merchant): RedirectResponse
    {
        $merchant->update(['status' => 'inactive']);
        $merchant->offers()->update(['status' => 'inactive']);
        AuditLog::record('merchant.disabled', $merchant);

        return redirect()->route('admin.merchants.index')->with('status', 'Merchant disabled.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:merchants,slug,'.($request->route('merchant')?->id ?? ''),
            'affiliate_network_id' => 'nullable|exists:affiliate_networks,id',
            'logo_path' => 'nullable|url|max:2048',
            'description' => 'nullable|string|max:5000',
            'website_url' => 'nullable|url|max:2048',
            'country' => 'required|string|size:2',
            'region' => 'nullable|string|max:100',
            'currencies' => 'nullable|array',
            'base_affiliate_url' => 'nullable|url|max:2048',
            'tracking_template' => 'nullable|string|max:1000',
            'commission_note' => 'nullable|string|max:1000',
            'status' => 'required|in:active,inactive',
            'terms_notes' => 'nullable|string|max:5000',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
        ]);
        $data['currencies'] = array_map('strtoupper', $data['currencies'] ?? ['BDT']);

        // Feed/API config comes from env-backed JSON fields; secrets stay in .env (§79)
        if ($request->filled('feed_config')) {
            $decoded = json_decode((string) $request->input('feed_config'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                unset($data['feed_config']);
                abort(422, 'Feed config must be valid JSON.');
            } else {
                $data['feed_config'] = $decoded;
            }
        }

        return $data;
    }
}
