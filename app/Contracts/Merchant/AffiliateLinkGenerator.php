<?php

namespace App\Contracts\Merchant;

use App\Models\AffiliateOffer;
use App\Models\Merchant;

/**
 * §20 affiliate link generation: manual (admin pastes URL → recorded onto the
 * affiliate offer) and automated (merchant generator workflow). The automated
 * flow must operate on the official generator when permitted — never guess URLs.
 */
interface AffiliateLinkGenerator
{
    /**
     * Generate (or record a manual) affiliate URL for the offer.
     *
     * @param  string|null  $manualUrl  admin-pasted URL for the manual workflow (§21)
     */
    public function generate(AffiliateOffer $affiliateOffer, ?string $manualUrl = null): AffiliateOffer;

    public function supports(Merchant $merchant): bool;
}
