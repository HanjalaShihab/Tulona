<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Str;

class CategoryDetector
{
    /**
     * Priority keyword map slug => keywords (lowercase). Hand-tuned, longest-match wins.
     * Covers Bengali, brand-specific and ambiguous cases that must outrank generic fallback.
     */
    protected const PRIORITY_MAP = [
        // Most specific first - security cameras before generic cameras/phones
        'security-cameras' => ['wifi camera','cctv','security camera','surveillance','meari s1','meari','portable wifi','s1 portable'],
        // Programming & Freelancing (must be before Books)
        'programming-and-freelancing' => [
            'প্রোগ্রামিং','পাইথন','জাভাস্ক্রিপ্ট','javascript','html','css','অ্যালগরিদম','algorithm','ফ্রিল্যান্সিং','freelancing','সাইবার','cyber','হাবলু','ফুলস্ট্যাক','r প্রোগ্রামিং','seo','facebook marketing','industrial automation',
            'programming er','programming for','habuder','python diye',
            // Bengali SEO / marketing variants that appear in CSV
            'সার্চ ইঞ্জিন','অপ্টিমাইজেশন','স্মার্ট ফেসবুক','ফেসবুক মার্কেটিং','ইন্ডাস্ট্রিয়াল অটোমেশন','ইন্ডাস্ট্রিয়াল অটোমেশন',
        ],
        // Keyboards & Mice - must be before Gaming Consoles to avoid "gaming keyboard" -> gaming consoles
        'keyboards-mice' => ['keyboard','keyboards','mouse','mice','gaming mouse','wireless mouse','gaming keyboard','mechanical keyboard','wireless keyboard','mechanical gaming keyboard','wireless gaming keyboard'],
        // Headphones & Earbuds
        'headphones-earbuds' => [
            'earphone','earbud','headphone','headset','soundcore','anker motorola','motorola','bullets','tws','bluetooth','fantech','capsule','in ear','a4 tech','mi in ear','oneplus bullets','oneplus nord','gaming headset','gaming headphone','gaming headphones','rgb headphone','rgb headset',
        ],
        // Presentation Supplies for presenter/mount/webcam conference
        'presentation-supplies' => ['presenter','wireless presenter','presentation supplies','presentation'],
        'camera-accessories' => ['tv mount','wall mount','video bar','video conference','conference cam','meetup','webcam','mount for video'],
        // Beauty & Personal Care (before Books to catch The Ordinary) - concise for CSV drafts, covers the 36 failing examples
        'beauty-personal-care' => [
            'skincare','serum','niacinamide','the ordinary','beauty','ordinary','lipstick','makeup','matte',
            'face wash','facewash','cleanser','cleansing foam','acne','salicylic','salicylic acid','pimple','blackheads','nose strip','pore strips','mesta','mesta guard','aloevera','aloe vera','permethrin','scabies','derma roller','micro needle','hair growth','beard growth','hair fall','skin cream','face cream','face serum','sheet mask','sunscreen','spf','toner','scrub','peeling','whitening','brightening',
            'castor oil','rosemary oil','jojoba oil','coconut oil','almond oil','onion','hair oil','scalp','dandruff','silky tresses','shampoo','conditioner','hair shampoo','hair mask','hair treatment','haircare','beard oil','mustache',
            'perfume','fragrance','oud','white oud','bella vita','deodorant','body spray','kajal','waterproof kajal','eye makeup','depilatory','wax strips','paper wax','lice','comb','nit','headlice','green tea','rice serum','vitamin c','retinol','hyaluronic','collagen','zafran','acne clearing','gel cleanser','sunscreen gel','low ph','daily gel','mesta guard','aloevera face wash','salicylic acid','acne solution','nose strip','derma roller','permethrin soap','hair growth','skin cream','acne prone','japan sakura','salicylic mask','green tea mask','beard growth','rice mask','white oud','jojoba oil','sunscreen','kajal','nose pore',
        ],
        // Health & Medical - heel pad etc
        'medical-supplies' => ['heel pad','heel pads','crack repair','pain relief','silicone heel','heel cushion','pad socks','heel pad socks','silicone','heel','pad','socks','cushion','crack','pain','relief','silicone heel pad','pain relief cushion','crack repair'],
        'health-wellness' => ['medical supplies','health wellness','wellness','medical','health','wellness','health care','medical care'],
        // Grocery - jaitun/olive oil
        'cooking-oils' => ['jaitun oil','olive oil','jaitun','olive','cooking oil','cooking oils','mustard oil','soybean oil','sunflower oil'],
        'grocery-food' => ['grocery','food','jaitun','olive oil','cooking oil','organic foods','organic','jaitun oil'],
        // Shoe accessories for heel pad socks alternative
        'shoe-accessories' => ['shoe accessories','heel pad','pad socks','heel cushion','shoe pad','foot care','heel protector','heel socks'],
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
        // EV Chargers - must beat Electric Vehicles for "Electric Vehicle Charger" products
        'ev-chargers' => ['ev charger','ev chargers','electric vehicle charger','electric vehicle chargers','electric vehicle charging','ev charging'],
        // Office Furniture - "Office Chair" should go to furniture, not generic stationery
        'office-furniture' => ['office furniture','office chair','office chairs','office desk','office table'],
    ];

