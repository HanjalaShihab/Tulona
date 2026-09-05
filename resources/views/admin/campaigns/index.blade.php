@extends('admin._shell')
@section('page-title')Campaigns@endsection

@section('page')
<div class="prod-form-head">
  <h2 style="font-size:18px;font-weight:750">Campaigns / Flash Deals</h2>
  <div class="spacer"></div>
  <a class="btn btn-primary btn-sm" href="{{ route('admin.campaigns.create') }}">+ New Campaign</a>
</div>

<div class="table-wrap">
  <table class="data-table">
    <thead>
      <tr>
        <th>Name</th>
        <th>Theme</th>
        <th>Products</th>
        <th>Start</th>
        <th>End</th>
        <th>Status</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      @forelse($campaigns as $c)
        @php
          $isActive = $c->is_active && $c->starts_at <= now() && $c->ends_at >= now();
          $isExpired = $c->ends_at < now();
        @endphp
        <tr>
          <td>
            <strong>{{ $c->name }}</strong>
            @if($c->slug)<br><small style="color:var(--ink-3)">/{{ $c->slug }}</small>@endif
          </td>
          <td><span class="badge badge-pick">{{ ucfirst($c->theme) }}</span></td>
          <td>{{ $c->products_count }}</td>
          <td>{{ $c->starts_at?->format('M d, Y') }}</td>
          <td>{{ $c->ends_at?->format('M d, Y') }}</td>
          <td>
            @if($isActive)
              <span class="badge badge-drop">Active</span>
            @elseif($isExpired)
              <span class="badge badge-out">Expired</span>
            @else
              <span class="badge badge-stale">Upcoming</span>
            @endif
          </td>
          <td>
            <a class="btn btn-outline btn-sm" href="{{ route('admin.campaigns.edit', $c) }}">Edit</a>
            <form method="POST" action="{{ route('admin.campaigns.destroy', $c) }}" onsubmit="return confirm('Delete this campaign?')" style="display:inline">
              @csrf @method('DELETE')
              <button class="btn btn-danger btn-sm">Delete</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--ink-3)">No campaigns yet. Create a campaign to group products for flash deals, seasonal promotions, or editorial picks.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
