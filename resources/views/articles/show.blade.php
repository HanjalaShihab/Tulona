@extends('layouts.app')

@section('schema')
@php
  $articleSchema = array_filter([
    '@context' => 'https://schema.org', '@type' => 'Article',
    'headline' => $article->title,
    'description' => strip_tags($article->excerpt ?? ''),
    'author' => ['@type' => 'Organization', 'name' => $article->author],
    'publisher' => ['@type' => 'Organization', 'name' => 'Tulona'],
    'datePublished' => $article->published_at?->toIso8601String(),
    'dateModified' => $article->updated_at->toIso8601String(),
    'image' => $article->og_image ?: $article->featured_image,
  ]) + ($article->faqs ? ['mainEntity' => collect($article->faqs)->map(fn ($f) => [
    '@type' => 'Question', 'name' => $f['question'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['answer']],
  ])->all()] : []);
@endphp
<script type="application/ld+json">@json($articleSchema, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)</script>
@endsection

@section('content')
@php
  $isGuide = $article->type === 'guide';
  $plainText = trim(strip_tags($article->excerpt.' '.$article->content));
  $wordCount = $plainText === '' ? 0 : str_word_count($plainText);
  $readMins = max(1, (int) round($wordCount / 200));
  $initials = collect(preg_split('/\s+/', trim($article->author ?? '')))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('');
  $pickCount = $article->products->count();
