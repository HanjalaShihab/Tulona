<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Str;

class CategoryDetector
{
    /**
     * Keyword map slug => keywords (lowercase). Order matters - first match wins for priority.
     * Covers existing 50 products + common future imports.
     */
    protected const MAP = [
        // Most specific first - security cameras before generic cameras/phones
        'security-cameras' => ['wifi camera','cctv','security camera','surveillance','meari s1','meari','portable wifi','s1 portable'],
        // Programming & Freelancing (must be before Books)
        'programming-and-freelancing' => [
            'প্রোগ্রামিং','পাইথন','জাভাস্ক্রিপ্ট','javascript','html','css','অ্যালগরিদম','algorithm','ফ্রিল্যান্সিং','freelancing','সাইবার','cyber','হাবলু','ফুলস্ট্যাক','r প্রোগ্রামিং','seo','facebook marketing','industrial automation',
            'programming er','programming for','habuder','python diye',
        ],
        // Keyboards & Mice - must be before Gaming Consoles to avoid "gaming keyboard" -> gaming consoles
        'keyboards-mice' => ['keyboard','keyboards','mouse','mice','gaming mouse','wireless mouse','gaming keyboard','mechanical keyboard','wireless keyboard','mechanical gaming keyboard','wireless gaming keyboard'],
        // Headphones & Earbuds
        'headphones-earbuds' => [
            'earphone','earbud','headphone','headset','soundcore','anker motorola','motorola','bullets','tws','bluetooth','fantech','capsule','in ear','a4 tech','mi in ear','oneplus bullets','oneplus nord','gaming headset',
        ],
        // Presentation Supplies for presenter/mount/webcam conference
        'presentation-supplies' => ['presenter','wireless presenter','presentation supplies','presentation'],
        'camera-accessories' => ['tv mount','wall mount','video bar','video conference','conference cam','meetup','webcam','mount for video'],
        // Beauty & Personal Care (before Books to catch The Ordinary)
        'beauty-personal-care' => ['skincare','serum','niacinamide','the ordinary','beauty','ordinary','lipstick','lip','makeup','matte'],
        // Bags & Luggage (before Books to catch Antler)
        'bags-luggage' => ['antler','jet cabin','luggage','backpack','cabin bag'],
        // TVs
        'tvs' => ['tv','television','led tv','smart tv','qled','oled tv'],
        // Dog Supplies / Pet
        'dog-supplies' => ['dog food','dog','puppy','pet food','pet supplies'],
        'yoga' => ['yoga','yoga mat','yoga mats'],
        // Books
        'books' => [
            'হুমায়ূন','আহমেদ','রবীন্দ্র','হাদীস','জোছনা','জননীর','ম্যাসেজ','দেয়াল','শূন্য','শেষ','student hacks','think and grow','vocabulary','spoken english','english therapy','english grammar','vocab therapy','তোমায় হৃদ',
            'book','novel','story','author',
        ],
        // Smartphones
        'smartphones' => ['smartphone','iphone','samsung galaxy','redmi','xiaomi','oppo','vivo','pixel','realme','mobile phone'],
        // Laptops (include galaxy book)
        'laptops' => ['laptop','macbook','thinkpad','asus rog','notebook','gaming laptop','galaxy book','samsung galaxy book'],
        // Tablets
        'tablets' => ['tablet','ipad'],
        // Cameras
        'cameras' => ['camera','dslr','mirrorless','action camera','webcam'],
        // Smartwatches
        'smartwatches' => ['smartwatch','smart watch','fitbit','mi band','smartwatch band'],
        // Power Banks
        'power-banks' => ['power bank','powerbank','anker prime'],
        // Chargers & Cables
        'chargers-cables' => ['charger','cable','adapter'],
        // Speakers
        'speakers' => ['speaker','jbl','bluetooth speaker'],
    ];

    protected $categoryCache = null;

    public function detect(string $name, ?string $description = null): ?Category
    {
        $text = mb_strtolower(trim($name . ' ' . ($description ?? '')), 'UTF-8');
        if ($text === '') {
            return null;
        }

        // 1) Explicit keyword map - score by longest matched keyword (best match wins)
        $bestSlug = null;
        $bestLen = 0;
        foreach (self::MAP as $slug => $keywords) {
            foreach ($keywords as $kw) {
                $kw = mb_strtolower($kw, 'UTF-8');
                if ($kw !== '' && str_contains($text, $kw)) {
                    $len = mb_strlen($kw);
                    if ($len > $bestLen) {
                        $bestLen = $len;
                        $bestSlug = $slug;
                    }
                }
            }
        }
        if ($bestSlug !== null) {
            $cat = $this->findBySlug($bestSlug);
            if ($cat) {
                return $cat;
            }
        }

        // 2) Generic fallback: category name token match
        $categories = $this->allCategories();
        $best = null;
        $bestScore = 0;
        foreach ($categories as $cat) {
            $score = $this->scoreCategory($cat, $text);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $cat;
            } elseif ($score === $bestScore && $score > 0) {
                // Prefer deeper (child) category on tie
                if ($cat->parent_id !== null && ($best?->parent_id === null)) {
                    $best = $cat;
                }
            }
        }

