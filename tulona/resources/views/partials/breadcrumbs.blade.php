@props(['items']) {{-- [{name,url|optional}] --}}
<nav class="crumbs" aria-label="Breadcrumb">
  @foreach($items as $i => $item)
    @if(!$loop->last && !empty($item['url']))
      <a href="{{ $item['url'] }}">{{ $item['name'] }}</a><span class="sep" aria-hidden="true">›</span>
    @else
      <span aria-current="page">{{ $item['name'] }}</span>
    @endif
  @endforeach
</nav>
@section('schema')
@php
  $crumbSchema = json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => collect($items)->values()->map(fn ($it, $i) => [
      '@type' => 'ListItem', 'position' => $i + 1, 'name' => $it['name'],
    ] + (! empty($it['url']) ? ['item' => url($it['url'])] : []))->all(),
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
@endphp
<script type="application/ld+json">{!! $crumbSchema !!}</script>
@endsection