    /**
     * Parent-level synonyms used to expand child keywords.
     * Key is base parent slug (without trailing -2) => list of synonyms (lowercase).
     * Only true synonyms for the parent itself, not child names, to avoid parent stealing child products.
     */
    protected const PARENT_SYNONYMS = [
        'electronics' => ['electronic','gadget','device','tech'],
        'fashion-apparel' => ['fashion','apparel','clothing','garment','wear'],
        'shoes' => ['shoe','footwear','sneaker'],
        'bags-luggage' => ['bag','luggage','backpack','suitcase'],
        'jewelry-accessories' => ['jewelry','jewellery','accessory'],
        'beauty-personal-care' => ['beauty','personal care','cosmetic'],
        'health-wellness' => ['health','wellness'],
        'home-living' => ['home','living','house','furniture','decor'],
        'kitchen-dining' => ['kitchen','dining','cook','culinary'],
        'grocery-food' => ['grocery','food'],
        'baby-kids' => ['baby','kid','child','infant','toddler'],
        'toys-games' => ['toy','game'],
        'sports-fitness' => ['sport','fitness','gym','exercise'],
        'automotive' => ['car','vehicle','auto','motor','motorcycle','automobile'],
        'books-media' => ['book','media'],
        'office-stationery' => ['office','stationery'],
        'tools-industrial' => ['tool','industrial'],
        'garden-outdoor' => ['garden','outdoor'],
        'pet-supplies' => ['pet','animal'],
        'arts-crafts-hobbies' => ['art','craft','hobby'],
        'musical-instruments' => ['music','instrument'],
        'musical-instruments-2' => ['music','instrument'],
        'appliances' => ['appliance'],
        'home-appliances-smart-home' => ['smart home','smart','automation'],
        'travel' => ['travel'],
        'religious-cultural-products' => ['religious','cultural','prayer','traditional'],
        'gifts-occasions' => ['gift','occasion'],
        'luxury-premium' => ['luxury','premium'],
        'collectibles-memorabilia' => ['collectible','memorabilia'],
        'digital-products' => ['digital'],
        'services' => ['service'],
        'b2b-business-supplies' => ['b2b','business'],
        'agriculture' => ['agriculture','farm'],
        'renewable-energy' => ['solar','renewable','energy'],
        'security-safety' => ['security','safety'],
        'fashion-beauty-for-specific-demographics' => ['mens','womens'],
    ];

    protected $categoryCache = null;
    protected $dynamicMapCache = null;

