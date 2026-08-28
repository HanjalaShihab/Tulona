@extends('layouts.app')

@section('content')
<div class="container">
  <header style="padding:22px 0 4px">
    <h1>{{ $type === 'guide' ? 'Buying Guides' : 'Editorial Reviews' }}</h1>
    <p style="color:var(--ink-2)">Independent, useful content — no SEO spam. Every guide states how we picked, what we rejected, and who should skip the product entirely.</p>
  </header>

  @if($articles->isEmpty())
    @include('partials.empty', ['icon'=>'📝','text'=>'Nothing published here yet.'])
  @else
    <div class="prod-grid" style="padding-bottom:32px">
      @foreach($articles as $a)
        <article class="card"><div class="card-body">
          <span class="card-brand">{{ $a->type === 'guide' ? 'Guide' : 'Review' }} · {{ $a->published_at->format('M j, Y') }}</span>
          <a class="card-name" href="{{ route('articles.show', $a->slug) }}">{{ $a->title }}</a>
          <p style="font-size:13.5px;color:var(--ink-2)">{{ \Illuminate\Support\Str::limit(strip_tags($a->excerpt ?? $a->content), 130) }}</p>
        </div></article>
      @endforeach
    </div>
    {{ $articles->links('partials.pagination') }}
  @endif
</div>
@endsection
