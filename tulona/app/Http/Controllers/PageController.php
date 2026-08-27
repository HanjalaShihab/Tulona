<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/** Static trust/transparency pages (§43). Content is plain Blade — no DB roundtrip. */
class PageController extends Controller
{
    public const PAGES = [
        'about' => ['About Tulona', 'Learn who we are and how Tulona helps you shop smarter in Bangladesh.'],
        'contact' => ['Contact Us', 'Get in touch with the Tulona team.'],
        'affiliate-disclosure' => ['Affiliate Disclosure', 'How affiliate links work on Tulona and how we may earn commissions.'],
        'privacy-policy' => ['Privacy Policy', 'What data Tulona collects (very little) and why.'],
        'terms-of-use' => ['Terms of Use', 'The terms that govern your use of Tulona.'],
        'cookie-policy' => ['Cookie Policy', 'How cookies are used on Tulona.'],
    ];

    public function show(string $slug): View
    {
        abort_unless(isset(self::PAGES[$slug]), 404);

        [$title, $description] = self::PAGES[$slug];

        return view("pages.{$slug}", [
            'seo' => ['title' => "{$title} — Tulona", 'description' => $description],
        ]);
    }
}
