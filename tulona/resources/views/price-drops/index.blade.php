@extends('layouts.app')

@section('content')
<div class="container">
  <header style="padding:22px 0 4px">
    <h1>Recent Price Drops</h1>
    <p style="color:var(--ink-2)">Products whose prices recently went down at trusted stores — ranked using our own verified price history.</p>
  </header>

  <div class="sort-bar chip-links" style="padding-top:10px">
    <a href="{{ route('drops.index', ['sort'=>'percent']) }}" {{ $sort==='percent' ? 'class=on' : '' }}>Largest % drop</a>
    <a href="{{ route('drops.index', ['sort'=>'amount']) }}" {{ $sort==='amount' ? 'class=on' : '' }}>Biggest amount saved</a>
    <a href="{{ route('drops.index', ['sort'=>'recent']) }}" {{ $sort==='recent' ? 'class=on' : '' }}>Most recent</a>
  </div>

  @if($drops->isEmpty())
    @include('partials.empty', ['icon'=>'📉','text'=>'No price drops recorded yet. As merchants update prices they will appear here.'])
  @else
    <div class="pane tbl-scroll" style="margin-bottom:24px;padding:0;border-radius:var(--radius)">
      <table class="compare-stores">
        <thead><tr><th>Product</th><th>Store</th><th>Was → Now</th><th>Drop</th><th>When</th><th></th></tr></thead>
        <tbody>
          @foreach($drops as $d)
            <tr>
              <td><strong>{{ $d->product->name }}</strong></td>
              <td>{{ $d->offer?->merchant?->name }}</td>
              <td><s class="price-old">{{ \App\Support\Currency::format((float)$d->previous_price, $d->currency) }}</s> → <strong>{{ \App\Support\Currency::format((float)$d->current_price, $d->currency) }}</strong></td>
              <td><span class="badge badge-drop">↓ {{ round($d->drop_percent,1) }}% · −{{ \App\Support\Currency::format((float)$d->drop_amount, $d->currency) }}</span></td>
              <td>{{ $d->occurred_at->diffForHumans() }}</td>
              <td><a class="btn btn-outline btn-sm" href="{{ route('product.show', $d->product->slug) }}">Compare Prices</a></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
  {{ $drops->links('partials.pagination') }}
</div>
@endsection
