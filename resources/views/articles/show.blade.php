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
@php($isGuide = $article->type === 'guide')
<div class="container">
  @include('partials.breadcrumbs', ['items' => array_values(array_filter([
    ['name' => 'Home', 'url' => route('home')],
    ['name' => $isGuide ? 'Guides' : 'Reviews', 'url' => $isGuide ? route('guides.index') : route('reviews.index')],
    ['name' => $article->title],
  ]))])

  <article class="art-page">
    <header class="art-hero">
      <span class="art-eyebrow"><span class="art-dot" aria-hidden="true"></span>{{ $isGuide ? 'Buying Guide' : 'Editorial Review' }}</span>
      <h1 class="art-title">{{ $article->title }}</h1>
      @if($article->excerpt)
        <p class="art-lead">{{ $article->excerpt }}</p>
      @endif
      <div class="art-meta">
        <span class="art-meta-item"><strong>By {{ $article->author }}</strong></span>
        <span class="art-meta-sep" aria-hidden="true"></span>
        <span class="art-meta-item">Updated {{ optional($article->updated_at)->format('M j, Y') }}</span>
        <span class="art-meta-sep" aria-hidden="true"></span>
        <span class="art-meta-item">Independently chosen</span>
      </div>
    </header>

    @if(!empty($article->selection_criteria))
      <aside class="art-criteria" aria-label="How we picked">
        <h2 class="art-criteria-head">How we picked</h2>
        <ul>
          @foreach($article->selection_criteria as $crit)
            <li>{{ $crit }}</li>
          @endforeach
        </ul>
      </aside>
    @endif

    <div class="art-content content">{!! $article->content !!}</div>

    @if($article->products->isNotEmpty())
      <section class="art-picks" aria-label="Our recommendations">
        <div class="art-picks-head">
          <h2>Our recommendations</h2>
          <span class="art-picks-count">{{ $article->products->count() }} {{ $article->products->count() === 1 ? 'pick' : 'picks' }}</span>
        </div>
        <div class="art-picks-list">
          @foreach($article->products as $p)
            @php($best = $p->activeOffers->whereNotNull('current_price')->sortBy(fn ($o) => (float) $o->current_price)->first())
            <div class="pick-card">
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
@endsection
