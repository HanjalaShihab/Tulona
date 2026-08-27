@extends('admin._shell')
@section('page-title')
Comparisons
@endsection
@section('page')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
  <div>
    <form method="GET" class="filter-bar">
      <select name="status">
        <option value="">All statuses</option>
        @foreach(['draft','published','archived'] as $s)<option value="{{ $s }}" {{ request('status')==$s?'selected':'' }}>{{ ucfirst($s) }}</option>@endforeach
      </select>
      <button class="btn btn-primary btn-sm">Filter</button>
    </form>
  </div>
  <a class="btn btn-primary" href="{{ route('admin.comparisons.create') }}">＋ New comparison</a>
</div>

<table class="data-table">
  <thead><tr><th>Title</th><th>Products</th><th>Status</th><th>Featured</th><th>Published</th><th></th></tr></thead>
  @forelse($comparisons as $c)
    <tr>
      <td><a href="{{ route('admin.comparisons.edit', $c) }}"><strong>{{ $c->title }}</strong></a></td>
      <td>{{ $c->products_count }}</td>
      <td><span class="status-pill status-{{ $c->status }}">{{ ucfirst($c->status) }}</span></td>
      <td>{{ $c->featured ? '★' : '—' }}</td>
      <td>{{ $c->published_at?->format('M j, Y') ?? '—' }}</td>
      <td style="text-align:right">
        <a class="btn btn-outline btn-sm" href="{{ route('comparisons.show', $c->slug) }}" target="_blank" rel="noopener">View</a>
        <form method="POST" action="{{ route('admin.comparisons.destroy', $c) }}" style="display:inline" onsubmit="return confirm('Delete comparison?')">@csrf @method('DELETE')<button class="btn btn-danger btn-sm">Delete</button></form>
      </td>
    </tr>
  @empty
    <tr><td colspan="6">No comparisons yet.</td></tr>
  @endforelse
</table>
{{ $comparisons->links('partials.pagination') }}
@endsection