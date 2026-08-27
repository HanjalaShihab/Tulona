<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Merchant\AffiliateLinkGenerator;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessAffiliateGenerations;
use App\Models\AffiliateLinkGeneration;
use App\Models\AffiliateOffer;
use App\Models\AuditLog;
use App\Models\Merchant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Affiliate offers + manual link generation (§19–§22). */
class AffiliateController extends Controller
{
    public function index(Request $request): View
    {
        $query = AffiliateOffer::with(['product:id,name,slug', 'merchant:id,name,slug'])
            ->when($request->query('merchant_id'), fn ($q, $v) => $q->where('merchant_id', $v))
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->query('q'), function ($q, $v) {
                $q->whereHas('product', fn ($p) => $p->where('name', 'like', "%{$v}%"));
            });

        return view('admin.affiliate.index', [
            'offers' => $query->latest('updated_at')->paginate(20)->withQueryString(),
            'merchants' => Merchant::orderBy('name')->get(['id', 'name']),
            'statuses' => ['pending', 'manual', 'generated', 'failed', 'invalid', 'inactive'],
            'counts' => [
                'total' => AffiliateOffer::count(),
                'manual' => AffiliateOffer::where('status', 'manual')->count(),
                'pending' => AffiliateOffer::whereIn('status', ['pending', 'failed', 'invalid'])->count(),
                'with_url' => AffiliateOffer::whereNotNull('affiliate_url')->count(),
            ],
        ]);
    }

    public function show(AffiliateOffer $affiliateOffer): View
    {
        $affiliateOffer->load('product:id,name,slug,short_description', 'merchant', 'offer', 'generations.initiator:id,name');

        return view('admin.affiliate.show', [
            'affiliateOffer' => $affiliateOffer,
            'recentGenerations' => $affiliateOffer->generations->sortByDesc('id')->take(20),
        ]);
    }

    public function edit(AffiliateOffer $affiliateOffer): View
    {
        $affiliateOffer->load('product:id,name,slug', 'merchant');

        return view('admin.affiliate.edit', [
            'affiliateOffer' => $affiliateOffer,
            'recentGenerations' => $affiliateOffer->generations->sortByDesc('id')->take(20),
        ]);
    }

    /** §21 save a manually-pasted affiliate URL + commission details. */
    public function update(Request $request, AffiliateOffer $affiliateOffer): RedirectResponse
    {
        $data = $request->validate([
            'affiliate_url' => 'nullable|url|max:2048',
            'normal_product_url' => 'nullable|url|max:2048',
            'tracking_identifier' => 'nullable|string|max:255',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'commission_type' => 'nullable|in:percent,fixed',
            'commission_eligible' => 'nullable|boolean',
            'status' => 'required|in:pending,manual,generated,failed,invalid,inactive',
        ]);

        $generator = app(AffiliateLinkGenerator::class);

        DB::transaction(function () use ($request, $affiliateOffer, $generator) {
            $affiliateOffer->update([
                'normal_product_url' => $request->input('normal_product_url'),
                'tracking_identifier' => $request->input('tracking_identifier') ?: null,
                'commission_rate' => $request->input('commission_rate') !== '' && $request->input('commission_rate') !== null ? $request->input('commission_rate') : null,
                'commission_type' => $request->input('commission_type') ?: null,
                'commission_eligible' => $request->boolean('commission_eligible'),
                'status' => $request->input('status'),
            ]);

            // Only record a new generation when a URL was actually pasted (§21).
            if ($request->filled('affiliate_url')) {
                $generator->generate($affiliateOffer, $request->input('affiliate_url'));
            } elseif ($request->filled('normal_product_url')) {
                $affiliateOffer->update(['normal_product_url' => $request->input('normal_product_url')]);
            }
        });

        AuditLog::record('affiliate.updated', $affiliateOffer, ['status' => $request->input('status')]);

        return back()->with('status', 'Affiliate offer saved.');
    }

    /** §23 open the merchant's official generator (reads merchant.configuration), §21 paste back. */
    public function openGenerator(AffiliateOffer $affiliateOffer): RedirectResponse
    {
        $url = $affiliateOffer->merchant?->configuration['affiliate_generator_url'] ?? null;
        abort_unless($url, 422, 'No affiliate generator URL configured for this merchant.');

        return redirect()->away($url);
    }

    /** Warm cache / quick status flip. */
    public function markVerified(AffiliateOffer $affiliateOffer): RedirectResponse
    {
        $affiliateOffer->update([
            'last_verified_at' => now(),
            'status' => $affiliateOffer->affiliate_url ? 'generated' : $affiliateOffer->status,
        ]);

        return back()->with('status', 'Offer marked verified.');
    }

    public function generationHistory(AffiliateOffer $affiliateOffer): View
    {
        $affiliateOffer->load('product:id,name', 'merchant');

        $generations = AffiliateLinkGeneration::where('affiliate_offer_id', $affiliateOffer->id)
            ->with('initiator:id,name')
            ->latest('id')
            ->paginate(30);

        return view('admin.affiliate.generations', [
            'affiliateOffer' => $affiliateOffer,
            'generations' => $generations,
        ]);
    }

    /** §23 queue bulk affiliate-link generation for a merchant (or all). */
    public function bulkGenerate(Request $request): RedirectResponse
    {
        $merchantId = $request->input('merchant_id');
        $count = AffiliateOffer::whereIn('status', ['pending', 'failed', 'invalid'])
            ->when($merchantId, fn ($q) => $q->where('merchant_id', $merchantId))
            ->count();

        abort_if($count === 0, 422, 'No pending affiliate offers to generate for this selection.');

        ProcessAffiliateGenerations::dispatch($merchantId ?: null);

        AuditLog::record('affiliate.bulk_generation_queued', null, ['merchant_id' => $merchantId, 'count' => $count]);

        return back()->with('status', "Bulk generation queued for {$count} pending offer(s). Run the queue worker to process.");
    }
}