    public function detect(string $name, ?string $description = null): ?Category
    {
        $text = mb_strtolower(trim($name . ' ' . ($description ?? '')), 'UTF-8');
        if ($text === '') {
            return null;
        }

        // Combined keyword search: priority + comprehensive dynamic map, longest match wins
        $bestSlug = null;
        $bestLen = 0;

        // Helper to evaluate a map
        $evaluateMap = function (array $map) use ($text, &$bestSlug, &$bestLen) {
            foreach ($map as $slug => $keywords) {
                foreach ($keywords as $kw) {
                    $kwLower = mb_strtolower($kw, 'UTF-8');
                    if ($kwLower === '') continue;
                    if (!$this->keywordMatches($kwLower, $text)) continue;
                    $len = mb_strlen($kwLower);
                    if ($len > $bestLen) {
                        $bestLen = $len;
                        $bestSlug = $slug;
                    }
                }
            }
        };

        $evaluateMap(self::PRIORITY_MAP);
        $evaluateMap($this->dynamicMap());

        if ($bestSlug !== null) {
            $cat = $this->findBySlug($bestSlug);
            if ($cat) {
                return $cat;
            }
        }

        // 3) Generic fallback: category name token match (covers any future categories)
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

        if ($best && $bestScore > 0) {
            return $best;
        }

        return null;
    }

