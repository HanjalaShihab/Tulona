{{-- Rendered sitemap (@lastmod is Carbon|null). --}}
@php
    /** @var \Illuminate\Support\Collection $entries */
    $entries = collect($entries);
@endphp
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach($entries as $entry)
    @php
        $url = is_array($entry) ? $entry['url'] : $entry;
        $lastmod = is_array($entry) ? ($entry['lastmod'] ?? null) : null;
    @endphp
    <url>
        <loc>{{ $url }}</loc>
        @if($lastmod !== null)
            <lastmod>{{ $lastmod->toDateString() }}</lastmod>
        @endif
    </url>
@endforeach
</urlset>
