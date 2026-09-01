@extends('admin._shell')
@section('page-title')
Merchants
@endsection

@section('page')
@php
  $totalOffers = $merchants->sum('offers_count');
  $totalProducts = $merchants->sum('product_count');
  $activeCount = $merchants->filter(fn ($m) => $m->status === 'active')->count();
@endphp

<div class="ad-head">
  <div class="ad-icon">&#8962;</div>
  <div class="ad-head-text">
    <h1 class="ad-title">Merchants &amp; Sync</h1>
    <div class="ad-meta">Stores you track &#8212; scheduled sync runs every 6 hours with no code changes.</div>
  </div>
  <div class="ad-head-actions">
    <a class="btn btn-primary" href="{{ route('admin.merchants.create') }}">&#43; New merchant</a>
  </div>
</div>

<div class="ad-chips">
  <div class="ad-chip"><b class="tone-brand">{{ $activeCount }}</b><span>Active</span></div>
  <div class="ad-chip"><b class="tone-accent">{{ $totalProducts }}</b><span>Products tracked</span></div>
  <div class="ad-chip"><b class="tone-warn">{{ $totalOffers }}</b><span>Live offers</span></div>
  <div class="ad-chip"><b>{{ $merchants->total() }}</b><span>Total merchants</span></div>
</div>

<div class="ad-pane">
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Network</th>
          <th>Products</th>
          <th>Offers</th>
          <th>Country</th>
          <th>Last sync</th>
          <th>Status</th>
          <th style="width:210px"></th>
        </tr>
      </thead>
      @forelse($merchants as $m)
        <tr>
          <td>
            <div class="ad-cell-main">
              <span class="ad-thumb">{{ strtoupper(substr($m->name,0,1)) }}</span>
              <span>
                <a href="{{ route('admin.merchants.edit', $m) }}"><strong>{{ $m->name }}</strong></a>
                <small>/{{ $m->slug }}</small>
              </span>
            </div>
          </td>
          <td>{{ $m->network?->name ?? '&#8212;' }}</td>
          <td>{{ $m->product_count }}</td>
          <td>{{ $m->offers_count }}</td>
          <td>{{ $m->country ?? '&#8212;' }}</td>
          <td>
            {{ $m->last_synced_at?->diffForHumans() ?? 'never' }}
            @if($m->sync_status === 'failed')
              <span class="status-pill status-failed">failed</span>
            @endif
          </td>
          <td>
            <span class="status-pill status-{{ $m->status }}">{{ ucfirst($m->status) }}</span>
          </td>
          <td>
            <div class="ad-actions">
              <form method="POST" action="{{ route('admin.merchants.sync', $m) }}">
                @csrf
                <button class="btn btn-outline btn-sm">Sync now</button>
              </form>
              @if($m->status === 'active')
                <form method="POST" action="{{ route('admin.merchants.destroy', $m) }}" onsubmit="return confirm('Disable merchant and all its offers?')">
                  @csrf @method('DELETE')
                  <button class="btn btn-danger btn-sm">Disable</button>
                </form>
              @endif
            </div>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="8">
            <div class="ad-table-empty">
              <b>No merchants yet</b>
              Add your first store to start syncing offers.
            </div>
          </td>
        </tr>
      @endforelse
    </table>
  </div>
  <div style="padding:16px 20px;border-top:1px solid var(--line-light)">
    {{ $merchants->links('partials.pagination') }}
  </div>
</div>
@endsection
