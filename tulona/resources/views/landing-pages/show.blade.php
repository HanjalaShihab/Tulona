@extends('layouts.app')

@section('schema')
@if(!empty($schema))
<script type="application/ld+json">@json($schema)</script>
@endif
@endsection

@section('content')
<div class="container">
  @include('partials.breadcrumbs', ['items' => [
    ['name' => 'Home', 'url' => route('home')],
    ['name' => $page->title],
  ]])

  <header style="margin:18px 0 24px">
    <h1>{{ $page->title }}</h1>
    @if($page->excerpt)<p style="color:var(--ink-2);max-width:720px">{{ $page->excerpt }}</p>@endif
  </header>

  @forelse($page->sections ?? [] as $section)
    @php($type = $section['type'] ?? '')
    @if($type === 'hero')
      <section class="hero-sect" style="padding:32px;border-radius:16px;background:var(--bg-2);margin-bottom:20px">
        @if(!empty($section['image_url']))<img src="{{ $section['image_url'] }}" alt="{{ $section['heading'] ?? $page->title }}" style="max-height:260px;width:100%;object-fit:cover;border-radius:12px;margin-bottom:18px">@endif
        <h2 style="font-size:24px;margin:0 0 8px">{{ $section['heading'] ?? '' }}</h2>
        @if(!empty($section['subheading']))<p style="color:var(--ink-2);max-width:640px">{{ $section['subheading'] }}</p>@endif
        @if(!empty($section['cta_text']) && !empty($section['cta_url']))
          <a class="btn btn-primary" style="margin-top:12px" href="{{ $section['cta_url'] }}">{{ $section['cta_text'] }}</a>
        @endif
      </section>

    @elseif($type === 'text')
      <section class="text-sect" style="margin-bottom:20px">
        @if(!empty($section['heading']))<h2 style="font-size:20px">{{ $section['heading'] }}</h2>@endif
        @if(!empty($section['body']))<div style="color:var(--ink-2);line-height:1.7">{{ $section['body'] }}</div>@endif
      </section>

    @elseif($type === 'products')
      @if($page->products->isNotEmpty())
        <section class="products-sect" style="margin-bottom:24px">
          @if(!empty($section['title']))<h2 style="font-size:20px;margin-bottom:4px">{{ $section['title'] }}</h2>@endif
          @if(!empty($section['description']))<p style="color:var(--ink-2);margin-bottom:14px">{{ $section['description'] }}</p>@endif
          <div class="grid cards-grid">
            @foreach($page->products as $product)
              @include('partials.product-card', ['product' => $product])
            @endforeach
          </div>
        </section>
      @endif

    @elseif($type === 'comparisons')
      @if($page->comparisons->isNotEmpty())
        <section class="comparisons-sect" style="margin-bottom:24px">
          @if(!empty($section['title']))<h2 style="font-size:20px;margin-bottom:4px">{{ $section['title'] }}</h2>@endif
          @if(!empty($section['description']))<p style="color:var(--ink-2);margin-bottom:14px">{{ $section['description'] }}</p>@endif
          <div class="grid cards-grid">
            @foreach($page->comparisons as $comparison)
              @include('partials.comparison-card', ['comparison' => $comparison])
            @endforeach
          </div>
        </section>
      @endif

    @elseif($type === 'faq')
      @if(!empty($section['items']))
        <section class="faq-sect" style="margin-bottom:24px">
          @if(!empty($section['heading']))<h2 style="font-size:20px;margin-bottom:14px">{{ $section['heading'] }}</h2>@endif
          @foreach($section['items'] as $item)
            <details style="border-bottom:1px solid var(--line);padding:12px 0">
              <summary style="cursor:pointer;font-weight:600">{{ $item['question'] }}</summary>
              <p style="margin:8px 0 0;color:var(--ink-2)">{{ $item['answer'] ?? '' }}</p>
            </details>
          @endforeach
        </section>
      @endif

    @elseif($type === 'cta')
      @if(!empty($section['heading']))
        <section class="cta-sect" style="padding:28px;border-radius:16px;background:var(--bg-2);text-align:center;margin-bottom:20px">
          <h2 style="font-size:22px;margin:0 0 6px">{{ $section['heading'] }}</h2>
          @if(!empty($section['text']))<p style="color:var(--ink-2);margin:0 0 14px">{{ $section['text'] }}</p>@endif
          @if(!empty($section['button_text']) && !empty($section['button_url']))
            <a class="btn btn-primary" href="{{ $section['button_url'] }}">{{ $section['button_text'] }}</a>
          @endif
        </section>
      @endif
    @endif
  @empty
    <p style="color:var(--ink-3)">This page has no sections yet.</p>
  @endforelse
</div>
@endsection