@endphp
<div class="container">
  @include('partials.breadcrumbs', ['items' => array_values(array_filter([
    ['name' => 'Home', 'url' => route('home')],
    ['name' => $isGuide ? 'Guides' : 'Reviews', 'url' => $isGuide ? route('guides.index') : route('reviews.index')],
    ['name' => $article->title],
  ]))])

  <article class="art-page art-page--{{ $isGuide ? 'guide' : 'review' }}">
    <header class="art-hero">
      <div class="art-hero-top">
        <span class="art-eyebrow art-eyebrow--{{ $isGuide ? 'guide' : 'review' }}"><span class="art-dot" aria-hidden="true"></span>{{ $isGuide ? 'Buying Guide' : 'Editorial Review' }}</span>
        @if($article->category)
          <a class="art-cat" href="{{ route('categories.show', $article->category->slug) }}">{{ $article->category->name }}</a>
        @endif
      </div>
      <h1 class="art-title">{{ $article->title }}</h1>
      @if($article->excerpt)
        <p class="art-lead">{{ $article->excerpt }}</p>
      @endif
      <div class="art-meta-card">
        <span class="art-avatar" aria-hidden="true">{{ $initials ?: 'T' }}</span>
        <div class="art-meta-text">
          <span class="art-meta-item"><strong>By {{ $article->author }}</strong><span class="art-meta-role">{{ $isGuide ? 'Research desk' : 'Review desk' }}</span></span>
          <span class="art-meta-sub">
            <span>Updated {{ optional($article->updated_at)->format('M j, Y') }}</span>
            <span class="art-meta-sep" aria-hidden="true"></span>
            <span>{{ $readMins }} min read</span>
            <span class="art-meta-sep" aria-hidden="true"></span>
            <span class="art-meta-trust">Independently chosen</span>
          </span>
        </div>
      </div>
    </header>

    @if($article->featured_image)
      <figure class="art-figure">
        <img src="{{ $article->featured_image }}" alt="{{ $article->title }}" loading="lazy">
      </figure>
    @endif

    <div class="art-layout">
      <div class="art-main">
        @if(!empty($article->selection_criteria))
          <aside class="art-criteria" aria-label="How we picked">
            <h2 class="art-criteria-head"><span aria-hidden="true">&#10003;</span> How we picked</h2>
            <p class="art-criteria-sub">{{ $isGuide ? 'Every recommendation below passed all of these checks.' : 'We scored this product against all of these points.' }}</p>
            <ul>
              @foreach($article->selection_criteria as $crit)
                <li>{{ $crit }}</li>
              @endforeach
            </ul>
          </aside>
        @endif

        <div class="art-content content">{!! $article->content !!}</div>

        @if($article->products->isNotEmpty())
          <section class="art-picks" aria-label="{{ $isGuide ? 'What to buy' : 'Verdict' }}">
            <div class="art-picks-head">
              <div>
                <p class="art-sec-kicker">{{ $isGuide ? 'What to buy' : 'Verdict' }}</p>
                <h2>{{ $isGuide ? 'Our recommendations' : 'Our verdict' }}</h2>
                <p class="art-sec-sub">{{ $isGuide ? 'Ranked best-first. Prices update as stores change them.' : 'Ranked best-first, with who should skip each option.' }}</p>
              </div>
              <span class="art-picks-count">{{ $pickCount }} {{ $pickCount === 1 ? 'pick' : 'picks' }}</span>
            </div>
            <div class="art-picks-list">
              @foreach($article->products as $p)
                @php($best = $p->activeOffers->whereNotNull('current_price')->sortBy(fn ($o) => (float) $o->current_price)->first())
                <div class="pick-card">
                  <span class="pick-rank" aria-hidden="true">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                  <div class="pick-thumb">
                    @if($p->images->first())
                      <img src="{{ str_starts_with($p->images->first()->path, 'http') ? $p->images->first()->path : asset('storage/' . $p->images->first()->path) }}" alt="{{ $p->images->first()->alt_text ?: $p->name }}" loading="lazy">
                    @else
                      <span class="pick-fallback">{{ strtoupper(substr($p->brand->name ?? $p->name, 0, 1)) }}</span>
                    @endif
                    @if($loop->first)
                      <span class="pick-flag">Top pick</span>
                    @endif
                  </div>
                  <div class="pick-body">
                    <span class="pick-brand">{{ $p->brand->name ?? 'Tulona' }}</span>
                    <a class="pick-name" href="{{ route('product.show', $p->slug) }}">{{ $p->name }}</a>
                    @if($p->pivot->blurb)
                      <p class="pick-blurb">{{ $p->pivot->blurb }}</p>
                    @endif
                    <div class="pick-price">
                      @if($best)
                        <span class="pick-now">{{ \App\Support\Currency::format((float)$best->current_price, $best->currency) }}</span>
                        <span class="pick-at">at {{ $best->merchant->name }}</span>
                      @else
                        <span class="pick-at">Price unavailable</span>
                      @endif
                      @if($p->pivot->pick_label)
                        <span class="badge badge-pick">{{ $p->pivot->pick_label }}</span>
                      @endif
                    </div>
                  </div>
                  <div class="pick-actions">
                    @if($best)
                      <a class="pick-buy view-deal-btn" rel="nofollow sponsored noopener" href="{{ route('go.redirect', [$p->slug, $best->merchant->slug]) }}">View Deal</a>
                    @endif
                    <a class="pick-details" href="{{ route('product.show', $p->slug) }}">Details</a>
                  </div>
                </div>
              @endforeach
            </div>
          </section>
        @endif

        @if($article->faqs)
          <section class="art-faqs" aria-label="Frequently asked questions">
            <p class="art-sec-kicker">Quick answers</p>
            <h2>Frequently asked questions</h2>
            <div class="art-faq-list">
              @foreach($article->faqs as $faq)
                <details class="art-faq">
                  <summary>{{ $faq['question'] }}<span class="art-faq-caret" aria-hidden="true">&#9662;</span></summary>
                  <p>{{ $faq['answer'] }}</p>
                </details>
              @endforeach
            </div>
          </section>
        @endif

        <aside class="art-disclosure" aria-label="Disclosure">
          <span class="art-disclosure-ico" aria-hidden="true">&#9432;</span>
          <p><strong>Why you can trust this {{ $isGuide ? 'guide' : 'review' }}.</strong> Tulona compares live store prices independently. If you buy through a store link we may earn a commission — it never changes what we recommend or what you pay.</p>
        </aside>

        <aside class="art-author" aria-label="About the author">
          <span class="art-avatar art-avatar--lg" aria-hidden="true">{{ $initials ?: 'T' }}</span>
          <div>
            <p class="art-author-name">{{ $article->author }}</p>
            <p class="art-author-role">{{ $isGuide ? 'Buying-guide research desk' : 'Editorial review desk' }} &middot; Tulona</p>
            <p class="art-author-note">We track live prices across trusted stores so recommendations stay honest after publishing.</p>
          </div>
        </aside>
      </div>

      <aside class="art-side" aria-label="On this page">
        <div class="art-toc" data-toc hidden>
          <p class="art-toc-head">On this page</p>
          <ol class="art-toc-list" data-toc-list></ol>
        </div>
        @if($article->products->isNotEmpty())
          <div class="art-side-picks">
            <p class="art-toc-head">Top picks</p>
            <ol>
              @foreach($article->products->take(3) as $p)
                <li><a href="{{ route('product.show', $p->slug) }}"><span class="art-side-rank">{{ $loop->iteration }}.</span> {{ \Illuminate\Support\Str::limit($p->name, 52) }}</a></li>
              @endforeach
            </ol>
          </div>
        @endif
        <div class="art-side-trust">
          <p class="art-toc-head">Our promise</p>
          <p>No sponsored picks. Live prices, verified history, no fake discounts.</p>
        </div>
      </aside>
    </div>
  </article>

  @if($related->isNotEmpty())
    <section class="art-related" aria-label="Related reading">
      <div class="sec-head"><h2>Related reading</h2></div>
      <div class="art-related-grid">
        @foreach($related as $r)
          <a class="art-related-card" href="{{ route('articles.show', $r->slug) }}">
            <span class="tag">{{ $r->type === 'guide' ? 'Guide' : 'Review' }}</span>
            <h3>{{ $r->title }}</h3>
            <span class="art-related-cta">Read <span aria-hidden="true">&#8594;</span></span>
          </a>
        @endforeach
      </div>
    </section>
  @endif
</div>

<script>
(function () {
  var content = document.querySelector('.art-content');
  var toc = document.querySelector('[data-toc]');
  var list = document.querySelector('[data-toc-list]');
  if (!content || !toc || !list) return;
  var heads = content.querySelectorAll('h2');
  if (!heads.length) return;
  heads.forEach(function (h, i) {
    if (!h.id) h.id = 'section-' + (i + 1);
    var li = document.createElement('li');
    var a = document.createElement('a');
    a.href = '#' + h.id;
    a.textContent = h.textContent;
    li.appendChild(a);
    list.appendChild(li);
  });
  toc.hidden = false;
})();
</script>
@endsection
