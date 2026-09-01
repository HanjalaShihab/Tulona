@extends('layouts.app')

@section('content')
<div class="premium-hero">
  <div class="container">
    <h1 data-reveal>{{ $type === 'guide' ? 'Buying Guides' : 'Editorial Reviews' }}</h1>
    <p data-reveal data-delay="80">Independent, useful content - no SEO spam. Every guide states how we picked, what we rejected, and who should skip the product entirely.</p>
    <div class="hero-meta" data-reveal data-delay="160">
      <span>{{ $articles->total() }} articles</span>
      <span>No sponsored picks</span>
    </div>
  </div>
</div>

<div class="container" style="margin-top:32px">
  @if($articles->isEmpty())
    @include('partials.empty', ['icon' => '&#128221;', 'text' => 'Nothing published here yet.'])
  @else
    <div class="guide-grid" style="padding-bottom:32px">
      @foreach($articles as $a)
        <a class="guide-card" href="{{ route('articles.show', $a->slug) }}" data-reveal>
          <span class="tag">{{ $a->type === 'guide' ? 'Buying Guide' : 'Review' }}</span>
          <h3>{{ $a->title }}</h3>
          <p>{{ \Illuminate\Support\Str::limit(strip_tags($a->excerpt ?? $a->content), 120) }}</p>
          <div style="display:flex;align-items:center;justify-content:space-between">
            <small style="color:var(--ink-3)">{{ $a->published_at->format('M j, Y') }}</small>
            <span class="guide-cta">Read <span aria-hidden="true">&#8594;</span></span>
          </div>
        </a>
      @endforeach
    </div>
    {{ $articles->links('partials.pagination') }}
  @endif
</div>
@endsection
