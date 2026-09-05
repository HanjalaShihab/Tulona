<?php

namespace App\Support;

use App\Models\Merchant;

class StartechAffiliate
{
    public const DEFAULT_CODE = '6a8fee867aad2';
    public const PARAM = 'tracking';

    public static function trackingCode(?string $override = null): string
    {
        if ($override !== null && trim($override) !== '') {
            return trim($override);
        }
        return (string) (config('services.startech.tracking_code') ?: self::DEFAULT_CODE);
    }

    /**
     * Is the merchant Star Tech (covers star-tech, startech, startech.com.bd, star-tech.com)?
     */
    public static function isStartechMerchant(?Merchant $merchant, ?string $url = null): bool
    {
        if ($merchant) {
            $slug = strtolower($merchant->slug ?? '');
            $name = strtolower($merchant->name ?? '');
            $website = strtolower($merchant->website_url ?? '');
            if (str_contains($slug, 'startech') || str_contains($slug, 'star-tech')) {
                return true;
            }
            if (str_contains($name, 'star tech') || str_contains($name, 'startech')) {
                return true;
            }
            if (str_contains($website, 'startech') || str_contains($website, 'star-tech')) {
                return true;
            }
        }
        if ($url) {
            $host = strtolower((string) parse_url($url, PHP_URL_HOST));
            if (str_contains($host, 'startech') || str_contains($host, 'star-tech')) {
                return true;
            }
        }
        return false;
    }

    public static function isStartechMerchantId(?int $merchantId, ?string $url = null): bool
    {
        if ($merchantId) {
            $merchant = Merchant::find($merchantId);
            if (self::isStartechMerchant($merchant, $url)) {
                return true;
            }
        }
        return self::isStartechMerchant(null, $url);
    }

    /**
     * Append ?tracking=CODE to a StarTech product URL.
     * Handles existing query string, avoids duplicate tracking param, preserves fragment.
     */
    public static function buildAffiliateUrl(string $url, ?string $code = null): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        $code = self::trackingCode($code);
        if ($code === '') {
            return $url;
        }
        // Already has tracking param -> replace or keep
        if (preg_match('/([?&])' . preg_quote(self::PARAM, '/') . '=/i', $url)) {
            // Replace existing tracking value with our code
            return preg_replace('/([?&])' . preg_quote(self::PARAM, '/') . '=[^&]*/i', '$1' . self::PARAM . '=' . $code, $url);
        }
        $parts = parse_url($url);
        $hasQuery = isset($parts['query']) && $parts['query'] !== '';
        $separator = $hasQuery ? '&' : '?';
        // Preserve fragment (#...)
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';
        // Remove fragment from url before appending
        $base = $fragment !== '' ? substr($url, 0, -strlen($fragment)) : $url;
        // Clean trailing ?/&
        $base = rtrim($base, '?&');
        return $base . $separator . self::PARAM . '=' . urlencode($code) . $fragment;
    }

    /**
     * If merchant is StarTech, ensure affiliate URL has tracking code.
     * Returns original URL untouched for other merchants.
     */
    public static function maybeAppend(string $url, ?int $merchantId = null, ?Merchant $merchant = null, ?string $urlForDetection = null, ?string $code = null): string
    {
        $isStartech = false;
        if ($merchant) {
            $isStartech = self::isStartechMerchant($merchant, $urlForDetection ?? $url);
        } elseif ($merchantId) {
            $isStartech = self::isStartechMerchantId($merchantId, $urlForDetection ?? $url);
        } else {
            $isStartech = self::isStartechMerchant(null, $urlForDetection ?? $url);
        }
        if (! $isStartech) {
            return $url;
        }
        // If url is empty, try to use external_url as base? Caller handles.
        return self::buildAffiliateUrl($url, $code);
    }
}
