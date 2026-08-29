@extends('admin.analytics._layout')

@section('analytics')

<div class="ana-surface">
    <div class="ana-pane-head">
        <h2 class="ana-pane-title">Product performance <span class="ana-count">{{ $products->count() }} published</span></h2>
        <nav class="sort-pills" aria-label="Sort products">
            @php($toggle = array_merge(request()->except(['sort', 'dir']), ['sort' => 'clicks', 'dir' => ($sort['key'] === 'clicks' && $sort['dir'] === 'desc') ? 'asc' : 'desc']))
            <a class="sort-pill {{ $sort['key'] === 'clicks' ? 'active' : '' }}" href="{{ url()->current().'?'.http_build_query($toggle) }}">Most Clicked</a>
            <span class="sort-pill disabled" title="Requires product-page view tracking">Most Viewed</span>
            <span class="sort-pill disabled" title="Requires page-view impressions">Highest CTR</span>
            <span class="sort-pill disabled" title="Requires page-view impressions">Lowest CTR</span>
        </nav>
    </div>

    <p class="ana-foot">
        Column sorting works on real click counts, merchant coverage, price and data completeness.
        “Views” and “CTR” are disabled until page-view tracking is recorded.
    </p>

    @if($products->isEmpty())
        @include('admin.analytics._empty', [
            'title' => 'No published products',
            'body' => 'Publish products from the catalog to start tracking their performance.',
        ])
    @else
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="num">Views</th>
                        <th class="num">Affiliate clicks</th>
                        <th class="num">CTR</th>
                        <th class="num">Best price</th>
                        <th class="num">Merchants</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($products as $p)
                    <tr>
                        <td><a href="{{ route('admin.products.edit', $p->id) }}">{{ $p->name }}</a></td>
                        <td class="num">@include('admin.analytics._await', ['title' => 'Requires product-page view tracking'])</td>
                        <td class="num"><b>{{ number_format($p->clicks) }}</b></td>
                        <td class="num">@include('admin.analytics._await', ['title' => 'CTR needs page-view impressions'])</td>
                        <td class="num">{{ $p->price !== null ? \App\Support\Currency::format($p->price, 'BDT') : '—' }}</td>
                        <td class="num">{{ $p->merchants }}</td>
                        <td>
                            <span class="status-pill status-{{ $p->hasAffiliate ? 'active' : 'draft' }}">Published</span>
                            @if(! $p->hasAffiliate)
                                <span class="chip chip-warn" title="No active offer with an affiliate URL — this product earns no commission">⚠ no affiliate link</span>
                            @elseif(! $p->hasPrice)
                                <span class="chip chip-warn" title="Active offers are missing a current price">⚠ no price</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <p class="ana-foot">Sorted by {{ $sort['dir'] === 'asc' ? 'ascending' : 'descending' }} {{ $sort['key'] }}. Use the pills to change the ranking.</p>
    @endif
</div>

@endsection