        // Require at least 1 full word match
        if ($best && $bestScore > 0) {
            return $best;
        }

        return null;
    }

    protected function scoreCategory(Category $cat, string $text): int
    {
        // Normalize text: hyphens/underscores -> space for "in-ear" vs "in ear"
        $normText = str_replace(['-', '_'], ' ', $text);
        $name = mb_strtolower($cat->name, 'UTF-8');
        $name = str_replace(['-', '_'], ' ', $name);
        $tokens = preg_split('/[\s&\/\-\(\)]+/u', $name, -1, PREG_SPLIT_NO_EMPTY);
        $score = 0;
        $stopwords = ['and','for','the','with','from','per','pcs','product','products','item','items','of','in','on','at','to','is','it','or','as','by','an','am','we','us','a','an'];
        $matchedTokens = 0;
        foreach ($tokens as $tok) {
            $tok = trim($tok);
            if (mb_strlen($tok) < 2) {
                continue;
            }
            if (in_array($tok, $stopwords, true)) {
                continue;
            }
            $variants = $this->tokenVariants($tok);
            foreach ($variants as $v) {
                if (preg_match('/\b' . preg_quote($v, '/') . '\b/u', $normText)) {
                    $score += mb_strlen($tok);
                    $matchedTokens++;
                    break; // count token once
                }
            }
        }
        // Bonus for multiple token matches (e.g. "Dog Food" 2 tokens vs "Premium" 1 token)
        if ($matchedTokens > 1) {
            $score += $matchedTokens * 5;
        }
        // Bonus for parent name match (e.g. "Electronics" rarely in product, but helps)
        if ($cat->parent) {
            $parentName = mb_strtolower($cat->parent->name, 'UTF-8');
            $parentTokens = preg_split('/[\s&\/\-\(\)]+/u', $parentName, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($parentTokens as $ptok) {
                if (mb_strlen($ptok) < 2 || in_array($ptok, $stopwords, true)) continue;
                foreach ($this->tokenVariants($ptok) as $pv) {
                    if (preg_match('/\b' . preg_quote($pv, '/') . '\b/u', $normText)) {
                        $score += 1; // small bonus for parent
                        break;
                    }
                }
            }
        }
        return $score;
    }

    protected function tokenVariants(string $tok): array
    {
        $tok = mb_strtolower($tok, 'UTF-8');
        $variants = [$tok];
        // Irregular plurals
        $irregular = [
            'mice' => 'mouse',
            'mouse' => 'mice',
            'keyboards' => 'keyboard',
            'keyboard' => 'keyboards',
            'cameras' => 'camera',
            'camera' => 'cameras',
            'speakers' => 'speaker',
            'speaker' => 'speakers',
            'earbuds' => 'earbud',
            'earbud' => 'earbuds',
            'headphones' => 'headphone',
            'headphone' => 'headphones',
            'watches' => 'watch',
            'watch' => 'watches',
            'chargers' => 'charger',
            'charger' => 'chargers',
            'cables' => 'cable',
            'cable' => 'cables',
            'batteries' => 'battery',
            'battery' => 'batteries',
            'children' => 'child',
            'categories' => 'category',
        ];
        if (isset($irregular[$tok])) {
            $variants[] = $irregular[$tok];
        }
        // Regular plural: remove/add trailing s
        if (str_ends_with($tok, 's') && mb_strlen($tok) > 3) {
            $variants[] = rtrim($tok, 's');
            // handle "ies" -> "y" (batteries -> battery)
            if (str_ends_with($tok, 'ies')) {
                $variants[] = substr($tok, 0, -3) . 'y';
            }
        } else {
            $variants[] = $tok . 's';
            if (str_ends_with($tok, 'y')) {
                $variants[] = substr($tok, 0, -1) . 'ies';
            }
        }
        return array_unique($variants);
    }

    protected function findBySlug(string $slug): ?Category
    {
        return $this->allCategories()->firstWhere('slug', $slug);
    }

    protected function allCategories()
    {
        if ($this->categoryCache === null) {
            $this->categoryCache = Category::where('is_active', true)->get();
        }
        return collect($this->categoryCache);
    }
}