    /**
     * Check if keyword appears in text as whole word/phrase.
     * For Bangla/non-latin, fall back to substring; for Latin, use word boundaries
     * and consider singular/plural variants of the last word (e.g. "camera" ↔ "cameras").
     */
    protected function keywordMatches(string $keyword, string $text): bool
    {
        $keyword = trim($keyword);
        if ($keyword === '') return false;

        // If keyword contains Bangla or non-ascii, use simple str_contains
        if (preg_match('/[^\x00-\x7F]/u', $keyword)) {
            return str_contains($text, $keyword);
        }

        // Normalize hyphens/underscores to space for both
        $normText = str_replace(['-', '_'], ' ', $text);
        $normKw = str_replace(['-', '_'], ' ', $keyword);
        $normText = (string) preg_replace('/\s+/', ' ', $normText);
        $normKw = (string) preg_replace('/\s+/', ' ', trim($normKw));

        $candidates = [$normKw];
        // Add singular/plural variants of the last word for phrase keywords (e.g. "security camera" ↔ "security cameras")
        $parts = preg_split('/\s+/', $normKw);
        if ($parts !== false && count($parts) > 0) {
            $last = array_pop($parts);
            $prefix = implode(' ', $parts);
            $variants = $this->tokenVariants($last);
            foreach ($variants as $v) {
                if ($v === $last) continue;
                $candidates[] = $prefix !== '' ? $prefix . ' ' . $v : $v;
            }
        }

        foreach ($candidates as $cand) {
            $pattern = '/\b' . preg_quote($cand, '/') . '\b/u';
            if (preg_match($pattern, $normText)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Build comprehensive keyword map for every active category not covered by PRIORITY_MAP.
     * Each slug maps to a list of lowercase keywords that should trigger it.
     */
    protected function dynamicMap(): array
    {
        if ($this->dynamicMapCache !== null) {
            return $this->dynamicMapCache;
        }

        $categories = $this->allCategories();

        $map = [];
        foreach ($categories as $cat) {
            $slug = $cat->slug;
            if (isset(self::PRIORITY_MAP[$slug])) {
                continue; // priority already handles this slug
            }

            $keywords = $this->generateKeywordsForCategory($cat);
            if (!empty($keywords)) {
                $map[$slug] = $keywords;
            }
        }

        $this->dynamicMapCache = $map;

        return $map;
    }

    /**
     * Generate keywords for a single category.
     * Covers name, slug, singular variants, parent-qualified and synonym expansions.
     */
    protected function generateKeywordsForCategory(Category $cat): array
    {
        $keywords = [];

        $name = trim($cat->name);
        $nameLower = mb_strtolower($name, 'UTF-8');
        // Normalized: & -> and, remove apostrophes, collapse spaces
        $nameNorm = str_replace(['&', "'", "’", "‘"], ['and', '', '', ''], $nameLower);
        $nameNorm = (string) preg_replace('/\s+/', ' ', $nameNorm);
        $nameNorm = trim($nameNorm);

        // Base slug without trailing -2, -3, then spaced
        $baseSlug = (string) preg_replace('/-\d+$/', '', $cat->slug);
        $slugSpaced = str_replace(['-', '_'], ' ', $baseSlug);
        $slugSpaced = mb_strtolower($slugSpaced, 'UTF-8');
        $slugSpaced = (string) preg_replace('/\s+/', ' ', $slugSpaced);

        // Singular forms
        $nameSingular = $this->singularize($nameNorm);
        $slugSingular = $this->singularize($slugSpaced);

        // Generic keywords for this category (even for duplicates, generic stays for fallback)
        $keywords[] = $nameLower;
        if ($nameNorm !== $nameLower) {
            $keywords[] = $nameNorm;
        }
        if ($nameSingular !== $nameLower && $nameSingular !== $nameNorm) {
            $keywords[] = $nameSingular;
        }
        if ($slugSpaced !== $nameLower && $slugSpaced !== $nameNorm) {
            $keywords[] = $slugSpaced;
        }
        if ($slugSingular !== $slugSpaced && $slugSingular !== $nameLower) {
            $keywords[] = $slugSingular;
        }

        // Token-level keywords: each meaningful word in name (e.g. "Vitamins & Supplements" -> "vitamins","supplements","vitamin","supplement")
        // For multi-word names, single generic words like "technology" would cause false positives (e.g. "Noise-canceling technology" -> wearable-technology)
        // and "size" for Plus Size matching "All Sizes" in derma roller, "control" for Remote-Control Toys matching "Hair Fall Control", "soap" for Soap Making matching "Permethrin Soap"
        // so we filter a generic stoplist.
        $stopTokens = ['and','for','the','with','from','per','pcs','product','products','item','items','of','in','on','at','to','is','it','or','as','by','an','am','we','us','a',
            'technology','technologies','equipment','supplies','supply','service','services','device','devices','accessory','accessories','material','materials','system','systems','goods','merchandise','collection','set','kit','pack','bundle','general','premium','luxury',
            'size','sizes','plus','control','remote','toys','toy','soap','making','oil','cream','wash','foam','strip','roller','needle','therapy','skin','hair','beard','face','facial','growth','solution','cleanser','cleansing','acne','serum','mask','shampoo','conditioner','sunscreen','gel','guard','mesta','aloevera','salicylic','acid','nose','blackheads','remover','permethrin','scabies','zafran','therapy','repair','crack','pad','socks','cushion','silicone','heel','pain','relief','jaitun','olive','cooking','organic','medical','health','wellness','shoe','accessories','foot','care'];
        $nameTokens = preg_split('/[\s&\/\-\(\)]+/u', $nameNorm, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($nameTokens as $tok) {
            $tokLower = mb_strtolower(trim($tok), 'UTF-8');
            if (mb_strlen($tokLower) < 3 || in_array($tokLower, $stopTokens, true)) continue;
            $keywords[] = $tokLower;
            $tokSing = $this->singularizeToken($tokLower);
            if ($tokSing !== $tokLower) {
                $keywords[] = $tokSing;
            }
        }

        // Parent-qualified keywords (important for child disambiguation)
        if ($cat->parent) {
            $parentName = trim($cat->parent->name);
            $parentLower = mb_strtolower($parentName, 'UTF-8');
            $parentNorm = str_replace(['&', "'", "’", "‘"], ['and', '', '', ''], $parentLower);
            $parentNorm = (string) preg_replace('/\s+/', ' ', $parentNorm);
            $parentNorm = trim($parentNorm);
            $parentBaseSlug = (string) preg_replace('/-\d+$/', '', $cat->parent->slug);
            $parentSlugKey = $parentBaseSlug;

            // parent + child
            $keywords[] = $parentNorm . ' ' . $nameNorm;
            if ($nameSingular !== $nameNorm) {
                $keywords[] = $parentNorm . ' ' . $nameSingular;
            }
            // also parent slug spaced + child
            $parentSpaced = str_replace(['-', '_'], ' ', $parentBaseSlug);
            $parentSpaced = mb_strtolower($parentSpaced, 'UTF-8');
            if ($parentSpaced !== $parentNorm) {
                $keywords[] = $parentSpaced . ' ' . $nameNorm;
                if ($nameSingular !== $nameNorm) {
                    $keywords[] = $parentSpaced . ' ' . $nameSingular;
                }
            }

            // Synonyms for parent
            if (isset(self::PARENT_SYNONYMS[$parentSlugKey])) {
                foreach (self::PARENT_SYNONYMS[$parentSlugKey] as $syn) {
                    $synLower = mb_strtolower($syn, 'UTF-8');
                    $keywords[] = $synLower . ' ' . $nameNorm;
                    if ($nameSingular !== $nameNorm) {
                        $keywords[] = $synLower . ' ' . $nameSingular;
                    }
                    if ($slugSpaced !== $nameNorm) {
                        $keywords[] = $synLower . ' ' . $slugSpaced;
                        if ($slugSingular !== $slugSpaced) {
                            $keywords[] = $synLower . ' ' . $slugSingular;
                        }
                    }
                }
            }
        }

        // For top-level parents themselves, also add slug spaced singular etc already
        // Add parent synonyms as alternative keywords for the parent itself (e.g. "electronic" -> electronics)
        if ($cat->parent_id === null) {
            $baseSlug = (string) preg_replace('/-\d+$/', '', $cat->slug);
            if (isset(self::PARENT_SYNONYMS[$baseSlug])) {
                foreach (self::PARENT_SYNONYMS[$baseSlug] as $syn) {
                    $synLower = mb_strtolower($syn, 'UTF-8');
                    if (mb_strlen($synLower) >= 3) {
                        $keywords[] = $synLower;
                    }
                }
            }
        }

        // Deduplicate, filter empty and very short (<2) generic single tokens that would cause false positives
        $keywords = array_unique(array_filter(array_map('trim', $keywords), fn ($k) => $k !== '' && mb_strlen($k) >= 2));
        // Remove overly generic single words that are stopwords
        $stopSingle = ['and','for','the','with','from','per','pcs','product','products','item','items','of','in','on','at','to','is','it','or','as','by','an','am','we','us','a'];
        $keywords = array_values(array_filter($keywords, function ($k) use ($stopSingle) {
            if (!str_contains($k, ' ')) {
                if (mb_strlen($k) < 3 || in_array($k, $stopSingle, true)) {
                    return false;
                }
            }
            return true;
        }));

        // Sort by length descending so longer (more specific) considered first
        usort($keywords, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        return $keywords;
    }

    protected function singularize(string $phrase): string
    {
        $phrase = trim($phrase);
        if ($phrase === '') return $phrase;
        $parts = preg_split('/\s+/', $phrase);
        $last = array_pop($parts);
        $singularLast = $this->singularizeToken($last);
        if ($singularLast === $last) {
            return $phrase;
        }
        $parts[] = $singularLast;
        return implode(' ', $parts);
    }

    protected function singularizeToken(string $tok): string
    {
        $low = mb_strtolower($tok, 'UTF-8');
        if (str_ends_with($low, 'ies') && mb_strlen($low) > 3) {
            return substr($tok, 0, -3) . 'y';
        }
        if (str_ends_with($low, 'ses') || str_ends_with($low, 'ches') || str_ends_with($low, 'shes') || str_ends_with($low, 'xes')) {
            return substr($tok, 0, -2);
        }
        if (str_ends_with($low, 's') && mb_strlen($low) > 3) {
            return substr($tok, 0, -1);
        }
        return $tok;
    }

    protected function scoreCategory(Category $cat, string $text): int
    {
        $normText = str_replace(['-', '_'], ' ', $text);
        $name = mb_strtolower($cat->name, 'UTF-8');
        $name = str_replace(['-', '_'], ' ', $name);
        $tokens = preg_split('/[\s&\/\-\(\)]+/u', $name, -1, PREG_SPLIT_NO_EMPTY);
        $score = 0;
        $stopwords = ['and','for','the','with','from','per','pcs','product','products','item','items','of','in','on','at','to','is','it','or','as','by','an','am','we','us','a','an'];
        $matchedTokens = 0;
        foreach ($tokens as $tok) {
            $tok = trim($tok);
            if (mb_strlen($tok) < 2) continue;
            if (in_array($tok, $stopwords, true)) continue;
            $variants = $this->tokenVariants($tok);
            foreach ($variants as $v) {
                if (preg_match('/\b' . preg_quote($v, '/') . '\b/u', $normText)) {
                    $score += mb_strlen($tok);
                    $matchedTokens++;
                    break;
                }
            }
        }
        if ($matchedTokens > 1) $score += $matchedTokens * 5;
        if ($cat->parent) {
            $parentName = mb_strtolower($cat->parent->name, 'UTF-8');
            $parentTokens = preg_split('/[\s&\/\-\(\)]+/u', $parentName, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($parentTokens as $ptok) {
                if (mb_strlen($ptok) < 2 || in_array($ptok, $stopwords, true)) continue;
                foreach ($this->tokenVariants($ptok) as $pv) {
                    if (preg_match('/\b' . preg_quote($pv, '/') . '\b/u', $normText)) { $score += 2; break; }
                }
            }
            $parentBaseSlug = (string) preg_replace('/-\d+$/', '', $cat->parent->slug);
            if (isset(self::PARENT_SYNONYMS[$parentBaseSlug])) {
                foreach (self::PARENT_SYNONYMS[$parentBaseSlug] as $syn) {
                    $synLower = mb_strtolower($syn, 'UTF-8');
                    if (preg_match('/\b' . preg_quote($synLower, '/') . '\b/u', $normText)) { $score += 1; break; }
                }
            }
        } else {
            $baseSlug = (string) preg_replace('/-\d+$/', '', $cat->slug);
            if (isset(self::PARENT_SYNONYMS[$baseSlug])) {
                foreach (self::PARENT_SYNONYMS[$baseSlug] as $syn) {
                    $synLower = mb_strtolower($syn, 'UTF-8');
                    if (preg_match('/\b' . preg_quote($synLower, '/') . '\b/u', $normText)) { $score += 1; break; }
                }
            }
        }
        return $score;
    }

    protected function tokenVariants(string $tok): array
    {
        $tok = mb_strtolower($tok, 'UTF-8');
        $variants = [$tok];
        $irregular = [
            'mice' => 'mouse', 'mouse' => 'mice',
            'keyboards' => 'keyboard', 'keyboard' => 'keyboards',
            'cameras' => 'camera', 'camera' => 'cameras',
            'speakers' => 'speaker', 'speaker' => 'speakers',
            'earbuds' => 'earbud', 'earbud' => 'earbuds',
            'headphones' => 'headphone', 'headphone' => 'headphones',
            'watches' => 'watch', 'watch' => 'watches',
            'chargers' => 'charger', 'charger' => 'chargers',
            'cables' => 'cable', 'cable' => 'cables',
            'batteries' => 'battery', 'battery' => 'batteries',
            'children' => 'child', 'categories' => 'category',
        ];
        if (isset($irregular[$tok])) $variants[] = $irregular[$tok];
        if (str_ends_with($tok, 's') && mb_strlen($tok) > 3) {
            $variants[] = rtrim($tok, 's');
            if (str_ends_with($tok, 'ies')) $variants[] = substr($tok, 0, -3) . 'y';
        } else {
            $variants[] = $tok . 's';
            if (str_ends_with($tok, 'y')) $variants[] = substr($tok, 0, -1) . 'ies';
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
            $this->categoryCache = Category::with('parent')->where('is_active', true)->get();
        }
        return collect($this->categoryCache);
    }
}
