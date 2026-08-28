@extends('layouts.app')

@section('schema')
@php
  $articleSchema = json_encode(array_filter([
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
  ])->all()] : []), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
@endphp
<script type="application/ld+json">{!! $articleSchema !!}</script>
@endsection

@section('content')
<div class="container">
  @include('partials.breadcrumbs', ['items' => array_values(array_filter([
    ['name'=>'Home','url'=>route('home')],
    ['name'=>$article->type === 'guide' ? 'Guides':'Reviews','url'=>$article->type==='guide'?route('guides.index'):route('reviews.index')],
    ['name'=>$article->title],
  ]))])

  <article class="article-body">
    <span class="card-brand">{{ $article->type === 'guide' ? 'Buying Guide' : 'Review' }} · Updated {{ optional($article->published_at)->diffForHumans() }} · By {{ $article->author }}</span>
    <h1 style="margin:8px 0 4px;font-size:clamp(24px,3vw,32px)">{{ $article->title }}</h1>

    <div class="alert alert-ok" role="note" style="margin-top:14px">
      <strong>Affiliate disclosure:</strong> we may earn a commission when you purchase through links on this page. It never affects our picks or costs you extra.
    </div>

    @if(!empty($article->selection_criteria))
      <h2>How we picked</h2>
      <ul>@foreach($article->selection_criteria as $crit)<li>{{ $crit }}</li>@endforeach</ul>
    @endif

    <div class="content">{!! $article->content !!}</div>

    {{-- Recommended products with honest blurbs + CTAs --}}
    @if($article->products->isNotEmpty())
      <h2>Our recommendations</h2>
      @foreach($article->products as $p)
        @php($best = $p->bestOffer())
        <div class="pick-card">
          <div style="font-size:34px;text-align:center">{{ strtoupper(substr($p->brand->name ?? 'P',0,1)) }}</div>
          <div>
            <strong><a href="{{ route('product.show', $p->slug) }}">{{ $p->name }}</a></strong>
            @if($p->pivot->pick_label)<span class="badge badge-pick" style="margin-left:6px">{{ $p->pivot->pick_label }}</span>@endif
            @if($best)<br><small>{{ \App\Support\Currency::format((float)$best->current_price, $best->currency) }} at {{ $best->merchant->name }}</small>@endif
            @if($p->pivot->blurb)<br><small style="color:var(--ink-2)">{{ $p->pivot->blurb }}</small>@endif
          </div>
          <div>
            @if($best)<a class="btn btn-primary btn-sm" rel="nofollow sponsored noopener" href="{{ route('go.redirect', [$p->slug, $best->merchant->slug]) }}">View Deal</a>@endif
            <a class="btn btn-outline btn-sm" href="{{ route('product.show', $p->slug) }}">Details</a>
          </div>
        </div>
      @endforeach
    @endif

    {{-- FAQ --}}
    @if($article->faqs)
      <h2>Frequently asked questions</h2>
      @foreach($article->faqs as $faq)
        <h3 style="margin-top:14px">{{ $faq['question'] }}</h3>
        <p>{{ $faq['answer'] }}</p>
      @endforeach
    @endif

    <p style="margin-top:26px"><a href="{{ url('/affiliate-disclosure') }}">How affiliate links work →</a></p>
  </article>

  @if($related->isNotEmpty())
    <section class="section" style="padding-bottom:40px;max-width:800px">
      <div class="sec-head"><h2>Related reading</h2></div>
      <ul style="line-height:2;padding-left:18px">
        @foreach($related as $r)<li><a href="{{ route('articles.show', $r->slug) }}">{{ $r->title }}</a></li>@endforeach
      </ul>
    </section>
  @endif
</div>
@endsection
