<?php

namespace App\Connectors\Generator;

use App\Contracts\Merchant\AffiliateLinkGenerator;
use App\Models\AffiliateLinkGeneration;
use App\Models\AffiliateOffer;
use App\Models\Merchant;

/**
 * Manual affiliate link workflow (§21): an admin pastes the URL produced by the
 * merchant's official generator; we validate and record it onto the affiliate
 * offer with a generation-history row. We never construct URLs by guessing.
 */
class ManualAffiliateLinkGenerator implements AffiliateLinkGenerator
{
    public function generate(AffiliateOffer $affiliateOffer, ?string $manualUrl = null): AffiliateOffer
    {
        $error = null;
        $status = 'success';

        if ($manualUrl === null || trim($manualUrl) === '') {
            $status = 'failed';
            $error = 'No URL provided. Open the merchant generator and paste the affiliate URL (§21).';
        } elseif (! filter_var($manualUrl, FILTER_VALIDATE_URL)) {
            $status = 'invalid';
            $error = 'Provided value is not a valid URL.';
        }

        $generation = AffiliateLinkGeneration::create([
            'affiliate_offer_id' => $affiliateOffer->id,
            'product_id' => $affiliateOffer->product_id,
            'merchant_id' => $affiliateOffer->merchant_id,
            'method' => 'manual',
            'status' => $status,
            'input_url' => $manualUrl,
            'generated_url' => $status === 'success' ? $manualUrl : null,
            'error' => $error,
            'processed_at' => now(),
        ]);

        if ($status === 'success') {
            $affiliateOffer->update([
                'affiliate_url' => $manualUrl,
                'status' => 'manual',
                'generation_method' => 'manual',
                'generated_at' => now(),
                'last_error' => null,
            ]);
        }

        $affiliateOffer->setRelation('latestGeneration', $generation);

        return $affiliateOffer;
    }

    public function supports(Merchant $merchant): bool
    {
        return true; // manual entry works for every merchant (§21)
    }
}
